<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Services\WorkflowApproval\MakerCheckerGuard;
use RuntimeException;
use Tests\TestCase;

class MakerCheckerGuardTest extends TestCase
{
    public function test_it_blocks_the_submitter_from_approving_when_maker_checker_is_enabled(): void
    {
        config()->set('vasaccounting.approval_defaults.finance_document_defaults.maker_checker', true);

        $guard = new MakerCheckerGuard();
        $document = new FinanceDocument(['submitted_by' => 25]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Maker-checker control prevents the document submitter from approving the same finance document.');

        $guard->assertCanApprove($document, 25);
    }

    public function test_it_allows_self_approval_when_maker_checker_is_disabled(): void
    {
        config()->set('vasaccounting.approval_defaults.finance_document_defaults.maker_checker', false);

        $guard = new MakerCheckerGuard();
        $document = new FinanceDocument(['submitted_by' => 25]);

        $guard->assertCanApprove($document, 25);

        $this->assertTrue(true);
    }

    public function test_it_uses_expense_specific_maker_checker_override_when_present(): void
    {
        config()->set('vasaccounting.approval_defaults.finance_document_defaults.maker_checker', true);
        config()->set('vasaccounting.approval_defaults.expense_document_policies.advance_settlement.maker_checker', false);

        $guard = new MakerCheckerGuard();
        $document = new FinanceDocument([
            'document_family' => 'expense_management',
            'document_type' => 'advance_settlement',
            'submitted_by' => 25,
        ]);

        $guard->assertCanApprove($document, 25);

        $this->assertTrue(true);
    }
}
