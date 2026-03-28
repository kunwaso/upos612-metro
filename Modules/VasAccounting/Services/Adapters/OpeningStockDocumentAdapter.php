<?php

namespace Modules\VasAccounting\Services\Adapters;

use App\Transaction;

class OpeningStockDocumentAdapter extends AbstractSourceDocumentAdapter
{
    public function loadSourceDocument(int $sourceId, array $context = [])
    {
        return Transaction::findOrFail($sourceId);
    }

    public function toVoucherPayload($sourceDocument, array $context = []): array
    {
        $transaction = $sourceDocument;
        $amount = $this->transactionTotalFromPurchaseLines((int) $transaction->id);

        $lines = [
            $this->line($this->accountIdByCode((int) $transaction->business_id, '156'), 'Opening inventory balance', $amount, 0),
            $this->line($this->accountIdByCode((int) $transaction->business_id, '421'), 'Opening retained earnings / balancing equity', 0, $amount),
        ];

        return $this->payload([
            'business_id' => (int) $transaction->business_id,
            'voucher_type' => 'opening_stock',
            'sequence_key' => 'general_journal',
            'source_type' => 'opening_stock',
            'source_id' => (int) $transaction->id,
            'transaction_id' => (int) $transaction->id,
            'business_location_id' => (int) ($transaction->location_id ?? 0) ?: null,
            'posting_date' => $transaction->transaction_date,
            'document_date' => $transaction->transaction_date,
            'description' => 'Auto-posted opening stock ' . ($transaction->ref_no ?: $transaction->id),
            'reference' => $transaction->ref_no,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => (int) ($transaction->created_by ?? 0),
        ], $lines);
    }
}
