<?php

namespace Modules\StorageManager\Services\Adapters;

use App\Transaction;

class StockTransferSourceAdapter extends AbstractTransactionSourceAdapter
{
    public function supportedSourceType(): string
    {
        return 'stock_transfer';
    }

    protected function type(): string
    {
        return 'sell_transfer';
    }

    public function load(int $businessId, int $sourceId)
    {
        return Transaction::query()
            ->with([
                'location',
                'sell_lines.product',
                'sell_lines.variations',
            ])
            ->where('business_id', $businessId)
            ->where('id', $sourceId)
            ->where('type', $this->type())
            ->firstOrFail();
    }

    public function summarize($sourceDocument): array
    {
        $purchaseTransfer = Transaction::query()
            ->with('location')
            ->where('business_id', $sourceDocument->business_id)
            ->where('transfer_parent_id', $sourceDocument->id)
            ->where('type', 'purchase_transfer')
            ->first();

        return array_merge(parent::summarize($sourceDocument), [
            'source_location_name' => (string) optional($sourceDocument->location)->name,
            'destination_location_id' => (int) ($purchaseTransfer->location_id ?? 0),
            'destination_location_name' => (string) optional($purchaseTransfer?->location)->name,
            'line_count' => (int) $sourceDocument->sell_lines->count(),
            'expected_qty' => round((float) $sourceDocument->sell_lines->sum('quantity'), 4),
        ]);
    }
}
