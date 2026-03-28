<?php

namespace Modules\VasAccounting\Services\Adapters;

use App\Transaction;
use Illuminate\Support\Facades\DB;

class StockAdjustmentDocumentAdapter extends AbstractSourceDocumentAdapter
{
    public function loadSourceDocument(int $sourceId, array $context = [])
    {
        return Transaction::findOrFail($sourceId);
    }

    public function toVoucherPayload($sourceDocument, array $context = []): array
    {
        $transaction = $sourceDocument;
        $settings = $this->settings((int) $transaction->business_id);
        $amount = $this->money(
            DB::table('stock_adjustment_lines')
                ->where('transaction_id', $transaction->id)
                ->selectRaw('SUM(quantity * unit_price) as total_value')
                ->value('total_value')
        );

        $lines = [
            $this->line($this->postingMapAccount($settings, 'stock_adjustment'), 'Stock adjustment expense', $amount, 0),
            $this->line($this->postingMapAccount($settings, 'inventory'), 'Inventory adjustment', 0, $amount),
        ];

        return $this->payload([
            'business_id' => (int) $transaction->business_id,
            'voucher_type' => 'stock_adjustment',
            'sequence_key' => 'general_journal',
            'source_type' => 'stock_adjustment',
            'source_id' => (int) $transaction->id,
            'transaction_id' => (int) $transaction->id,
            'business_location_id' => (int) ($transaction->location_id ?? 0) ?: null,
            'posting_date' => $transaction->transaction_date,
            'document_date' => $transaction->transaction_date,
            'description' => 'Auto-posted stock adjustment ' . ($transaction->ref_no ?: $transaction->id),
            'reference' => $transaction->ref_no,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => (int) ($transaction->created_by ?? 0),
        ], $lines);
    }
}
