<?php

namespace Modules\VasAccounting\Tests\Unit;

use App\Utils\ProductUtil;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Services\ApprovalRuleService;
use Modules\VasAccounting\Services\DocumentApprovalService;
use Modules\VasAccounting\Services\NativeDocumentMetaBuilder;
use Modules\VasAccounting\Services\NativeInvoiceService;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Utils\LedgerPostingUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;
use Tests\TestCase;

class NativeInvoiceServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_build_draft_payload_for_purchase_invoice_balances_and_sets_invoice_meta(): void
    {
        config(['vasaccounting.native_document_families.invoice.sequence_keys.purchase_invoice' => 'purchase_invoice']);

        $settings = new VasBusinessSetting([
            'posting_map' => [
                'accounts_payable' => 201,
                'vat_input' => 1331,
            ],
        ]);

        $period = new VasAccountingPeriod([
            'id' => 77,
            'name' => '2026-03',
            'status' => 'open',
        ]);

        $ledgerPostingUtil = Mockery::mock(LedgerPostingUtil::class);
        $ledgerPostingUtil->shouldReceive('buildSourceHash')->once()->andReturn('hash-1');

        $vasUtil = Mockery::mock(VasAccountingUtil::class);
        $vasUtil->shouldReceive('resolvePeriodForDate')->twice()->with(5, Mockery::type(\Carbon\Carbon::class))->andReturn($period);
        $vasUtil->shouldReceive('getOrCreateBusinessSettings')->once()->with(5)->andReturn($settings);

        $approvalRuleService = Mockery::mock(ApprovalRuleService::class);
        $approvalRuleService->shouldReceive('defaultStatus')->once()->with(5, 'invoice', Mockery::type('array'))->andReturn('draft');
        $approvalRuleService->shouldReceive('requiresApproval')->once()->with(5, 'invoice', Mockery::type('array'))->andReturn(true);

        $productUtil = Mockery::mock(ProductUtil::class);

        $service = new NativeInvoiceService(
            $ledgerPostingUtil,
            $vasUtil,
            $approvalRuleService,
            Mockery::mock(DocumentApprovalService::class),
            Mockery::mock(VasPostingService::class),
            new NativeDocumentMetaBuilder(),
            $productUtil
        );

        $payload = $service->buildDraftPayload(5, [
            'invoice_kind' => 'purchase_invoice',
            'contact_id' => 22,
            'business_location_id' => 9,
            'document_date' => '2026-03-10',
            'posting_date' => '2026-03-10',
            'due_date' => '2026-03-25',
            'currency_code' => 'usd',
            'exchange_rate' => 1.2,
            'reference' => 'PINV-0001',
            'external_reference' => 'SUP-INV-88',
            'description' => 'Supplier invoice',
            'line_items' => [
                ['account_id' => 611, 'description' => 'Raw material', 'net_amount' => 1000, 'tax_amount' => 100],
                ['account_id' => 612, 'description' => 'Freight', 'net_amount' => 500, 'tax_amount' => 0],
            ],
        ]);

        $this->assertSame(5, $payload['business_id']);
        $this->assertSame(77, $payload['accounting_period_id']);
        $this->assertSame('purchase_invoice', $payload['voucher_type']);
        $this->assertSame('purchase_invoice', $payload['sequence_key']);
        $this->assertSame('native_invoice', $payload['source_type']);
        $this->assertNull($payload['source_id']);
        $this->assertSame('draft', $payload['status']);
        $this->assertSame('USD', $payload['currency_code']);
        $this->assertSame(1.2, $payload['exchange_rate']);
        $this->assertSame(1600.0, $payload['total_debit']);
        $this->assertSame(1600.0, $payload['total_credit']);
        $this->assertSame('hash-1', $payload['source_hash']);
        $this->assertCount(4, $payload['lines']);

        $this->assertSame(611, $payload['lines'][0]['account_id']);
        $this->assertEquals(1000.0, $payload['lines'][0]['debit']);
        $this->assertEquals(0.0, $payload['lines'][0]['credit']);

        $this->assertSame(1331, $payload['lines'][1]['account_id']);
        $this->assertEquals(100.0, $payload['lines'][1]['debit']);
        $this->assertEquals(0.0, $payload['lines'][1]['credit']);

        $this->assertSame(201, $payload['lines'][3]['account_id']);
        $this->assertEquals(0.0, $payload['lines'][3]['debit']);
        $this->assertEquals(1600.0, $payload['lines'][3]['credit']);

        $this->assertSame('invoice', $payload['meta']['document_family']);
        $this->assertSame('purchase', $payload['meta']['document_direction']);
        $this->assertSame('purchase_invoice', $payload['meta']['invoice']['invoice_kind']);
        $this->assertSame('vendor', $payload['meta']['invoice']['counterparty_type']);
        $this->assertSame(22, $payload['meta']['invoice']['counterparty_id']);
        $this->assertSame(100.0, $payload['meta']['invoice']['tax_summary']['tax_amount']);
        $this->assertSame('PINV-0001', $payload['meta']['legacy_links']['purchase_ref_no']);
        $this->assertTrue($payload['meta']['lifecycle']['requires_approval']);
    }

    public function test_build_draft_payload_for_debit_note_flips_ap_directions_and_generates_reference(): void
    {
        config(['vasaccounting.native_document_families.invoice.sequence_keys.purchase_debit_note' => 'purchase_debit_note']);

        $settings = new VasBusinessSetting([
            'posting_map' => [
                'accounts_payable' => 201,
                'vat_input' => 1331,
            ],
        ]);

        $period = new VasAccountingPeriod([
            'id' => 78,
            'name' => '2026-04',
            'status' => 'open',
        ]);

        $ledgerPostingUtil = Mockery::mock(LedgerPostingUtil::class);
        $ledgerPostingUtil->shouldReceive('buildSourceHash')->once()->andReturn('hash-2');

        $vasUtil = Mockery::mock(VasAccountingUtil::class);
        $vasUtil->shouldReceive('resolvePeriodForDate')->twice()->with(5, Mockery::type(\Carbon\Carbon::class))->andReturn($period);
        $vasUtil->shouldReceive('getOrCreateBusinessSettings')->once()->with(5)->andReturn($settings);

        $approvalRuleService = Mockery::mock(ApprovalRuleService::class);
        $approvalRuleService->shouldReceive('defaultStatus')->once()->with(5, 'invoice', Mockery::type('array'))->andReturn('draft');
        $approvalRuleService->shouldReceive('requiresApproval')->once()->with(5, 'invoice', Mockery::type('array'))->andReturn(false);

        $productUtil = Mockery::mock(ProductUtil::class);
        $productUtil->shouldReceive('setAndGetReferenceCount')->once()->with('purchase_return', 5)->andReturn(34);
        $productUtil->shouldReceive('generateReferenceNumber')->once()->with('purchase_return', 34, 5)->andReturn('PRN-00034');

        $service = new NativeInvoiceService(
            $ledgerPostingUtil,
            $vasUtil,
            $approvalRuleService,
            Mockery::mock(DocumentApprovalService::class),
            Mockery::mock(VasPostingService::class),
            new NativeDocumentMetaBuilder(),
            $productUtil
        );

        $payload = $service->buildDraftPayload(5, [
            'invoice_kind' => 'purchase_debit_note',
            'contact_id' => 22,
            'document_date' => '2026-04-01',
            'posting_date' => '2026-04-01',
            'line_items' => [
                ['account_id' => 611, 'description' => 'Debit note line', 'net_amount' => 200, 'tax_amount' => 0],
            ],
        ]);

        $this->assertSame('purchase_debit_note', $payload['voucher_type']);
        $this->assertSame('purchase_debit_note', $payload['sequence_key']);
        $this->assertSame('PRN-00034', $payload['reference']);
        $this->assertSame(200.0, $payload['total_debit']);
        $this->assertSame(200.0, $payload['total_credit']);
        $this->assertSame('hash-2', $payload['source_hash']);
        $this->assertCount(2, $payload['lines']);

        $this->assertSame(611, $payload['lines'][0]['account_id']);
        $this->assertEquals(0.0, $payload['lines'][0]['debit']);
        $this->assertEquals(200.0, $payload['lines'][0]['credit']);

        $this->assertSame(201, $payload['lines'][1]['account_id']);
        $this->assertEquals(200.0, $payload['lines'][1]['debit']);
        $this->assertEquals(0.0, $payload['lines'][1]['credit']);
        $this->assertFalse($payload['meta']['lifecycle']['requires_approval']);
        $this->assertSame('purchase_debit_note', $payload['meta']['invoice']['invoice_kind']);
    }

    public function test_build_draft_payload_rejects_unsupported_invoice_kind(): void
    {
        $service = new NativeInvoiceService(
            Mockery::mock(LedgerPostingUtil::class),
            Mockery::mock(VasAccountingUtil::class),
            Mockery::mock(ApprovalRuleService::class),
            Mockery::mock(DocumentApprovalService::class),
            Mockery::mock(VasPostingService::class),
            new NativeDocumentMetaBuilder(),
            Mockery::mock(ProductUtil::class)
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported native invoice kind.');

        $service->buildDraftPayload(5, [
            'invoice_kind' => 'unsupported_kind',
        ]);
    }

    public function test_build_draft_payload_for_sales_invoice_sets_ar_lines_and_public_token(): void
    {
        config(['vasaccounting.native_document_families.invoice.sequence_keys.sales_invoice' => 'sales_invoice']);
        config(['vasaccounting.native_document_families.invoice.sales_guardrails.block_inventory_impact' => true]);

        $settings = new VasBusinessSetting([
            'posting_map' => [
                'accounts_receivable' => 131,
                'vat_output' => 3331,
            ],
        ]);

        $period = new VasAccountingPeriod([
            'id' => 79,
            'name' => '2026-05',
            'status' => 'open',
        ]);

        $ledgerPostingUtil = Mockery::mock(LedgerPostingUtil::class);
        $ledgerPostingUtil->shouldReceive('buildSourceHash')->once()->andReturn('hash-3');

        $vasUtil = Mockery::mock(VasAccountingUtil::class);
        $vasUtil->shouldReceive('resolvePeriodForDate')->twice()->with(5, Mockery::type(\Carbon\Carbon::class))->andReturn($period);
        $vasUtil->shouldReceive('getOrCreateBusinessSettings')->once()->with(5)->andReturn($settings);

        $approvalRuleService = Mockery::mock(ApprovalRuleService::class);
        $approvalRuleService->shouldReceive('defaultStatus')->once()->with(5, 'invoice', Mockery::type('array'))->andReturn('draft');
        $approvalRuleService->shouldReceive('requiresApproval')->once()->with(5, 'invoice', Mockery::type('array'))->andReturn(true);

        $service = new NativeInvoiceService(
            $ledgerPostingUtil,
            $vasUtil,
            $approvalRuleService,
            Mockery::mock(DocumentApprovalService::class),
            Mockery::mock(VasPostingService::class),
            new NativeDocumentMetaBuilder(),
            Mockery::mock(ProductUtil::class)
        );

        $payload = $service->buildDraftPayload(5, [
            'invoice_kind' => 'sales_invoice',
            'contact_id' => 43,
            'document_date' => '2026-05-10',
            'posting_date' => '2026-05-10',
            'reference' => 'SINV-0001',
            'line_items' => [
                ['account_id' => 511, 'description' => 'Sales line', 'net_amount' => 1500, 'tax_amount' => 150],
            ],
        ]);

        $this->assertSame('sales_invoice', $payload['voucher_type']);
        $this->assertSame('sales_invoice', $payload['sequence_key']);
        $this->assertSame(1650.0, $payload['total_debit']);
        $this->assertSame(1650.0, $payload['total_credit']);
        $this->assertCount(3, $payload['lines']);

        $this->assertSame(511, $payload['lines'][0]['account_id']);
        $this->assertEquals(0.0, $payload['lines'][0]['debit']);
        $this->assertEquals(1500.0, $payload['lines'][0]['credit']);
        $this->assertSame(3331, $payload['lines'][1]['account_id']);
        $this->assertEquals(150.0, $payload['lines'][1]['credit']);
        $this->assertSame(131, $payload['lines'][2]['account_id']);
        $this->assertEquals(1650.0, $payload['lines'][2]['debit']);

        $this->assertSame('sales', $payload['meta']['document_direction']);
        $this->assertSame('customer', $payload['meta']['invoice']['counterparty_type']);
        $this->assertSame('SINV-0001', $payload['meta']['legacy_links']['invoice_no']);
        $this->assertNotEmpty($payload['meta']['invoice']['public_token']);
        $this->assertSame('hash-3', $payload['source_hash']);
    }

    public function test_build_draft_payload_blocks_inventory_impact_for_sales_when_guardrail_enabled(): void
    {
        config(['vasaccounting.native_document_families.invoice.sales_guardrails.block_inventory_impact' => true]);

        $settings = new VasBusinessSetting([
            'posting_map' => [
                'accounts_receivable' => 131,
                'vat_output' => 3331,
            ],
        ]);

        $period = new VasAccountingPeriod([
            'id' => 80,
            'name' => '2026-06',
            'status' => 'open',
        ]);

        $vasUtil = Mockery::mock(VasAccountingUtil::class);
        $vasUtil->shouldReceive('resolvePeriodForDate')->once()->with(5, Mockery::type(\Carbon\Carbon::class))->andReturn($period);
        $vasUtil->shouldReceive('getOrCreateBusinessSettings')->once()->with(5)->andReturn($settings);

        $approvalRuleService = Mockery::mock(ApprovalRuleService::class);
        $approvalRuleService->shouldReceive('defaultStatus')->never();
        $approvalRuleService->shouldReceive('requiresApproval')->never();

        $service = new NativeInvoiceService(
            Mockery::mock(LedgerPostingUtil::class),
            $vasUtil,
            $approvalRuleService,
            Mockery::mock(DocumentApprovalService::class),
            Mockery::mock(VasPostingService::class),
            new NativeDocumentMetaBuilder(),
            Mockery::mock(ProductUtil::class)
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('financial-only');

        $service->buildDraftPayload(5, [
            'invoice_kind' => 'sales_invoice',
            'contact_id' => 1,
            'document_date' => '2026-06-10',
            'posting_date' => '2026-06-10',
            'line_items' => [
                [
                    'account_id' => 511,
                    'net_amount' => 100,
                    'tax_amount' => 0,
                    'product_id' => 999,
                ],
            ],
        ]);
    }
}
