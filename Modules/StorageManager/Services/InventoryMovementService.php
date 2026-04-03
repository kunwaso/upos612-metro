<?php

namespace Modules\StorageManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\StorageManager\Entities\StorageInventoryMovement;
use Modules\StorageManager\Entities\StorageSlot;
use Modules\StorageManager\Entities\StorageSlotStock;
use RuntimeException;

class InventoryMovementService
{
    public function applyMovement(array $payload): StorageInventoryMovement
    {
        return DB::transaction(function () use ($payload) {
            $businessId = (int) ($payload['business_id'] ?? 0);
            $quantity = round((float) ($payload['quantity'] ?? 0), 4);
            $idempotencyKey = trim((string) ($payload['idempotency_key'] ?? ''));

            if ($businessId <= 0) {
                throw new RuntimeException('business_id is required for inventory movements.');
            }

            if ($quantity <= 0) {
                throw new RuntimeException('quantity must be greater than zero for inventory movements.');
            }

            if ($idempotencyKey !== '') {
                $existing = StorageInventoryMovement::query()
                    ->where('business_id', $businessId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $fromStatus = $payload['from_status'] ?? null;
            $toStatus = $payload['to_status'] ?? null;
            $fromSlotId = ! empty($payload['from_slot_id']) ? (int) $payload['from_slot_id'] : null;
            $toSlotId = ! empty($payload['to_slot_id']) ? (int) $payload['to_slot_id'] : null;
            $fromAreaId = ! empty($payload['from_area_id']) ? (int) $payload['from_area_id'] : $this->resolveAreaIdFromSlot($fromSlotId);
            $toAreaId = ! empty($payload['to_area_id']) ? (int) $payload['to_area_id'] : $this->resolveAreaIdFromSlot($toSlotId);
            $locationId = (int) ($payload['location_id'] ?? $this->resolveLocationId($fromSlotId, $toSlotId));

            if ($fromSlotId && $fromStatus) {
                $this->adjustSnapshot($businessId, $locationId, $fromAreaId, $fromSlotId, $fromStatus, $payload, -1 * $quantity);
            }

            if ($toSlotId && $toStatus) {
                $this->adjustSnapshot($businessId, $locationId, $toAreaId, $toSlotId, $toStatus, $payload, $quantity);
            }

            return StorageInventoryMovement::query()->create([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'document_id' => $payload['document_id'] ?? null,
                'document_line_id' => $payload['document_line_id'] ?? null,
                'task_id' => $payload['task_id'] ?? null,
                'source_type' => $payload['source_type'] ?? null,
                'source_id' => $payload['source_id'] ?? null,
                'source_line_id' => $payload['source_line_id'] ?? null,
                'movement_type' => $payload['movement_type'] ?? 'adjustment',
                'direction' => $payload['direction'] ?? $this->resolveDirection($fromSlotId, $toSlotId),
                'product_id' => (int) $payload['product_id'],
                'variation_id' => ! empty($payload['variation_id']) ? (int) $payload['variation_id'] : null,
                'from_area_id' => $fromAreaId,
                'to_area_id' => $toAreaId,
                'from_slot_id' => $fromSlotId,
                'to_slot_id' => $toSlotId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'lot_number' => (string) ($payload['lot_number'] ?? ''),
                'expiry_date' => $payload['expiry_date'] ?? null,
                'quantity' => $quantity,
                'unit_cost' => round((float) ($payload['unit_cost'] ?? 0), 4),
                'reason_code' => $payload['reason_code'] ?? null,
                'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
                'moved_at' => $payload['moved_at'] ?? now(),
                'created_by' => $payload['created_by'] ?? null,
                'meta' => $payload['meta'] ?? null,
            ]);
        });
    }

    public function buildStockKey(array $attributes): string
    {
        return implode('|', [
            (int) $attributes['location_id'],
            (int) $attributes['slot_id'],
            (int) $attributes['product_id'],
            (int) ($attributes['variation_id'] ?? 0),
            (string) ($attributes['inventory_status'] ?? 'available'),
            (string) ($attributes['lot_number'] ?? ''),
            (string) ($attributes['expiry_date'] ?? ''),
        ]);
    }

    protected function adjustSnapshot(
        int $businessId,
        int $locationId,
        ?int $areaId,
        int $slotId,
        string $inventoryStatus,
        array $payload,
        float $delta
    ): StorageSlotStock {
        $stockKey = $this->buildStockKey([
            'location_id' => $locationId,
            'slot_id' => $slotId,
            'product_id' => (int) $payload['product_id'],
            'variation_id' => ! empty($payload['variation_id']) ? (int) $payload['variation_id'] : null,
            'inventory_status' => $inventoryStatus,
            'lot_number' => (string) ($payload['lot_number'] ?? ''),
            'expiry_date' => $payload['expiry_date'] ?? '',
        ]);

        $snapshot = StorageSlotStock::query()
            ->where('business_id', $businessId)
            ->where('stock_key', $stockKey)
            ->lockForUpdate()
            ->first();

        if (! $snapshot) {
            $snapshot = new StorageSlotStock([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'area_id' => $areaId,
                'slot_id' => $slotId,
                'product_id' => (int) $payload['product_id'],
                'variation_id' => ! empty($payload['variation_id']) ? (int) $payload['variation_id'] : null,
                'stock_key' => $stockKey,
                'lot_number' => (string) ($payload['lot_number'] ?? ''),
                'expiry_date' => $payload['expiry_date'] ?? null,
                'inventory_status' => $inventoryStatus,
                'qty_on_hand' => 0,
                'qty_reserved' => 0,
                'qty_inbound' => 0,
                'qty_outbound' => 0,
                'qty_count_pending' => 0,
            ]);
        }

        $newQty = round((float) $snapshot->qty_on_hand + $delta, 4);
        if ($newQty < 0) {
            throw new RuntimeException("Inventory movement would make slot stock negative for stock key [{$stockKey}].");
        }

        $snapshot->fill([
            'location_id' => $locationId,
            'area_id' => $areaId,
            'qty_on_hand' => $newQty,
            'last_movement_at' => now(),
            'meta' => array_merge((array) $snapshot->meta, (array) ($payload['stock_meta'] ?? [])),
        ]);

        $this->syncBucketQuantities($snapshot);
        $snapshot->save();

        return $snapshot;
    }

    protected function syncBucketQuantities(StorageSlotStock $snapshot): void
    {
        $snapshot->qty_reserved = $snapshot->inventory_status === 'reserved' ? $snapshot->qty_on_hand : 0;
        $snapshot->qty_inbound = in_array($snapshot->inventory_status, ['receiving', 'staged_in'], true) ? $snapshot->qty_on_hand : 0;
        $snapshot->qty_outbound = in_array($snapshot->inventory_status, ['picked', 'packed', 'staged_out'], true) ? $snapshot->qty_on_hand : 0;
        $snapshot->qty_count_pending = $snapshot->inventory_status === 'count_hold' ? $snapshot->qty_on_hand : 0;
    }

    protected function resolveAreaIdFromSlot(?int $slotId): ?int
    {
        if (! $slotId) {
            return null;
        }

        return StorageSlot::query()->where('id', $slotId)->value('area_id');
    }

    protected function resolveLocationId(?int $fromSlotId, ?int $toSlotId): int
    {
        $slotId = $toSlotId ?: $fromSlotId;
        if (! $slotId) {
            return 0;
        }

        return (int) StorageSlot::query()->where('id', $slotId)->value('location_id');
    }

    protected function resolveDirection(?int $fromSlotId, ?int $toSlotId): string
    {
        if ($fromSlotId && $toSlotId) {
            return 'move';
        }

        return $toSlotId ? 'in' : 'out';
    }
}
