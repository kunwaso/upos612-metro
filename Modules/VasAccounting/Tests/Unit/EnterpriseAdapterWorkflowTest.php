<?php

namespace Modules\VasAccounting\Tests\Unit;

use Mockery;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Services\Adapters\EssentialsPayrollBridgeAdapter;
use Modules\VasAccounting\Services\Adapters\LocalTaxExportAdapter;
use Modules\VasAccounting\Services\Adapters\NullBankStatementImportAdapter;
use Modules\VasAccounting\Services\Adapters\PaymentDocumentAdapter;
use Modules\VasAccounting\Services\Adapters\SandboxEInvoiceAdapter;
use Modules\VasAccounting\Services\Adapters\StockAdjustmentDocumentAdapter;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class EnterpriseAdapterWorkflowTest extends TestCase
{
    public function test_null_bank_statement_adapter_normalizes_lines(): void
    {
        $adapter = new NullBankStatementImportAdapter();

        $result = $adapter->import([
            'provider' => 'manual',
            'lines' => [
                [
                    'transaction_date' => '2026-03-28',
                    'description' => 'Incoming transfer',
                    'amount' => '1500.50',
                    'running_balance' => '4500.75',
                ],
            ],
        ]);

        $this->assertSame('queued_for_manual_reconciliation', $result['status']);
        $this->assertSame('manual', $result['provider']);
        $this->assertSame(1500.5, $result['lines'][0]['amount']);
        $this->assertSame('unmatched', $result['lines'][0]['match_status']);
    }

    public function test_local_tax_export_adapter_returns_metadata(): void
    {
        $adapter = new LocalTaxExportAdapter();

        $result = $adapter->export('vat_declaration', ['business_id' => 1]);

        $this->assertSame('local', $result['provider']);
        $this->assertSame('vat_declaration', $result['export_type']);
        $this->assertSame(1, $result['payload']['business_id']);
        $this->assertNotEmpty($result['generated_at']);
    }

    public function test_sandbox_einvoice_adapter_supports_lifecycle_actions(): void
    {
        $adapter = new SandboxEInvoiceAdapter();

        $issued = $adapter->issue(['voucher_id' => 99]);
        $cancelled = $adapter->cancel(['provider_document_id' => $issued['provider_document_id']]);
        $corrected = $adapter->correct(['provider_document_id' => $issued['provider_document_id']]);
        $replaced = $adapter->replace(['provider_document_id' => $issued['provider_document_id']]);

        $this->assertSame('issued', $issued['status']);
        $this->assertSame('cancelled', $cancelled['status']);
        $this->assertSame('corrected', $corrected['status']);
        $this->assertSame('replaced', $replaced['status']);
    }

    public function test_payment_document_adapter_uses_bank_receipt_sequence_for_bank_customer_receipts(): void
    {
        $vasUtil = Mockery::mock(VasAccountingUtil::class);
        $vasUtil->shouldReceive('getOrCreateBusinessSettings')
            ->once()
            ->andReturn(new VasBusinessSetting([
                'posting_map' => [
                    'cash' => 111,
                    'bank' => 112,
                    'accounts_receivable' => 131,
                    'accounts_payable' => 331,
                ],
            ]));

        $adapter = new class($vasUtil) extends PaymentDocumentAdapter {
            protected function loadTransaction(int $transactionId)
            {
                return (object) [
                    'id' => $transactionId,
                    'business_id' => 1,
                    'type' => 'sell',
                    'contact_id' => 5,
                    'location_id' => 3,
                    'transaction_date' => '2026-03-28',
                    'created_by' => 9,
                ];
            }
        };
        $payload = $adapter->toVoucherPayload((object) [
            'id' => 20,
            'transaction_id' => 10,
            'transaction_no' => 'INV-0010',
            'method' => 'bank_transfer',
            'amount' => 1000,
            'paid_on' => '2026-03-28',
            'payment_ref_no' => 'PMT-100',
            'created_by' => 9,
        ]);

        $this->assertSame('bank_receipt', $payload['voucher_type']);
        $this->assertSame('bank_receipt', $payload['sequence_key']);
        $this->assertSame('PMT-100', $payload['external_reference']);
    }

    public function test_stock_adjustment_adapter_builds_delete_payload_from_snapshot_context(): void
    {
        $vasUtil = Mockery::mock(VasAccountingUtil::class);
        $vasUtil->shouldReceive('getOrCreateBusinessSettings')
            ->once()
            ->andReturn(new VasBusinessSetting([
                'posting_map' => [
                    'stock_adjustment' => 632,
                    'inventory' => 156,
                ],
            ]));

        $adapter = new class($vasUtil) extends StockAdjustmentDocumentAdapter {
            protected function resolveAmount($transaction, array $snapshot, int $sourceId, int $businessId, bool $isDeleted): float
            {
                return (float) ($snapshot['final_total'] ?? 0);
            }
        };

        $payload = $adapter->toVoucherPayload((object) ['id' => 91], [
            'is_deleted' => true,
            'source_snapshot' => [
                'id' => 91,
                'business_id' => 7,
                'location_id' => 3,
                'transaction_date' => '2026-03-29',
                'ref_no' => 'SA-00091',
                'created_by' => 11,
                'final_total' => 1500.5,
            ],
        ]);

        $this->assertSame(7, $payload['business_id']);
        $this->assertSame('stock_adjustment', $payload['source_type']);
        $this->assertSame(91, $payload['source_id']);
        $this->assertSame(3, $payload['business_location_id']);
        $this->assertSame('2026-03-29', $payload['posting_date']);
        $this->assertSame('SA-00091', $payload['reference']);
        $this->assertCount(2, $payload['lines']);
        $this->assertSame(632, $payload['lines'][0]['account_id']);
        $this->assertSame(1500.5, $payload['lines'][0]['debit']);
        $this->assertSame(156, $payload['lines'][1]['account_id']);
        $this->assertSame(1500.5, $payload['lines'][1]['credit']);
    }

    public function test_stock_adjustment_adapter_requires_source_identifiers(): void
    {
        $adapter = new class(Mockery::mock(VasAccountingUtil::class)) extends StockAdjustmentDocumentAdapter {
            protected function resolveAmount($transaction, array $snapshot, int $sourceId, int $businessId, bool $isDeleted): float
            {
                return 0;
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stock adjustment payload is missing required source identifiers.');

        $adapter->toVoucherPayload((object) ['id' => 0], [
            'is_deleted' => true,
            'source_snapshot' => [],
        ]);
    }

    public function test_essentials_payroll_bridge_adapter_builds_payroll_batch_payload(): void
    {
        $adapter = new EssentialsPayrollBridgeAdapter();

        $payload = $adapter->buildVoucherPayload([
            'business_id' => 1,
            'source_id' => 77,
            'source_type' => 'payroll_batch',
            'business_location_id' => 5,
            'posting_date' => '2026-03-31',
            'document_date' => '2026-03-31',
            'reference' => 'PAYROLL-0077-202603',
            'gross_total' => 100000,
            'net_total' => 100000,
            'created_by' => 9,
        ], [
            'salary_expense_account_id' => 6427,
            'payroll_payable_account_id' => 331,
            'status' => 'posted',
        ]);

        $this->assertSame('payroll_batch', $payload['source_type']);
        $this->assertSame('payroll', $payload['module_area']);
        $this->assertSame('payroll_batch', $payload['document_type']);
        $this->assertCount(2, $payload['lines']);
        $this->assertSame(5, $payload['lines'][0]['business_location_id']);
    }

    public function test_payment_document_adapter_uses_payroll_payment_sequence_for_payroll_transactions(): void
    {
        $vasUtil = Mockery::mock(VasAccountingUtil::class);
        $vasUtil->shouldReceive('getOrCreateBusinessSettings')
            ->once()
            ->andReturn(new VasBusinessSetting([
                'posting_map' => [
                    'cash' => 111,
                    'bank' => 112,
                    'accounts_receivable' => 131,
                    'accounts_payable' => 331,
                ],
            ]));

        $adapter = new class($vasUtil) extends PaymentDocumentAdapter {
            protected function loadTransaction(int $transactionId)
            {
                return (object) [
                    'id' => $transactionId,
                    'business_id' => 1,
                    'type' => 'payroll',
                    'contact_id' => null,
                    'location_id' => 2,
                    'transaction_date' => '2026-03-28',
                    'created_by' => 9,
                ];
            }
        };

        $payload = $adapter->toVoucherPayload((object) [
            'id' => 44,
            'transaction_id' => 10,
            'transaction_no' => 'PAYROLL-0010',
            'method' => 'bank_transfer',
            'amount' => 5000,
            'paid_on' => '2026-03-28',
            'payment_ref_no' => 'PAY-44',
            'created_by' => 9,
        ]);

        $this->assertSame('payroll_payment', $payload['voucher_type']);
        $this->assertSame('payroll_payment', $payload['sequence_key']);
        $this->assertSame('payroll', $payload['module_area']);
        $this->assertSame('payroll_payment', $payload['document_type']);
        $this->assertSame(331, $payload['lines'][0]['account_id']);
        $this->assertSame(112, $payload['lines'][1]['account_id']);
    }
}
