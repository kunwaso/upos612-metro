<?php

namespace Modules\VasAccounting\Tests\Unit;

use Mockery;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Services\Adapters\PaymentDocumentAdapter;
use Modules\VasAccounting\Services\Adapters\PurchaseDocumentAdapter;
use Modules\VasAccounting\Services\Adapters\PurchaseReturnDocumentAdapter;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class DeleteSnapshotAdaptersTest extends TestCase
{
    public function test_purchase_document_adapter_builds_delete_payload_from_snapshot(): void
    {
        $vasUtil = Mockery::mock(VasAccountingUtil::class);
        $vasUtil->shouldReceive('getOrCreateBusinessSettings')
            ->once()
            ->andReturn(new VasBusinessSetting([
                'posting_map' => [
                    'inventory' => 156,
                    'accounts_payable' => 331,
                    'vat_input' => 1331,
                ],
            ]));

        $adapter = new PurchaseDocumentAdapter($vasUtil);

        $context = [
            'is_deleted' => true,
            'source_snapshot' => [
                'id' => 20,
                'business_id' => 44,
                'location_id' => 3,
                'contact_id' => 11,
                'transaction_date' => '2026-04-04',
                'ref_no' => 'PUR-0020',
                'created_by' => 7,
                'final_total' => 110,
                'tax_amount' => 10,
            ],
        ];

        $payload = $adapter->toVoucherPayload($adapter->loadSourceDocument(20, $context), $context);

        $this->assertSame('purchase_invoice_reversal', $payload['voucher_type']);
        $this->assertNull($payload['transaction_id']);
        $this->assertSame('Reversed purchase PUR-0020', $payload['description']);
        $this->assertSame(331, $payload['lines'][0]['account_id']);
        $this->assertSame(110.0, $payload['lines'][0]['debit']);
        $this->assertSame(156, $payload['lines'][1]['account_id']);
        $this->assertSame(100.0, $payload['lines'][1]['credit']);
        $this->assertSame(1331, $payload['lines'][2]['account_id']);
        $this->assertSame(10.0, $payload['lines'][2]['credit']);
    }

    public function test_purchase_return_document_adapter_builds_delete_payload_from_snapshot(): void
    {
        $vasUtil = Mockery::mock(VasAccountingUtil::class);
        $vasUtil->shouldReceive('getOrCreateBusinessSettings')
            ->once()
            ->andReturn(new VasBusinessSetting([
                'posting_map' => [
                    'inventory' => 156,
                    'accounts_payable' => 331,
                    'vat_input' => 1331,
                ],
            ]));

        $adapter = new PurchaseReturnDocumentAdapter($vasUtil);

        $context = [
            'is_deleted' => true,
            'source_snapshot' => [
                'id' => 30,
                'business_id' => 44,
                'location_id' => 3,
                'contact_id' => 11,
                'transaction_date' => '2026-04-04',
                'ref_no' => 'PR-0030',
                'created_by' => 7,
                'final_total' => 110,
                'tax_amount' => 10,
            ],
        ];

        $payload = $adapter->toVoucherPayload($adapter->loadSourceDocument(30, $context), $context);

        $this->assertSame('purchase_return_reversal', $payload['voucher_type']);
        $this->assertNull($payload['transaction_id']);
        $this->assertSame('Reversed purchase return PR-0030', $payload['description']);
        $this->assertSame(156, $payload['lines'][0]['account_id']);
        $this->assertSame(100.0, $payload['lines'][0]['debit']);
        $this->assertSame(331, $payload['lines'][1]['account_id']);
        $this->assertSame(110.0, $payload['lines'][1]['credit']);
        $this->assertSame(1331, $payload['lines'][2]['account_id']);
        $this->assertSame(10.0, $payload['lines'][2]['debit']);
    }

    public function test_payment_document_adapter_uses_snapshots_for_deleted_purchase_payment(): void
    {
        $vasUtil = Mockery::mock(VasAccountingUtil::class);
        $vasUtil->shouldReceive('getOrCreateBusinessSettings')
            ->once()
            ->andReturn(new VasBusinessSetting([
                'posting_map' => [
                    'cash' => 111,
                    'bank' => 112,
                    'accounts_payable' => 331,
                    'accounts_receivable' => 131,
                ],
            ]));

        $adapter = new PaymentDocumentAdapter($vasUtil);

        $context = [
            'is_deleted' => true,
            'source_snapshot' => [
                'id' => 401,
                'transaction_id' => 20,
                'amount' => 50,
                'method' => 'cash',
                'paid_on' => '2026-04-04',
                'payment_ref_no' => 'PAY-0401',
                'created_by' => 7,
            ],
            'transaction_snapshot' => [
                'id' => 20,
                'business_id' => 44,
                'location_id' => 3,
                'contact_id' => 11,
                'transaction_date' => '2026-04-04',
                'type' => 'purchase',
                'created_by' => 7,
            ],
        ];

        $payload = $adapter->toVoucherPayload($adapter->loadSourceDocument(401, $context), $context);

        $this->assertSame('payment_reversal', $payload['voucher_type']);
        $this->assertSame('Reversed cash payment PAY-0401', $payload['description']);
        $this->assertSame(111, $payload['lines'][0]['account_id']);
        $this->assertSame(50.0, $payload['lines'][0]['debit']);
        $this->assertSame(331, $payload['lines'][1]['account_id']);
        $this->assertSame(50.0, $payload['lines'][1]['credit']);
    }
}
