<?php

namespace Modules\StorageManager\Services\Adapters;

use Illuminate\Support\Facades\Schema;
use Modules\StorageManager\Contracts\WarehousePostingAdapterInterface;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLink;
use Modules\StorageManager\Entities\StorageSyncLog;
use Modules\VasAccounting\Entities\VasInventoryDocument;
use Modules\VasAccounting\Entities\VasWarehouse;
use Modules\VasAccounting\Services\VasWarehouseDocumentService;
use RuntimeException;

class VasWarehousePostingAdapter implements WarehousePostingAdapterInterface
{
    public function __construct(
        protected VasWarehouseDocumentService $warehouseDocumentService
    ) {
    }

    public function adapterKey(): string
    {
        return 'vas';
    }

    public function isAvailable(int $businessId): bool
    {
        return Schema::hasTable('vas_inventory_documents') && Schema::hasTable('vas_warehouses');
    }

    public function sync(StorageDocument $document, int $userId): array
    {
        if (! $this->isAvailable((int) $document->business_id)) {
            return ['required' => false, 'status' => 'not_required'];
        }

        if (! in_array($document->document_type, ['receipt', 'transfer_dispatch', 'transfer_receipt', 'damage', 'cycle_count'], true)) {
            return ['required' => false, 'status' => 'not_required'];
        }

        try {
            $existing = $document->vasInventoryDocument;
            if (! $existing && ! empty($document->vas_inventory_document_id)) {
                $existing = VasInventoryDocument::query()->find($document->vas_inventory_document_id);
            }

            if (! $existing) {
                $payload = $this->payloadForDocument($document);
                $existing = $this->warehouseDocumentService->createDocument(
                    (int) $document->business_id,
                    $payload,
                    $userId
                );
            }

            if (in_array($document->status, ['closed', 'completed'], true) && $existing->status !== 'posted') {
                $existing = $this->warehouseDocumentService->postDocument($existing, $userId);
            }

            $link = StorageDocumentLink::query()->updateOrCreate(
                [
                    'business_id' => $document->business_id,
                    'document_id' => $document->id,
                    'linked_system' => $this->adapterKey(),
                    'link_role' => 'posting',
                ],
                [
                    'linked_type' => 'vas_inventory_document',
                    'linked_id' => $existing->id,
                    'linked_ref' => $existing->document_no,
                    'sync_status' => $existing->status === 'posted' ? 'posted' : 'synced_unposted',
                    'synced_at' => now(),
                    'meta' => ['document_type' => $existing->document_type],
                ]
            );

            $document->forceFill([
                'vas_inventory_document_id' => $existing->id,
                'sync_status' => $link->sync_status,
            ])->save();

            StorageSyncLog::query()->create([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'linked_system' => $this->adapterKey(),
                'action' => 'sync',
                'status' => $link->sync_status,
                'message' => 'Storage document synced to VAS inventory document ' . $existing->document_no,
                'payload' => ['vas_inventory_document_id' => $existing->id],
                'created_by' => $userId,
            ]);

            return [
                'required' => true,
                'status' => $link->sync_status,
                'linked_id' => $existing->id,
                'linked_ref' => $existing->document_no,
            ];
        } catch (\Throwable $exception) {
            $document->forceFill(['sync_status' => 'sync_error'])->save();

            StorageSyncLog::query()->create([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'linked_system' => $this->adapterKey(),
                'action' => 'sync',
                'status' => 'sync_error',
                'message' => $exception->getMessage(),
                'payload' => ['document_type' => $document->document_type],
                'created_by' => $userId,
            ]);

            throw $exception;
        }
    }

    public function reconcile(StorageDocument $document): array
    {
        if (! $this->isAvailable((int) $document->business_id) || ! $document->vas_inventory_document_id) {
            return ['required' => false, 'status' => 'not_required', 'has_errors' => false];
        }

        $vasDocument = VasInventoryDocument::query()
            ->with('lines')
            ->find($document->vas_inventory_document_id);

        if (! $vasDocument) {
            return ['required' => true, 'status' => 'reconcile_error', 'has_errors' => true, 'message' => 'Linked VAS document not found.'];
        }

        $storageQty = (float) $document->lines()->sum('executed_qty');
        $fallbackQty = (float) $document->lines()->sum('expected_qty');
        $documentQty = $storageQty > 0 ? $storageQty : $fallbackQty;
        $vasQty = (float) $vasDocument->lines->sum('quantity');
        $delta = round($documentQty - $vasQty, 4);
        $status = $delta === 0.0 ? ($vasDocument->status === 'posted' ? 'posted' : 'synced_unposted') : 'reconcile_error';

        return [
            'required' => true,
            'status' => $status,
            'has_errors' => $status === 'reconcile_error',
            'document_qty' => $documentQty,
            'vas_qty' => $vasQty,
            'delta' => $delta,
            'vas_status' => $vasDocument->status,
        ];
    }

    protected function payloadForDocument(StorageDocument $document): array
    {
        $warehouseId = $this->resolveWarehouseId((int) $document->business_id, (int) $document->location_id);
        $destinationWarehouseId = $this->resolveWarehouseId(
            (int) $document->business_id,
            (int) data_get($document->meta, 'destination_location_id', 0)
        );

        if (in_array($document->document_type, ['receipt', 'transfer_dispatch', 'transfer_receipt', 'damage', 'cycle_count'], true) && ! $warehouseId) {
            throw new RuntimeException('No VAS warehouse is mapped to this business location.');
        }

        $documentType = match ($document->document_type) {
            'receipt' => 'receipt',
            'transfer_dispatch', 'transfer_receipt' => 'transfer',
            'damage' => 'issue',
            default => 'adjustment',
        };

        $lines = $document->lines->map(function ($line) use ($document) {
            $qty = (float) ($line->executed_qty ?: $line->expected_qty);
            $direction = 'decrease';

            if (in_array($document->document_type, ['receipt', 'transfer_receipt'], true)) {
                $direction = 'increase';
            } elseif ($document->document_type === 'cycle_count') {
                $direction = (string) data_get($line->meta, 'count_direction', ($qty >= 0 ? 'increase' : 'decrease'));
                $qty = abs($qty);
            }

            return [
                'product_id' => (int) $line->product_id,
                'variation_id' => $line->variation_id ? (int) $line->variation_id : null,
                'quantity' => $qty,
                'unit_cost' => (float) $line->unit_cost,
                'amount' => $qty * (float) $line->unit_cost,
                'direction' => $direction,
            ];
        })->filter(fn (array $line) => $line['quantity'] > 0)->values()->all();

        return [
            'document_type' => $documentType,
            'status' => in_array($document->status, ['closed', 'completed'], true) ? 'approved' : 'draft',
            'posting_date' => optional($document->completed_at ?? $document->closed_at ?? $document->created_at)->toDateString(),
            'document_date' => optional($document->created_at)->toDateString(),
            'business_location_id' => (int) $document->location_id,
            'warehouse_id' => $warehouseId,
            'destination_warehouse_id' => $destinationWarehouseId,
            'reference' => $document->document_no,
            'external_reference' => $document->source_ref,
            'description' => $document->notes ?: ('Storage document ' . $document->document_no),
            'lines' => $lines,
        ];
    }

    protected function resolveWarehouseId(int $businessId, int $locationId): ?int
    {
        if ($locationId <= 0 || ! Schema::hasTable('vas_warehouses')) {
            return null;
        }

        return VasWarehouse::query()
            ->where('business_id', $businessId)
            ->where('business_location_id', $locationId)
            ->value('id');
    }
}
