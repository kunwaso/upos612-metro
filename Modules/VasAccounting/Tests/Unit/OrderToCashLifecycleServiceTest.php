<?php

namespace Modules\VasAccounting\Tests\Unit;

use Illuminate\Support\Collection;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLine;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceOpenItem;
use Modules\VasAccounting\Services\Sales\OrderToCashLifecycleService;
use Tests\TestCase;

class OrderToCashLifecycleServiceTest extends TestCase
{
    public function test_it_marks_sales_order_as_partially_delivered_when_linked_delivery_qty_is_incomplete(): void
    {
        $service = new OrderToCashLifecycleService();
        $salesOrder = $this->document('sales_order', 'sales', 'released', 'not_ready', [
            $this->line(1, 10),
        ]);
        $delivery = $this->document('delivery', 'sales', 'posted', 'posted', [
            $this->line(2, 4),
        ]);

        $summary = $service->calculateSalesOrderSummary($salesOrder, new Collection([$delivery]));

        $this->assertSame('partially_delivered', $summary['workflow_status']);
        $this->assertSame('10.0000', $summary['ordered_quantity']);
        $this->assertSame('4.0000', $summary['delivered_quantity']);
        $this->assertSame(1, $summary['posted_delivery_count']);
    }

    public function test_it_marks_sales_order_as_fully_delivered_when_delivery_qty_is_complete(): void
    {
        $service = new OrderToCashLifecycleService();
        $salesOrder = $this->document('sales_order', 'sales', 'released', 'not_ready', [
            $this->line(1, 10),
        ]);
        $delivery = $this->document('delivery', 'sales', 'posted', 'posted', [
            $this->line(2, 10),
        ]);

        $summary = $service->calculateSalesOrderSummary($salesOrder, new Collection([$delivery]));

        $this->assertSame('fully_delivered', $summary['workflow_status']);
        $this->assertEquals(1.0, $summary['delivery_progress']);
    }

    public function test_it_marks_customer_invoice_as_partially_collected_when_open_amount_remains(): void
    {
        $service = new OrderToCashLifecycleService();
        $invoice = $this->document('customer_invoice', 'receivables', 'posted', 'posted', []);
        $openItem = new FinanceOpenItem([
            'original_amount' => 1000,
            'open_amount' => 250,
            'settled_amount' => 750,
        ]);

        $summary = $service->calculateInvoiceCollectionSummary($invoice, $openItem);

        $this->assertSame('partially_collected', $summary['workflow_status']);
        $this->assertSame('250.0000', $summary['open_amount']);
        $this->assertSame('750.0000', $summary['settled_amount']);
    }

    public function test_it_marks_customer_invoice_as_collected_when_open_amount_is_zero(): void
    {
        $service = new OrderToCashLifecycleService();
        $invoice = $this->document('customer_invoice', 'receivables', 'posted', 'posted', []);
        $openItem = new FinanceOpenItem([
            'original_amount' => 1000,
            'open_amount' => 0,
            'settled_amount' => 1000,
        ]);

        $summary = $service->calculateInvoiceCollectionSummary($invoice, $openItem);

        $this->assertSame('collected', $summary['workflow_status']);
        $this->assertEquals(1.0, $summary['collection_ratio']);
    }

    public function test_it_rolls_invoice_amounts_into_delivery_summary(): void
    {
        $service = new OrderToCashLifecycleService();
        $delivery = $this->document('delivery', 'sales', 'posted', 'posted', [
            $this->line(1, 5),
        ]);
        $invoice = $this->document('customer_invoice', 'receivables', 'partially_collected', 'posted', []);
        $invoice->setRelation('openItems', collect([
            new FinanceOpenItem([
                'ledger_type' => 'receivable',
                'document_role' => 'charge',
                'status' => 'partial',
                'original_amount' => 1000,
                'open_amount' => 300,
                'settled_amount' => 700,
            ]),
        ]));

        $summary = $service->calculateDeliverySummary($delivery, new Collection([$invoice]));

        $this->assertSame(1, $summary['posted_invoice_count']);
        $this->assertSame('1000.0000', $summary['invoiced_amount']);
        $this->assertSame('700.0000', $summary['collected_amount']);
        $this->assertSame('300.0000', $summary['open_receivable_amount']);
    }

    protected function document(
        string $documentType,
        string $family,
        string $workflowStatus,
        string $accountingStatus,
        array $lines
    ): FinanceDocument {
        $document = new FinanceDocument([
            'id' => random_int(100, 999),
            'document_type' => $documentType,
            'document_family' => $family,
            'workflow_status' => $workflowStatus,
            'accounting_status' => $accountingStatus,
            'gross_amount' => 1000,
            'open_amount' => 1000,
        ]);
        $document->setRelation('lines', collect($lines));
        $document->setRelation('openItems', collect());

        return $document;
    }

    protected function line(int $id, float $quantity): FinanceDocumentLine
    {
        return new FinanceDocumentLine([
            'id' => $id,
            'line_no' => $id,
            'quantity' => $quantity,
        ]);
    }
}
