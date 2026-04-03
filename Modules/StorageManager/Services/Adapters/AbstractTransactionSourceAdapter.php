<?php

namespace Modules\StorageManager\Services\Adapters;

use App\Transaction;
use Modules\StorageManager\Contracts\SourceDocumentAdapterInterface;

abstract class AbstractTransactionSourceAdapter implements SourceDocumentAdapterInterface
{
    abstract protected function type(): string;

    public function load(int $businessId, int $sourceId)
    {
        return Transaction::query()
            ->where('business_id', $businessId)
            ->where('id', $sourceId)
            ->where('type', $this->type())
            ->firstOrFail();
    }

    public function summarize($sourceDocument): array
    {
        return [
            'source_type' => $this->supportedSourceType(),
            'source_id' => (int) $sourceDocument->id,
            'reference' => (string) ($sourceDocument->ref_no ?: $sourceDocument->invoice_no ?: $sourceDocument->id),
            'status' => (string) ($sourceDocument->status ?? 'unknown'),
            'location_id' => (int) ($sourceDocument->location_id ?? 0),
            'transaction_date' => $sourceDocument->transaction_date,
        ];
    }
}
