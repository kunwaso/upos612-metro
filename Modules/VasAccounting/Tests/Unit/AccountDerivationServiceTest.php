<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Application\DTOs\AccountDerivationInput;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLine;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinancePostingRuleLine;
use Modules\VasAccounting\Services\FinanceCore\AccountDerivationService;
use Tests\TestCase;

class AccountDerivationServiceTest extends TestCase
{
    public function test_it_derives_a_fixed_account_from_the_rule_line(): void
    {
        $service = new AccountDerivationService();
        $document = new FinanceDocument(['document_family' => 'sales', 'document_type' => 'customer_invoice']);
        $documentLine = new FinanceDocumentLine(['line_no' => 1, 'credit_account_id' => 511]);
        $ruleLine = new FinancePostingRuleLine(['account_source' => 'fixed', 'fixed_account_id' => 131]);

        $result = $service->derive(new AccountDerivationInput($document, $documentLine, $ruleLine, 'debit'));

        $this->assertSame(131, $result->accountId);
        $this->assertSame([], $result->warnings);
    }

    public function test_it_returns_warnings_when_no_account_can_be_derived(): void
    {
        $service = new AccountDerivationService();
        $document = new FinanceDocument(['document_family' => 'sales', 'document_type' => 'customer_invoice']);
        $documentLine = new FinanceDocumentLine(['line_no' => 1]);

        $result = $service->derive(new AccountDerivationInput($document, $documentLine, null, 'debit'));

        $this->assertNull($result->accountId);
        $this->assertNotEmpty($result->warnings);
    }
}
