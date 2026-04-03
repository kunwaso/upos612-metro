<?php

namespace Modules\StorageManager\Services;

use Modules\StorageManager\Contracts\WarehousePostingAdapterInterface;
use Modules\StorageManager\Entities\StorageDocument;
use RuntimeException;

class WarehouseSyncService
{
    public function __construct(
        protected ReconciliationService $reconciliationService
    ) {
    }

    public function syncDocument(StorageDocument $document, int $userId): array
    {
        $adapter = $this->resolvePostingAdapter();
        if (! $adapter->isAvailable((int) $document->business_id)) {
            $document->forceFill(['sync_status' => 'not_required'])->save();

            return ['required' => false, 'status' => 'not_required'];
        }

        $document->forceFill(['sync_status' => 'pending_sync'])->save();
        $adapter->sync($document->fresh('lines'), $userId);

        return $this->reconcileDocument($document->fresh('lines'));
    }

    public function reconcileDocument(StorageDocument $document): array
    {
        $adapter = $this->resolvePostingAdapter();
        $adapterResult = $adapter->reconcile($document->fresh('lines'));
        $locationResult = $this->reconciliationService->reconcileLocation(
            (int) $document->business_id,
            (int) $document->location_id
        );

        $hasBlockers = (bool) ($adapterResult['has_errors'] ?? false) || (bool) ($locationResult['has_blockers'] ?? false);
        $status = $hasBlockers ? 'reconcile_error' : ($adapterResult['status'] ?? 'not_required');

        $document->forceFill(['sync_status' => $status])->save();

        return [
            'sync' => $adapterResult,
            'location' => $locationResult,
            'status' => $status,
            'has_blockers' => $hasBlockers,
        ];
    }

    protected function resolvePostingAdapter(): WarehousePostingAdapterInterface
    {
        $adapterClass = config('storagemanager.posting_adapters.vas');
        if (! is_string($adapterClass) || ! class_exists($adapterClass)) {
            throw new RuntimeException('No warehouse posting adapter configured.');
        }

        $adapter = app($adapterClass);
        if (! $adapter instanceof WarehousePostingAdapterInterface) {
            throw new RuntimeException("Configured warehouse posting adapter [{$adapterClass}] is invalid.");
        }

        return $adapter;
    }
}
