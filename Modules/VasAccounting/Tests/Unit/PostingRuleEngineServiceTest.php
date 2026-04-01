<?php

namespace Modules\VasAccounting\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\VasAccounting\Contracts\DocumentTraceServiceInterface;
use Modules\VasAccounting\Contracts\InventoryCostServiceInterface;
use Modules\VasAccounting\Contracts\OpenItemServiceInterface;
use Modules\VasAccounting\Contracts\OrderToCashLifecycleServiceInterface;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLine;
use Modules\VasAccounting\Services\FinanceCore\AccountDerivationService;
use Modules\VasAccounting\Services\FinanceCore\DocumentWorkflowService;
use Modules\VasAccounting\Services\FinanceCore\PostingRuleEngineService;
use Tests\TestCase;

class PostingRuleEngineServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_preview_uses_document_account_hints_when_no_rule_set_is_active(): void
    {
        $document = new FinanceDocument([
            'business_id' => 1,
            'document_family' => 'sales',
            'document_type' => 'customer_invoice',
            'document_date' => '2026-03-31',
            'posting_date' => '2026-03-31',
            'workflow_status' => 'approved',
            'accounting_status' => 'ready_to_post',
        ]);
        $document->exists = false;
        $document->setRelation('lines', collect([
            new FinanceDocumentLine([
                'line_no' => 1,
                'description' => 'Revenue line',
                'line_amount' => 1000000,
                'debit_account_id' => 131,
                'credit_account_id' => 511,
            ]),
        ]));

        $service = Mockery::mock(PostingRuleEngineService::class, [
            new AccountDerivationService(),
            Mockery::mock(DocumentTraceServiceInterface::class),
            Mockery::mock(InventoryCostServiceInterface::class),
            Mockery::mock(OpenItemServiceInterface::class),
            Mockery::mock(OrderToCashLifecycleServiceInterface::class),
            new DocumentWorkflowService(),
        ])->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('resolveRuleSet')->once()->andReturnNull();

        $preview = $service->preview($document, 'post');

        $this->assertTrue($preview->isBalanced);
        $this->assertSame('1000000.0000', $preview->totalDebit);
        $this->assertSame('1000000.0000', $preview->totalCredit);
        $this->assertCount(2, $preview->lines);
        $this->assertSame('debit', $preview->lines[0]->entrySide);
        $this->assertSame('credit', $preview->lines[1]->entrySide);
    }
}
