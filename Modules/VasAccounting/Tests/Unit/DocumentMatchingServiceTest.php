<?php

namespace Modules\VasAccounting\Tests\Unit;

use Illuminate\Support\Collection;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLine;
use Modules\VasAccounting\Services\FinanceCore\DocumentMatchingService;
use Tests\TestCase;

class DocumentMatchingServiceTest extends TestCase
{
    public function test_it_matches_supplier_invoice_lines_against_goods_receipts_within_tolerance(): void
    {
        $service = new DocumentMatchingService();
        $invoice = $this->supplierInvoice([
            $this->line(11, 1, 10, 100, 10),
        ]);
        $goodsReceipt = $this->sourceDocument('goods_receipt', [
            $this->line(21, 1, 10, 100, 10),
        ]);

        $result = $service->evaluateSupplierInvoice($invoice, new Collection([$goodsReceipt]));

        $this->assertSame('matched', $result['status']);
        $this->assertSame(0, $result['blocking_exception_count']);
        $this->assertSame(1, $result['matched_line_count']);
        $this->assertSame('matched', $result['line_results'][0]['status']);
    }

    public function test_it_blocks_supplier_invoice_when_line_has_no_matching_source(): void
    {
        $service = new DocumentMatchingService();
        $invoice = $this->supplierInvoice([
            $this->line(12, 7, 5, 75, 7.5),
        ]);
        $goodsReceipt = $this->sourceDocument('goods_receipt', [
            $this->line(22, 1, 5, 75, 7.5),
        ]);

        $result = $service->evaluateSupplierInvoice($invoice, new Collection([$goodsReceipt]));

        $this->assertSame('blocked', $result['status']);
        $this->assertSame(1, $result['blocking_exception_count']);
        $this->assertSame('missing_source_line', $result['exceptions'][0]['code']);
    }

    public function test_it_flags_purchase_order_only_matching_as_warning_when_allowed(): void
    {
        $service = new DocumentMatchingService();
        $invoice = $this->supplierInvoice([
            $this->line(13, 3, 4, 80, 8),
        ]);
        $purchaseOrder = $this->sourceDocument('purchase_order', [
            $this->line(23, 3, 4, 80, 8),
        ]);

        $result = $service->evaluateSupplierInvoice($invoice, new Collection([$purchaseOrder]), [
            'allow_purchase_order_only' => true,
        ]);

        $this->assertSame('matched_with_warning', $result['status']);
        $this->assertSame(0, $result['blocking_exception_count']);
        $this->assertSame(1, $result['warning_count']);
        $this->assertSame('purchase_order_only_match', $result['exceptions'][0]['code']);
    }

    public function test_it_blocks_when_amount_variance_exceeds_tolerance(): void
    {
        $service = new DocumentMatchingService();
        $invoice = $this->supplierInvoice([
            $this->line(14, 9, 2, 50, 5),
        ]);
        $goodsReceipt = $this->sourceDocument('goods_receipt', [
            $this->line(24, 9, 2, 45, 5),
        ]);

        $result = $service->evaluateSupplierInvoice($invoice, new Collection([$goodsReceipt]), [
            'amount_variance_tolerance' => '0.5000',
        ]);

        $this->assertSame('blocked', $result['status']);
        $this->assertSame('amount_variance_exceeded', $result['exceptions'][0]['code']);
        $this->assertSame('5.0000', $result['line_results'][0]['variance_amount']);
    }

    protected function supplierInvoice(array $lines): FinanceDocument
    {
        $document = new FinanceDocument([
            'id' => 101,
            'business_id' => 1,
            'document_family' => 'payables',
            'document_type' => 'supplier_invoice',
        ]);
        $document->setRelation('lines', new Collection($lines));

        return $document;
    }

    protected function sourceDocument(string $documentType, array $lines): FinanceDocument
    {
        $document = new FinanceDocument([
            'id' => $documentType === 'goods_receipt' ? 201 : 301,
            'business_id' => 1,
            'document_family' => 'procurement',
            'document_type' => $documentType,
        ]);
        $document->setRelation('lines', new Collection($lines));

        return $document;
    }

    protected function line(int $id, int $productId, float $quantity, float $lineAmount, float $taxAmount): FinanceDocumentLine
    {
        return new FinanceDocumentLine([
            'id' => $id,
            'line_no' => $id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'line_amount' => $lineAmount,
            'tax_amount' => $taxAmount,
        ]);
    }
}
