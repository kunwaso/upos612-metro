<?php

namespace Modules\VasAccounting\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Services\DocumentApprovalService;
use Modules\VasAccounting\Services\NativeDocumentMetaBuilder;
use Modules\VasAccounting\Services\PaymentDocumentService;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;
use Modules\VasAccounting\Utils\LedgerPostingUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use App\Utils\TransactionUtil;
use Tests\TestCase;

class PaymentDocumentServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_build_payload_builds_outgoing_bank_payment_with_normalized_meta_and_lines(): void
    {
        $service = $this->makeService([
            'posting_map' => [
                'accounts_payable' => 201,
                'accounts_receivable' => 301,
                'bank' => 401,
                'cash' => 402,
            ],
            'book_currency' => 'usd',
        ], [
            'payment' => [
                'coexistence_mode' => 'parallel',
            ],
        ]);

        $payload = $this->invokeBuildPayload($service, 11, [
            'payment_kind' => 'bank_payment',
            'amount' => '1250.5',
            'contact_id' => 17,
            'business_location_id' => 9,
            'document_date' => '2026-03-05',
            'posting_date' => '2026-03-06',
            'reference' => 'PAY-001',
            'external_reference' => 'BANK-EXT-99',
            'settlement_targets' => [
                [
                    'target_voucher_id' => '88',
                    'amount' => '300.125',
                    'legacy_transaction_id' => '444',
                ],
                [
                    'target_voucher_id' => 0,
                    'amount' => 50,
                ],
            ],
            'notes' => 'settle vendor',
        ], 5);

        $this->assertSame(11, $payload['business_id']);
        $this->assertSame('bank_payment', $payload['voucher_type']);
        $this->assertSame('bank_payment', $payload['sequence_key']);
        $this->assertSame('native_payment', $payload['source_type']);
        $this->assertNull($payload['source_id']);
        $this->assertNull($payload['transaction_id']);
        $this->assertSame(17, $payload['contact_id']);
        $this->assertSame(9, $payload['business_location_id']);
        $this->assertSame('2026-03-06', $payload['posting_date']);
        $this->assertSame('2026-03-05', $payload['document_date']);
        $this->assertSame('PAY-001', $payload['reference']);
        $this->assertSame('BANK-EXT-99', $payload['external_reference']);
        $this->assertSame('draft', $payload['status']);
        $this->assertSame('USD', $payload['currency_code']);
        $this->assertNull($payload['exchange_rate']);
        $this->assertSame('cash_bank', $payload['module_area']);
        $this->assertSame('bank_payment', $payload['document_type']);
        $this->assertFalse($payload['is_system_generated']);

        $this->assertCount(2, $payload['lines']);
        $this->assertSame(201, $payload['lines'][0]['account_id']);
        $this->assertSame(1250.5, $payload['lines'][0]['debit']);
        $this->assertSame(0.0, $payload['lines'][0]['credit']);
        $this->assertSame('Settlement against payable', $payload['lines'][0]['description']);
        $this->assertSame(401, $payload['lines'][1]['account_id']);
        $this->assertSame(0.0, $payload['lines'][1]['debit']);
        $this->assertSame(1250.5, $payload['lines'][1]['credit']);
        $this->assertSame('Cash or bank outflow', $payload['lines'][1]['description']);
        $this->assertSame(1250.5, array_sum(array_column($payload['lines'], 'debit')));
        $this->assertSame(1250.5, array_sum(array_column($payload['lines'], 'credit')));

        $this->assertSame('bank_transfer', $payload['meta']['payment']['payment_method']);
        $this->assertSame('PAY-001', $payload['meta']['payment']['legacy_reference']);
        $this->assertSame('settle vendor', $payload['meta']['payment']['notes']);
        $this->assertSame(
            [
                [
                    'target_type' => 'payable',
                    'target_voucher_id' => 88,
                    'amount' => 300.125,
                    'legacy_transaction_id' => 444,
                ],
            ],
            $payload['meta']['payment']['settlement_targets']
        );
        $this->assertArrayNotHasKey('transaction_id', $payload['meta']['legacy_links']);
        $this->assertSame('PAY-001', $payload['meta']['legacy_links']['payment_ref_no']);
        $this->assertSame('parallel', $payload['meta']['coexistence']['mode']);
    }

    public function test_build_payload_builds_incoming_cash_receipt_with_receivable_lines(): void
    {
        $service = $this->makeService([
            'posting_map' => [
                'accounts_payable' => 201,
                'accounts_receivable' => 301,
                'bank' => 401,
                'cash' => 402,
            ],
            'book_currency' => 'VND',
        ], [
            'payment' => [
                'coexistence_mode' => 'mirror',
            ],
        ]);

        $payload = $this->invokeBuildPayload($service, 11, [
            'payment_kind' => 'cash_receipt',
            'amount' => 999.99,
            'contact_id' => 44,
            'document_date' => '2026-03-08',
            'posting_date' => '2026-03-09',
            'reference' => 'RCPT-100',
            'settlement_targets' => [
                [
                    'target_voucher_id' => 91,
                    'amount' => 100,
                ],
            ],
        ], 5);

        $this->assertSame('cash_receipt', $payload['voucher_type']);
        $this->assertSame('cash_receipt', $payload['sequence_key']);
        $this->assertSame('native_payment', $payload['source_type']);
        $this->assertSame('cash_bank', $payload['module_area']);
        $this->assertSame('cash_receipt', $payload['document_type']);
        $this->assertSame('cash', $payload['meta']['payment']['payment_method']);
        $this->assertSame('RCPT-100', $payload['meta']['payment']['legacy_reference']);
        $this->assertSame('receivable', $payload['meta']['payment']['settlement_targets'][0]['target_type']);
        $this->assertSame('mirror', $payload['meta']['coexistence']['mode']);
        $this->assertArrayNotHasKey('transaction_id', $payload['meta']['legacy_links']);
        $this->assertSame(402, $payload['lines'][0]['account_id']);
        $this->assertSame(999.99, $payload['lines'][0]['debit']);
        $this->assertSame(301, $payload['lines'][1]['account_id']);
        $this->assertSame(999.99, $payload['lines'][1]['credit']);
        $this->assertSame(999.99, array_sum(array_column($payload['lines'], 'debit')));
        $this->assertSame(999.99, array_sum(array_column($payload['lines'], 'credit')));
    }

    private function makeService(array $settingsOverrides, array $families): PaymentDocumentService
    {
        $settings = new VasBusinessSetting(array_merge([
            'posting_map' => [],
            'book_currency' => 'VND',
        ], $settingsOverrides));

        $vasUtil = Mockery::mock(VasAccountingUtil::class);
        $vasUtil->shouldReceive('getOrCreateBusinessSettings')->once()->andReturn($settings);
        $vasUtil->shouldReceive('nativeDocumentFamilies')->once()->andReturn($families);

        $ledgerPostingUtil = Mockery::mock(LedgerPostingUtil::class);

        return new PaymentDocumentService(
            Mockery::mock(VasPostingService::class),
            Mockery::mock(DocumentApprovalService::class),
            Mockery::mock(EnterpriseFinanceReportUtil::class),
            $vasUtil,
            $ledgerPostingUtil,
            new NativeDocumentMetaBuilder(),
            Mockery::mock(TransactionUtil::class)
        );
    }

    private function invokeBuildPayload(PaymentDocumentService $service, int $businessId, array $data, int $userId): array
    {
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildPayload');
        $method->setAccessible(true);

        return $method->invoke($service, $businessId, $data, $userId);
    }
}
