<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Services\Subledger\OpenItemService;
use Tests\TestCase;

class OpenItemServiceTest extends TestCase
{
    public function test_it_maps_customer_invoice_to_receivable_charge_profile(): void
    {
        $service = new OpenItemService();
        $document = new FinanceDocument(['document_type' => 'customer_invoice']);

        $profile = $service->resolveDocumentProfile($document);

        $this->assertSame('receivable', $profile['ledger_type']);
        $this->assertSame('charge', $profile['document_role']);
    }

    public function test_it_maps_supplier_payment_to_payable_settlement_profile(): void
    {
        $service = new OpenItemService();
        $document = new FinanceDocument(['document_type' => 'supplier_payment']);

        $profile = $service->resolveDocumentProfile($document);

        $this->assertSame('payable', $profile['ledger_type']);
        $this->assertSame('settlement', $profile['document_role']);
    }

    public function test_it_returns_null_for_non_subledger_document_types(): void
    {
        $service = new OpenItemService();
        $document = new FinanceDocument(['document_type' => 'manual_journal']);

        $this->assertNull($service->resolveDocumentProfile($document));
    }
}
