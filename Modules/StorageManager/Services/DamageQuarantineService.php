<?php

namespace Modules\StorageManager\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\StorageManager\Entities\StorageApprovalRequest;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLine;
use Modules\StorageManager\Entities\StorageDocumentLink;
use Modules\StorageManager\Entities\StorageLocationSetting;
use Modules\StorageManager\Entities\StorageSlot;
use Modules\StorageManager\Entities\StorageSlotStock;
use RuntimeException;

class DamageQuarantineService
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService,
        protected PutawayService $putawayService,
        protected ReconciliationService $reconciliationService,
        protected WarehouseSyncService $warehouseSyncService,
        protected StockAdjustmentBridgeService $stockAdjustmentBridgeService
    ) {
    }

    public function boardForLocation(int $businessId, ?int $locationId = null): array
    {
        $settings = StorageLocationSetting::query()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->where('execution_mode', '!=', 'off')
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->get()
            ->keyBy('location_id');

        $enabledLocationIds = $settings->keys()->map(fn ($id) => (int) $id)->values()->all();
        if (empty($enabledLocationIds)) {
            return [
                'boardSummary' => [
                    'enabled_location_count' => 0,
                    'open_documents' => 0,
                    'pending_approvals' => 0,
                    'quarantine_qty' => 0,
                ],
                'documentRows' => collect(),
                'approvalRows' => collect(),
                'quarantineSlotOptions' => [],
                'availableBuckets' => collect(),
            ];
        }

        $documents = StorageDocument::query()
            ->with(['lines.product', 'lines.variation'])
            ->where('business_id', $businessId)
            ->where('document_type', 'damage')
            ->whereIn('location_id', $enabledLocationIds)
            ->orderByDesc('id')
            ->get();

        $approvals = StorageApprovalRequest::query()
            ->where('business_id', $businessId)
            ->where('approval_type', 'damage_disposal')
            ->whereIn('location_id', $enabledLocationIds)
            ->orderByDesc('id')
            ->get();

        $quarantineQty = (float) StorageSlotStock::query()
            ->where('business_id', $businessId)
            ->whereIn('location_id', $enabledLocationIds)
            ->where('inventory_status', 'quarantine')
            ->sum('qty_on_hand');

        $selectedLocationId = $locationId ?: (int) $settings->keys()->first();

        return [
            'boardSummary' => [
                'enabled_location_count' => count($enabledLocationIds),
                'open_documents' => (int) $documents->whereNotIn('status', ['closed', 'completed', 'cancelled'])->count(),
                'pending_approvals' => (int) $approvals->where('status', 'pending')->count(),
                'quarantine_qty' => round($quarantineQty, 4),
            ],
            'documentRows' => $documents->map(fn (StorageDocument $document) => [
                'id' => (int) $document->id,
                'document_no' => (string) $document->document_no,
                'source_ref' => (string) ($document->source_ref ?: '—'),
                'location_id' => (int) $document->location_id,
                'location_name' => (string) data_get($document->meta, 'location_name', '#' . $document->location_id),
                'status' => (string) $document->status,
                'workflow_state' => (string) $document->workflow_state,
                'approval_status' => (string) $document->approval_status,
                'sync_status' => (string) $document->sync_status,
                'line_count' => (int) $document->lines->count(),
                'reported_qty' => round((float) $document->lines->sum('executed_qty'), 4),
                'reported_by' => (int) ($document->created_by ?: 0),
            ])->values(),
            'approvalRows' => $approvals->map(fn (StorageApprovalRequest $approval) => [
                'id' => (int) $approval->id,
                'document_id' => (int) ($approval->document_id ?: 0),
                'location_id' => (int) ($approval->location_id ?: 0),
                'approval_type' => (string) $approval->approval_type,
                'status' => (string) $approval->status,
                'threshold_value' => (float) ($approval->threshold_value ?: 0),
                'notes' => (string) ($approval->notes ?: ''),
            ])->values(),
            'quarantineSlotOptions' => $this->quarantineSlotOptions($businessId, $selectedLocationId),
            'availableBuckets' => $this->reportableBuckets($businessId, $selectedLocationId),
        ];
    }

    public function getWorkbench(int $businessId, int $documentId): array
    {
        $document = StorageDocument::query()
            ->with(['lines.product', 'lines.variation', 'lines.fromArea', 'lines.toArea', 'lines.fromSlot', 'lines.toSlot'])
            ->where('business_id', $businessId)
            ->where('document_type', 'damage')
            ->findOrFail($documentId);

        $approvals = StorageApprovalRequest::query()
            ->where('business_id', $businessId)
            ->where('document_id', $document->id)
            ->orderByDesc('id')
            ->get();

        return [
            'document' => $document,
            'lineRows' => $document->lines->map(fn (StorageDocumentLine $line) => [
                'id' => (int) $line->id,
                'product_label' => (string) optional($line->product)->name,
                'sku' => (string) (optional($line->variation)->sub_sku ?: optional($line->product)->sku ?: '—'),
                'qty' => (float) ($line->executed_qty ?: $line->expected_qty),
                'from_slot_label' => $this->slotLabel($line->fromSlot),
                'quarantine_slot_label' => $this->slotLabel($line->toSlot),
                'lot_number' => (string) ($line->lot_number ?: '—'),
                'expiry_date' => optional($line->expiry_date)->toDateString(),
                'reason_code' => (string) ($line->reason_code ?: '—'),
                'result_status' => (string) $line->result_status,
            ])->values(),
            'approvals' => $approvals,
            'releaseSlotOptions' => $this->putawayService->slotOptionsForLocation($businessId, (int) $document->location_id),
        ];
    }

    public function reportDamage(int $businessId, array $payload, int $userId): StorageDocument
    {
        $locationId = (int) $payload['location_id'];
        $sourceSlotId = (int) $payload['source_slot_id'];
        $quarantineSlotId = (int) $payload['quarantine_slot_id'];
        $quantity = round((float) $payload['quantity'], 4);
        $productId = (int) $payload['product_id'];
        $variationId = ! empty($payload['variation_id']) ? (int) $payload['variation_id'] : null;
        $inventoryStatus = (string) ($payload['inventory_status'] ?? 'available');

        if ($quantity <= 0) {
            throw new RuntimeException('Damage quantity must be greater than zero.');
        }

        $settings = $this->locationSettingForLocation($businessId, $locationId);
        $sourceSlot = $this->validatedSlot($businessId, $locationId, $sourceSlotId);
        $quarantineSlot = $this->validatedSlot($businessId, $locationId, $quarantineSlotId);
        $bucket = $this->sourceBucket(
            $businessId,
            $locationId,
            $sourceSlotId,
            $productId,
            $variationId,
            $inventoryStatus,
            $quantity,
            (string) ($payload['lot_number'] ?? ''),
            $payload['expiry_date'] ?? null
        );

        return DB::transaction(function () use ($businessId, $payload, $userId, $locationId, $sourceSlot, $quarantineSlot, $bucket, $quantity, $settings, $variationId, $inventoryStatus) {
            $document = new StorageDocument([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'area_id' => $quarantineSlot->area_id,
                'document_no' => 'TMP-DMG-' . uniqid(),
                'document_type' => 'damage',
                'source_type' => 'damage_report',
                'status' => 'open',
                'workflow_state' => 'reported',
                'execution_mode' => $settings->execution_mode,
                'sync_status' => 'not_required',
                'approval_status' => 'pending',
                'requested_by' => $userId,
                'created_by' => $userId,
                'notes' => trim((string) ($payload['notes'] ?? '')) ?: null,
                'meta' => [
                    'location_name' => optional($settings->location)->name,
                    'reported_inventory_status' => $inventoryStatus,
                ],
            ]);
            $document->save();
            $document->forceFill([
                'document_no' => 'DMG-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
            ])->save();

            $line = StorageDocumentLine::query()->create([
                'business_id' => $businessId,
                'document_id' => $document->id,
                'line_no' => 1,
                'product_id' => $bucket->product_id,
                'variation_id' => $variationId,
                'from_area_id' => $sourceSlot->area_id,
                'to_area_id' => $quarantineSlot->area_id,
                'from_slot_id' => $sourceSlot->id,
                'to_slot_id' => $quarantineSlot->id,
                'expected_qty' => $quantity,
                'executed_qty' => $quantity,
                'variance_qty' => 0,
                'unit_cost' => round((float) ($payload['unit_cost'] ?? 0), 4),
                'inventory_status' => 'quarantine',
                'result_status' => 'reported',
                'lot_number' => (string) $bucket->lot_number,
                'expiry_date' => $bucket->expiry_date,
                'reason_code' => (string) ($payload['reason_code'] ?? 'damage_reported'),
                'meta' => [
                    'reported_inventory_status' => $inventoryStatus,
                ],
            ]);

            $this->inventoryMovementService->applyMovement([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'document_id' => $document->id,
                'document_line_id' => $line->id,
                'source_type' => 'damage_report',
                'source_id' => $document->id,
                'movement_type' => 'damage_quarantine',
                'direction' => 'move',
                'product_id' => $line->product_id,
                'variation_id' => $line->variation_id,
                'from_area_id' => $sourceSlot->area_id,
                'to_area_id' => $quarantineSlot->area_id,
                'from_slot_id' => $sourceSlot->id,
                'to_slot_id' => $quarantineSlot->id,
                'from_status' => $inventoryStatus,
                'to_status' => 'quarantine',
                'lot_number' => $line->lot_number,
                'expiry_date' => optional($line->expiry_date)->toDateString(),
                'quantity' => $quantity,
                'unit_cost' => $line->unit_cost,
                'reason_code' => $line->reason_code,
                'idempotency_key' => 'damage-report-' . $document->id . '-line-' . $line->id,
                'created_by' => $userId,
            ]);

            StorageApprovalRequest::query()->create([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'document_id' => $document->id,
                'document_line_id' => $line->id,
                'approval_type' => 'damage_disposal',
                'status' => 'pending',
                'requested_by' => $userId,
                'threshold_value' => $quantity,
                'notes' => $document->notes,
                'payload' => [
                    'document_no' => $document->document_no,
                    'reason_code' => $line->reason_code,
                ],
            ]);

            return $document->fresh(['lines.product', 'lines.variation', 'lines.fromSlot', 'lines.toSlot']);
        });
    }

    public function resolveDocument(int $businessId, StorageDocument $document, array $payload, int $userId): StorageDocument
    {
        if ($document->document_type !== 'damage') {
            throw new RuntimeException('Only damage documents can be resolved here.');
        }

        if (in_array($document->status, ['closed', 'completed', 'cancelled'], true)) {
            throw new RuntimeException('This damage document is already closed.');
        }

        $action = (string) ($payload['resolution_action'] ?? 'dispose');
        if (! in_array($action, ['dispose', 'release'], true)) {
            throw new RuntimeException('Invalid damage resolution action.');
        }

        $document->loadMissing(['lines.fromSlot', 'lines.toSlot']);
        $approval = StorageApprovalRequest::query()
            ->where('business_id', $businessId)
            ->where('document_id', $document->id)
            ->where('approval_type', 'damage_disposal')
            ->latest('id')
            ->first();

        DB::transaction(function () use ($businessId, $document, $payload, $userId, $action, $approval) {
            $adjustmentLines = [];
            $linkedTransaction = null;

            foreach ($document->lines as $line) {
                $quantity = round((float) ($line->executed_qty ?: $line->expected_qty), 4);
                if ($quantity <= 0) {
                    continue;
                }

                if ($action === 'dispose') {
                    $this->assertBucketQuantity(
                        $businessId,
                        (int) $document->location_id,
                        (int) $line->to_slot_id,
                        (int) $line->product_id,
                        $line->variation_id ? (int) $line->variation_id : null,
                        'quarantine',
                        $quantity,
                        (string) $line->lot_number,
                        optional($line->expiry_date)->toDateString()
                    );

                    $adjustmentLines[] = [
                        'product_id' => (int) $line->product_id,
                        'variation_id' => $line->variation_id ? (int) $line->variation_id : null,
                        'quantity' => $quantity,
                        'unit_price' => (float) $line->unit_cost,
                    ];
                }
            }

            if ($action === 'dispose' && ! empty($adjustmentLines)) {
                $linkedTransaction = $this->stockAdjustmentBridgeService->createDecreaseAdjustment(
                    $businessId,
                    (int) $document->location_id,
                    $adjustmentLines,
                    $userId,
                    [
                        'notes' => trim((string) ($payload['resolution_notes'] ?? $document->notes ?? '')),
                        'adjustment_type' => 'abnormal',
                    ]
                );
            }

            foreach ($document->lines as $line) {
                $quantity = round((float) ($line->executed_qty ?: $line->expected_qty), 4);
                if ($quantity <= 0) {
                    continue;
                }

                if ($action === 'dispose') {
                    $this->inventoryMovementService->applyMovement([
                        'business_id' => $businessId,
                        'location_id' => $document->location_id,
                        'document_id' => $document->id,
                        'document_line_id' => $line->id,
                        'source_type' => 'stock_adjustment',
                        'source_id' => $linkedTransaction?->id,
                        'movement_type' => 'damage_disposal',
                        'direction' => 'out',
                        'product_id' => $line->product_id,
                        'variation_id' => $line->variation_id,
                        'from_area_id' => $line->to_area_id,
                        'from_slot_id' => $line->to_slot_id,
                        'from_status' => 'quarantine',
                        'lot_number' => $line->lot_number,
                        'expiry_date' => optional($line->expiry_date)->toDateString(),
                        'quantity' => $quantity,
                        'unit_cost' => $line->unit_cost,
                        'reason_code' => 'damage_disposed',
                        'idempotency_key' => 'damage-dispose-' . $document->id . '-line-' . $line->id,
                        'created_by' => $userId,
                    ]);
                } else {
                    $releaseSlotId = (int) (($payload['lines'][$line->id]['release_slot_id'] ?? 0) ?: $line->from_slot_id);
                    $releaseSlot = $this->validatedSlot($businessId, (int) $document->location_id, $releaseSlotId);

                    $this->inventoryMovementService->applyMovement([
                        'business_id' => $businessId,
                        'location_id' => $document->location_id,
                        'document_id' => $document->id,
                        'document_line_id' => $line->id,
                        'source_type' => 'damage_report',
                        'source_id' => $document->id,
                        'movement_type' => 'damage_release',
                        'direction' => 'move',
                        'product_id' => $line->product_id,
                        'variation_id' => $line->variation_id,
                        'from_area_id' => $line->to_area_id,
                        'to_area_id' => $releaseSlot->area_id,
                        'from_slot_id' => $line->to_slot_id,
                        'to_slot_id' => $releaseSlot->id,
                        'from_status' => 'quarantine',
                        'to_status' => data_get($line->meta, 'reported_inventory_status', 'available'),
                        'lot_number' => $line->lot_number,
                        'expiry_date' => optional($line->expiry_date)->toDateString(),
                        'quantity' => $quantity,
                        'unit_cost' => $line->unit_cost,
                        'reason_code' => 'damage_released',
                        'idempotency_key' => 'damage-release-' . $document->id . '-line-' . $line->id,
                        'created_by' => $userId,
                    ]);

                    $line->forceFill([
                        'to_area_id' => $releaseSlot->area_id,
                        'to_slot_id' => $releaseSlot->id,
                        'inventory_status' => data_get($line->meta, 'reported_inventory_status', 'available'),
                    ])->save();
                }

                $line->forceFill([
                    'result_status' => $action === 'dispose' ? 'disposed' : 'released',
                ])->save();
            }

            if ($linkedTransaction) {
                $document->forceFill([
                    'source_type' => 'stock_adjustment',
                    'source_id' => $linkedTransaction->id,
                    'source_ref' => $linkedTransaction->ref_no,
                    'sync_status' => 'pending_sync',
                ])->save();

                StorageDocumentLink::query()->updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'document_id' => $document->id,
                        'linked_system' => 'source',
                        'link_role' => 'source_document',
                    ],
                    [
                        'linked_type' => 'transaction',
                        'linked_id' => $linkedTransaction->id,
                        'linked_ref' => $linkedTransaction->ref_no,
                        'sync_status' => 'posted',
                        'synced_at' => now(),
                        'meta' => ['transaction_type' => 'stock_adjustment'],
                    ]
                );
            }

            $document->forceFill([
                'status' => 'closed',
                'workflow_state' => $action === 'dispose' ? 'adjusted' : 'released_back',
                'approval_status' => 'approved',
                'approved_by' => $userId,
                'completed_at' => now(),
                'closed_at' => now(),
                'closed_by' => $userId,
                'notes' => trim((string) ($payload['resolution_notes'] ?? $document->notes ?? '')) ?: $document->notes,
            ])->save();

            if ($approval) {
                $approval->forceFill([
                    'status' => 'approved',
                    'approved_by' => $userId,
                    'resolved_at' => now(),
                    'notes' => trim((string) ($payload['resolution_notes'] ?? $approval->notes ?? '')) ?: $approval->notes,
                    'payload' => array_merge((array) $approval->payload, ['resolution_action' => $action]),
                ])->save();
            }
        });

        $document = $document->fresh(['lines.product', 'lines.variation', 'lines.fromSlot', 'lines.toSlot']);
        if ($action === 'dispose') {
            $this->warehouseSyncService->syncDocument($document, $userId);
        } else {
            $this->reconciliationService->reconcileLocation((int) $document->business_id, (int) $document->location_id);
        }

        return $document->fresh(['lines.product', 'lines.variation', 'lines.fromSlot', 'lines.toSlot']);
    }

    protected function sourceBucket(
        int $businessId,
        int $locationId,
        int $slotId,
        int $productId,
        ?int $variationId,
        string $inventoryStatus,
        float $requiredQty,
        string $lotNumber = '',
        ?string $expiryDate = null
    ): StorageSlotStock {
        $query = StorageSlotStock::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('slot_id', $slotId)
            ->where('product_id', $productId)
            ->where('inventory_status', $inventoryStatus)
            ->where('qty_on_hand', '>=', $requiredQty)
            ->when($variationId, fn ($q) => $q->where('variation_id', $variationId), fn ($q) => $q->whereNull('variation_id'));

        if ($lotNumber !== '') {
            $query->where('lot_number', $lotNumber);
        }

        if (! empty($expiryDate)) {
            $query->whereDate('expiry_date', $expiryDate);
        }

        $bucket = $query->orderByDesc('qty_on_hand')->first();
        if (! $bucket) {
            throw new RuntimeException('No matching source bucket has enough stock for this damage report.');
        }

        return $bucket;
    }

    protected function assertBucketQuantity(
        int $businessId,
        int $locationId,
        int $slotId,
        int $productId,
        ?int $variationId,
        string $inventoryStatus,
        float $requiredQty,
        string $lotNumber = '',
        ?string $expiryDate = null
    ): void {
        $this->sourceBucket($businessId, $locationId, $slotId, $productId, $variationId, $inventoryStatus, $requiredQty, $lotNumber, $expiryDate);
    }

    protected function locationSettingForLocation(int $businessId, int $locationId): StorageLocationSetting
    {
        $settings = StorageLocationSetting::query()
            ->with('location')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->first();

        if (! $settings || $settings->execution_mode === 'off') {
            throw new RuntimeException('Warehouse execution is not enabled for this location.');
        }

        return $settings;
    }

    protected function validatedSlot(int $businessId, int $locationId, int $slotId): StorageSlot
    {
        $slot = StorageSlot::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->active()
            ->find($slotId);

        if (! $slot) {
            throw new RuntimeException("Slot [{$slotId}] is not valid for this warehouse location.");
        }

        return $slot;
    }

    protected function reportableBuckets(int $businessId, int $locationId): Collection
    {
        if ($locationId <= 0) {
            return collect();
        }

        return StorageSlotStock::query()
            ->with(['slot', 'product', 'variation'])
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('inventory_status', 'available')
            ->where('qty_on_hand', '>', 0)
            ->orderBy('slot_id')
            ->orderBy('product_id')
            ->limit(100)
            ->get()
            ->map(fn (StorageSlotStock $stock) => [
                'slot_id' => (int) $stock->slot_id,
                'slot_label' => $this->slotLabel($stock->slot),
                'product_id' => (int) $stock->product_id,
                'variation_id' => $stock->variation_id ? (int) $stock->variation_id : null,
                'product_label' => (string) optional($stock->product)->name,
                'sku' => (string) (optional($stock->variation)->sub_sku ?: optional($stock->product)->sku ?: '—'),
                'inventory_status' => (string) $stock->inventory_status,
                'qty_on_hand' => (float) $stock->qty_on_hand,
                'lot_number' => (string) ($stock->lot_number ?: ''),
                'expiry_date' => optional($stock->expiry_date)->toDateString(),
            ])
            ->values();
    }

    protected function quarantineSlotOptions(int $businessId, int $locationId): array
    {
        if ($locationId <= 0) {
            return [];
        }

        $settings = StorageLocationSetting::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->first();

        $areaIds = array_values(array_filter([(int) ($settings->default_quarantine_area_id ?? 0)]));
        $options = $this->putawayService->slotOptionsForLocation($businessId, $locationId, $areaIds ?: null);

        return ! empty($options)
            ? $options
            : $this->putawayService->slotOptionsForLocation($businessId, $locationId);
    }

    protected function slotLabel(?StorageSlot $slot): string
    {
        if (! $slot) {
            return '—';
        }

        $parts = array_filter([
            $slot->slot_code,
            $slot->slot_name,
        ]);

        return ! empty($parts) ? implode(' • ', $parts) : ('#' . $slot->id);
    }
}
