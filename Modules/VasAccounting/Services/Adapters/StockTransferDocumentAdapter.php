<?php

namespace Modules\VasAccounting\Services\Adapters;

use App\Transaction;

class StockTransferDocumentAdapter extends AbstractSourceDocumentAdapter
{
    public function loadSourceDocument(int $sourceId, array $context = [])
    {
        return Transaction::findOrFail($sourceId);
    }

    public function toVoucherPayload($sourceDocument, array $context = []): array
    {
        $transaction = $sourceDocument;
        $settings = $this->settings((int) $transaction->business_id);
        $amount = $this->transactionTotalFromPurchaseLines((int) $transaction->id);

        $lines = [
            $this->line($this->postingMapAccount($settings, 'stock_transfer'), 'Inventory in transit or transfer clearing', $amount, 0),
            $this->line($this->postingMapAccount($settings, 'inventory'), 'Inventory issued for transfer', 0, $amount),
        ];

        return $this->payload([
            'business_id' => (int) $transaction->business_id,
            'voucher_type' => 'stock_transfer',
            'sequence_key' => 'general_journal',
            'source_type' => 'stock_transfer',
            'source_id' => (int) $transaction->id,
            'transaction_id' => (int) $transaction->id,
            'business_location_id' => (int) ($transaction->location_id ?? 0) ?: null,
            'posting_date' => $transaction->transaction_date,
            'document_date' => $transaction->transaction_date,
            'description' => 'Auto-posted stock transfer ' . ($transaction->ref_no ?: $transaction->id),
            'reference' => $transaction->ref_no,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => (int) ($transaction->created_by ?? 0),
        ], $lines);
    }
}
