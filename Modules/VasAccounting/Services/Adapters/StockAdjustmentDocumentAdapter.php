<?php

namespace Modules\VasAccounting\Services\Adapters;

use App\Transaction;
use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Entities\VasVoucher;

class StockAdjustmentDocumentAdapter extends AbstractSourceDocumentAdapter
{
    public function loadSourceDocument(int $sourceId, array $context = [])
    {
        $transaction = Transaction::find($sourceId);
        if ($transaction) {
            return $transaction;
        }

        $isDeleted = (bool) ($context['is_deleted'] ?? false);
        if (! $isDeleted) {
            return Transaction::findOrFail($sourceId);
        }

        $snapshot = (array) ($context['source_snapshot'] ?? []);
        if (! empty($snapshot)) {
            return (object) array_replace(['id' => $sourceId], $snapshot);
        }

        // Keep a minimal placeholder so downstream payload builders can no-op safely
        // instead of crashing hard on deleted documents.
        return (object) ['id' => $sourceId];
    }

    public function toVoucherPayload($sourceDocument, array $context = []): array
    {
        $transaction = $sourceDocument;
        $snapshot = (array) ($context['source_snapshot'] ?? []);
        $businessId = (int) ($transaction->business_id ?? ($snapshot['business_id'] ?? 0));
        $sourceId = (int) ($transaction->id ?? ($snapshot['id'] ?? 0));
        $isDeleted = (bool) ($context['is_deleted'] ?? false);

        if ($businessId <= 0 || $sourceId <= 0) {
            throw new \RuntimeException('Stock adjustment payload is missing required source identifiers.');
        }

        $settings = $this->settings($businessId);
        $amount = $this->resolveAmount($transaction, $snapshot, $sourceId, $businessId, $isDeleted);

        $postingDate = $transaction->transaction_date
            ?? ($snapshot['transaction_date'] ?? now()->toDateString());
        $reference = $transaction->ref_no
            ?? ($snapshot['ref_no'] ?? null);
        $createdBy = (int) ($transaction->created_by ?? ($snapshot['created_by'] ?? 0));
        $locationId = (int) ($transaction->location_id ?? ($snapshot['location_id'] ?? 0)) ?: null;

        $lines = [
            $this->line($this->postingMapAccount($settings, 'stock_adjustment'), 'Stock adjustment expense', $amount, 0),
            $this->line($this->postingMapAccount($settings, 'inventory'), 'Inventory adjustment', 0, $amount),
        ];

        return $this->payload([
            'business_id' => $businessId,
            'voucher_type' => 'stock_adjustment',
            'sequence_key' => 'general_journal',
            'source_type' => 'stock_adjustment',
            'source_id' => $sourceId,
            'transaction_id' => $sourceId,
            'business_location_id' => $locationId,
            'posting_date' => $postingDate,
            'document_date' => $postingDate,
            'description' => 'Auto-posted stock adjustment ' . ($reference ?: $sourceId),
            'reference' => $reference,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => $createdBy,
        ], $lines);
    }

    protected function resolveAmount($transaction, array $snapshot, int $sourceId, int $businessId, bool $isDeleted): float
    {
        $amount = $this->money(
            DB::table('stock_adjustment_lines')
                ->where('transaction_id', $sourceId)
                ->selectRaw('SUM(quantity * unit_price) as total_value')
                ->value('total_value')
        );

        if ($amount > 0) {
            return $amount;
        }

        // In delete flows, stock_adjustment_lines may already be removed.
        $snapshotAmount = $this->money($snapshot['final_total'] ?? ($transaction->final_total ?? 0));
        if ($snapshotAmount > 0) {
            return $snapshotAmount;
        }

        if ($isDeleted) {
            $latestVoucherAmount = $this->money(
                VasVoucher::query()
                    ->where('business_id', $businessId)
                    ->where('source_type', 'stock_adjustment')
                    ->where('source_id', $sourceId)
                    ->orderByDesc('version_no')
                    ->value('total_debit')
            );

            if ($latestVoucherAmount > 0) {
                return $latestVoucherAmount;
            }
        }

        return $amount;
    }
}
