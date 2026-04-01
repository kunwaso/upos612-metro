<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Services\FinanceCore\DocumentWorkflowService;
use Tests\TestCase;

class DocumentWorkflowServiceTest extends TestCase
{
    public function test_it_identifies_non_posting_purchase_orders(): void
    {
        $service = new DocumentWorkflowService();
        $document = new FinanceDocument([
            'document_family' => 'procurement',
            'document_type' => 'purchase_order',
        ]);

        $this->assertFalse($service->isPostingDocument($document));
    }

    public function test_it_identifies_supplier_invoices_as_posting_documents(): void
    {
        $service = new DocumentWorkflowService();
        $document = new FinanceDocument([
            'document_family' => 'payables',
            'document_type' => 'supplier_invoice',
        ]);

        $this->assertTrue($service->isPostingDocument($document));
    }

    public function test_it_identifies_cash_transfers_as_posting_documents(): void
    {
        $service = new DocumentWorkflowService();
        $document = new FinanceDocument([
            'document_family' => 'cash_bank',
            'document_type' => 'cash_transfer',
        ]);

        $this->assertTrue($service->isPostingDocument($document));
    }

    public function test_it_transitions_supplier_invoice_to_matched(): void
    {
        $service = new DocumentWorkflowService();
        $document = new FinanceDocument([
            'document_family' => 'payables',
            'document_type' => 'supplier_invoice',
            'workflow_status' => 'approved',
            'accounting_status' => 'ready_to_post',
        ]);

        $target = $service->transition($document, 'match');

        $this->assertSame('matched', $target['workflow_status']);
        $this->assertSame('ready_to_post', $target['accounting_status']);
    }

    public function test_it_requires_completion_state_for_sales_order_fulfillment(): void
    {
        $service = new DocumentWorkflowService();
        $document = new FinanceDocument([
            'document_family' => 'sales',
            'document_type' => 'sales_order',
            'workflow_status' => 'approved',
            'accounting_status' => 'not_ready',
        ]);

        $this->expectException(\RuntimeException::class);

        $service->transition($document, 'fulfill');
    }
}
