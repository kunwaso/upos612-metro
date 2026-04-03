<?php

namespace Modules\StorageManager\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\StorageManager\Entities\StorageApprovalRequest;
use Modules\StorageManager\Entities\StorageCountLine;
use Modules\StorageManager\Entities\StorageCountSession;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLine;
use Modules\StorageManager\Entities\StorageDocumentLink;
use Modules\StorageManager\Entities\StorageLocationSetting;
use Modules\StorageManager\Entities\StorageSlot;
use Modules\StorageManager\Entities\StorageSlotStock;
use RuntimeException;

class CycleCountService
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService,
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
                    'open_sessions' => 0,
                    'pending_shortages' => 0,
                    'pending_gain_reviews' => 0,
                ],
                'sessionRows' => collect(),
                'approvalRows' => collect(),
            ];
        }

        $sessions = StorageCountSession::query()
            ->with(['area', 'lines'])
            ->where('business_id', $businessId)
            ->whereIn('location_id', $enabledLocationIds)
            ->orderByDesc('id')
            ->get();

        $approvals = StorageApprovalRequest::query()
            ->where('business_id', $businessId)
            ->whereIn('location_id', $enabledLocationIds)
            ->whereIn('approval_type', ['cycle_count_shrink', 'cycle_count_gain_review'])
            ->orderByDesc('id')
            ->get();

        return [
            'boardSummary' => [
                'enabled_location_count' => count($enabledLocationIds),
                'open_sessions' => (int) $sessions->whereNotIn('status', ['closed', 'cancelled'])->count(),
                'pending_shortages' => (int) $approvals->where('approval_type', 'cycle_count_shrink')->where('status', 'pending')->count(),
                'pending_gain_reviews' => (int) $approvals->where('approval_type', 'cycle_count_gain_review')->where('status', 'pending')->count(),
            ],
            'sessionRows' => $sessions->map(function (StorageCountSession $session) {
                return [
                    'id' => (int) $session->id,
                    'session_no' => (string) $session->session_no,
                    'location_id' => (int) $session->location_id,
                    'area_name' => (string) optional($session->area)->name,
                    'status' => (string) $session->status,
                    'freeze_mode' => (string) $session->freeze_mode,
                    'blind_count' => (bool) $session->blind_count,
                    'line_count' => (int) $session->lines->count(),
                    'variance_qty' => round((float) $session->lines->sum('variance_qty'), 4),
                    'scheduled_at' => optional($session->scheduled_at)->format('Y-m-d H:i'),
                ];
            })->values(),
            'approvalRows' => $approvals->map(fn (StorageApprovalRequest $approval) => [
                'id' => (int) $approval->id,
                'location_id' => (int) ($approval->location_id ?: 0),
                'approval_type' => (string) $approval->approval_type,
                'status' => (string) $approval->status,
                'threshold_value' => (float) ($approval->threshold_value ?: 0),
                'session_id' => (int) data_get($approval->payload, 'count_session_id', 0),
                'count_line_id' => (int) data_get($approval->payload, 'count_line_id', 0),
            ])->values(),
        ];
    }

    public function createSession(int $businessId, array $payload, int $userId): StorageCountSession
    {
        $locationId = (int) $payload['location_id'];
        $areaId = ! empty($payload['area_id']) ? (int) $payload['area_id'] : null;
        $freezeMode = (string) ($payload['freeze_mode'] ?? 'soft');
        $blindCount = (bool) ($payload['blind_count'] ?? false);
        if (! in_array($freezeMode, ['soft', 'hard'], true)) {
            throw new RuntimeException('Invalid freeze mode for cycle count session.');
        }

        $settings = $this->locationSettingForLocation($businessId, $locationId);
        $buckets = $this->countableBuckets($businessId, $locationId, $areaId);
        if ($buckets->isEmpty()) {
            throw new RuntimeException('No countable bin stock was found for the selected location or area.');
        }

        return DB::transaction(function () use ($businessId, $locationId, $areaId, $freezeMode, $blindCount, $userId, $payload, $settings, $buckets) {
            $session = new StorageCountSession([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'area_id' => $areaId,
                'session_no' => 'TMP-CC-' . uniqid(),
                'status' => $freezeMode === 'hard' ? 'frozen' : 'planned',
                'freeze_mode' => $freezeMode,
                'blind_count' => $blindCount,
                'created_by' => $userId,
                'scheduled_at' => now(),
                'started_at' => now(),
                'notes' => trim((string) ($payload['notes'] ?? '')) ?: null,
                'meta' => [
                    'location_name' => optional($settings->location)->name,
                ],
            ]);
            $session->save();
            $session->forceFill([
                'session_no' => 'CC-' . str_pad((string) $session->id, 6, '0', STR_PAD_LEFT),
            ])->save();

            foreach ($buckets->values() as $index => $bucket) {
                $line = StorageCountLine::query()->create([
                    'business_id' => $businessId,
                    'count_session_id' => $session->id,
                    'slot_id' => $bucket->slot_id,
                    'product_id' => $bucket->product_id,
                    'variation_id' => $bucket->variation_id,
                    'inventory_status' => $freezeMode === 'hard' ? 'count_hold' : $bucket->inventory_status,
                    'lot_number' => $bucket->lot_number,
                    'expiry_date' => $bucket->expiry_date,
                    'system_qty' => $bucket->qty_on_hand,
                    'status' => 'open',
                    'meta' => [
                        'line_no' => $index + 1,
                        'slot_area_id' => $bucket->area_id,
                        'original_inventory_status' => $bucket->inventory_status,
                    ],
                ]);

                if ($freezeMode === 'hard') {
                    $this->inventoryMovementService->applyMovement([
                        'business_id' => $businessId,
                        'location_id' => $locationId,
                        'source_type' => 'cycle_count_session',
                        'source_id' => $session->id,
                        'source_line_id' => $line->id,
                        'movement_type' => 'cycle_count_hold',
                        'direction' => 'move',
                        'product_id' => $bucket->product_id,
                        'variation_id' => $bucket->variation_id,
                        'from_area_id' => $bucket->area_id,
                        'to_area_id' => $bucket->area_id,
                        'from_slot_id' => $bucket->slot_id,
                        'to_slot_id' => $bucket->slot_id,
                        'from_status' => $bucket->inventory_status,
                        'to_status' => 'count_hold',
                        'lot_number' => $bucket->lot_number,
                        'expiry_date' => optional($bucket->expiry_date)->toDateString(),
                        'quantity' => (float) $bucket->qty_on_hand,
                        'reason_code' => 'cycle_count_hold',
                        'idempotency_key' => 'cycle-count-hold-' . $session->id . '-line-' . $line->id,
                        'created_by' => $userId,
                    ]);
                }
            }

            return $session->fresh(['area', 'lines.slot', 'lines.product', 'lines.variation']);
        });
    }

    public function getWorkbench(int $businessId, int $sessionId): array
    {
        $session = StorageCountSession::query()
            ->with(['area', 'lines.slot', 'lines.product', 'lines.variation'])
            ->where('business_id', $businessId)
            ->findOrFail($sessionId);

        $approvals = StorageApprovalRequest::query()
            ->where('business_id', $businessId)
            ->whereIn('approval_type', ['cycle_count_shrink', 'cycle_count_gain_review'])
            ->where(function ($query) use ($sessionId) {
                $query->where('payload->count_session_id', $sessionId)
                    ->orWhere('payload->session_id', $sessionId);
            })
            ->orderByDesc('id')
            ->get();

        return [
            'session' => $session,
            'lineRows' => $session->lines->map(function (StorageCountLine $line) use ($session) {
                return [
                    'id' => (int) $line->id,
                    'slot_label' => $this->slotLabel($line->slot),
                    'product_label' => (string) optional($line->product)->name,
                    'sku' => (string) (optional($line->variation)->sub_sku ?: optional($line->product)->sku ?: '—'),
                    'inventory_status' => (string) data_get($line->meta, 'original_inventory_status', $line->inventory_status),
                    'system_qty' => $session->blind_count ? null : (float) $line->system_qty,
                    'counted_qty' => $line->counted_qty !== null ? (float) $line->counted_qty : null,
                    'variance_qty' => (float) $line->variance_qty,
                    'status' => (string) $line->status,
                    'lot_number' => (string) ($line->lot_number ?: '—'),
                    'expiry_date' => optional($line->expiry_date)->toDateString(),
                    'reason_code' => (string) ($line->reason_code ?: ''),
                ];
            })->values(),
            'approvals' => $approvals,
        ];
    }

    public function submitCounts(int $businessId, StorageCountSession $session, array $payload, int $userId): StorageCountSession
    {
        if (in_array($session->status, ['closed', 'cancelled'], true)) {
            throw new RuntimeException('This cycle count session is already closed.');
        }

        $lineInputs = (array) ($payload['lines'] ?? []);
        if (empty($lineInputs)) {
            throw new RuntimeException('At least one counted line is required.');
        }

        $session->loadMissing(['lines.slot']);

        DB::transaction(function () use ($businessId, $session, $lineInputs, $userId) {
            foreach ($session->lines as $line) {
                $input = (array) ($lineInputs[$line->id] ?? []);
                if (! array_key_exists('counted_qty', $input)) {
                    continue;
                }

                $countedQty = round((float) $input['counted_qty'], 4);
                if ($countedQty < 0) {
                    throw new RuntimeException("Counted quantity cannot be negative for count line [{$line->id}].");
                }

                $variance = round($countedQty - (float) $line->system_qty, 4);
                $reasonCode = trim((string) ($input['reason_code'] ?? ''));
                $line->forceFill([
                    'counted_qty' => $countedQty,
                    'variance_qty' => $variance,
                    'counted_by' => $userId,
                    'reason_code' => $reasonCode !== '' ? $reasonCode : null,
                ])->save();

                if ($variance === 0.0) {
                    if ($session->freeze_mode === 'hard') {
                        $this->releaseHeldQuantity($businessId, $session, $line, (float) $line->system_qty, $userId, 'cycle_count_zero_variance_release');
                    }

                    $line->forceFill([
                        'status' => 'approved',
                        'reviewed_by' => $userId,
                    ])->save();
                    continue;
                }

                if ($variance > 0) {
                    if ($session->freeze_mode === 'hard') {
                        $this->releaseHeldQuantity($businessId, $session, $line, (float) $line->system_qty, $userId, 'cycle_count_gain_review_release');
                    }

                    $this->upsertApproval($businessId, $session, $line, 'cycle_count_gain_review', 'increase', abs($variance), $userId, 'Positive cycle count variance requires manual source-truth review.');
                    $line->forceFill([
                        'status' => 'review',
                        'reviewed_by' => $userId,
                    ])->save();
                    continue;
                }

                $this->upsertApproval($businessId, $session, $line, 'cycle_count_shrink', 'decrease', abs($variance), $userId, 'Negative cycle count variance is pending warehouse approval.');
                $line->forceFill([
                    'status' => 'review',
                    'reviewed_by' => $userId,
                ])->save();
            }

            $hasReview = $session->lines()->where('status', 'review')->exists();
            $session->forceFill([
                'status' => $hasReview ? 'review' : 'closed',
                'closed_at' => $hasReview ? null : now(),
            ])->save();
        });

        return $session->fresh(['area', 'lines.slot', 'lines.product', 'lines.variation']);
    }

    public function approveShortages(int $businessId, StorageCountSession $session, array $payload, int $userId): StorageCountSession
    {
        $session->loadMissing(['lines.slot', 'lines.product', 'lines.variation']);
        $negativeLines = $session->lines->filter(fn (StorageCountLine $line) => (float) $line->variance_qty < 0 && $line->status === 'review');

        if ($negativeLines->isEmpty()) {
            throw new RuntimeException('There are no negative count variances waiting for approval in this session.');
        }

        DB::transaction(function () use ($businessId, $session, $payload, $userId, $negativeLines) {
            $document = $this->ensureAdjustmentDocument($businessId, $session, $userId);
            $adjustmentLines = [];

            foreach ($negativeLines->values() as $index => $line) {
                $shortageQty = abs((float) $line->variance_qty);
                $originalStatus = (string) data_get($line->meta, 'original_inventory_status', 'available');

                if ($session->freeze_mode === 'hard') {
                    $this->releaseHeldQuantity($businessId, $session, $line, (float) $line->counted_qty, $userId, 'cycle_count_shortage_release');
                    $this->removeHeldShortage($businessId, $session, $line, $shortageQty, $userId);
                } else {
                    $this->removeSoftShortage($businessId, $session, $line, $shortageQty, $originalStatus, $userId);
                }

                $adjustmentLines[] = [
                    'product_id' => (int) $line->product_id,
                    'variation_id' => $line->variation_id ? (int) $line->variation_id : null,
                    'quantity' => $shortageQty,
                    'unit_price' => 0,
                ];

                $documentLine = StorageDocumentLine::query()->updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'document_id' => $document->id,
                        'line_no' => $index + 1,
                    ],
                    [
                        'source_line_id' => $line->id,
                        'product_id' => $line->product_id,
                        'variation_id' => $line->variation_id,
                        'from_area_id' => data_get($line->meta, 'slot_area_id'),
                        'from_slot_id' => $line->slot_id,
                        'expected_qty' => $shortageQty,
                        'executed_qty' => $shortageQty,
                        'variance_qty' => 0,
                        'unit_cost' => 0,
                        'inventory_status' => $originalStatus,
                        'result_status' => 'approved',
                        'lot_number' => $line->lot_number,
                        'expiry_date' => $line->expiry_date,
                        'reason_code' => 'cycle_count_shortage',
                        'meta' => [
                            'count_direction' => 'decrease',
                            'variance_signed' => (float) $line->variance_qty,
                            'count_session_id' => $session->id,
                        ],
                    ]
                );

                StorageApprovalRequest::query()
                    ->where('business_id', $businessId)
                    ->where('approval_type', 'cycle_count_shrink')
                    ->where('payload->count_session_id', $session->id)
                    ->where('payload->count_line_id', $line->id)
                    ->update([
                        'document_id' => $document->id,
                        'document_line_id' => $documentLine->id,
                        'status' => 'approved',
                        'approved_by' => $userId,
                        'resolved_at' => now(),
                        'notes' => trim((string) ($payload['approval_notes'] ?? '')) ?: 'Cycle count shortage approved.',
                    ]);

                $line->forceFill([
                    'status' => 'approved',
                    'approved_by' => $userId,
                    'reviewed_by' => $userId,
                ])->save();
            }

            $linkedTransaction = $this->stockAdjustmentBridgeService->createDecreaseAdjustment(
                $businessId,
                (int) $session->location_id,
                $adjustmentLines,
                $userId,
                [
                    'notes' => trim((string) ($payload['approval_notes'] ?? $session->notes ?? '')),
                    'adjustment_type' => 'abnormal',
                ]
            );

            $document->forceFill([
                'source_type' => 'stock_adjustment',
                'source_id' => $linkedTransaction->id,
                'source_ref' => $linkedTransaction->ref_no,
                'status' => 'closed',
                'workflow_state' => 'adjusted',
                'approval_status' => 'approved',
                'approved_by' => $userId,
                'sync_status' => 'pending_sync',
                'completed_at' => now(),
                'closed_at' => now(),
                'closed_by' => $userId,
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

            $hasReview = $session->lines()->where('status', 'review')->exists();
            $session->forceFill([
                'status' => $hasReview ? 'review' : 'closed',
                'approved_by' => $hasReview ? null : $userId,
                'closed_at' => $hasReview ? null : now(),
            ])->save();
        });

        $document = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'cycle_count')
            ->where('source_ref', $session->session_no)
            ->latest('id')
            ->first();

        if ($document) {
            $this->warehouseSyncService->syncDocument($document, $userId);
        }

        return $session->fresh(['area', 'lines.slot', 'lines.product', 'lines.variation']);
    }

    protected function upsertApproval(
        int $businessId,
        StorageCountSession $session,
        StorageCountLine $line,
        string $approvalType,
        string $direction,
        float $thresholdValue,
        int $userId,
        string $notes
    ): void {
        StorageApprovalRequest::query()
            ->where('business_id', $businessId)
            ->where('approval_type', $approvalType)
            ->where('status', 'pending')
            ->where('payload->count_session_id', $session->id)
            ->where('payload->count_line_id', $line->id)
            ->delete();

        StorageApprovalRequest::query()->create([
            'business_id' => $businessId,
            'location_id' => $session->location_id,
            'approval_type' => $approvalType,
            'status' => 'pending',
            'requested_by' => $userId,
            'threshold_value' => $thresholdValue,
            'notes' => $notes,
            'payload' => [
                'count_session_id' => $session->id,
                'count_line_id' => $line->id,
                'direction' => $direction,
            ],
        ]);
    }

    protected function ensureAdjustmentDocument(int $businessId, StorageCountSession $session, int $userId): StorageDocument
    {
        $document = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'cycle_count')
            ->where('source_ref', $session->session_no)
            ->first();

        if ($document) {
            return $document;
        }

        $document = new StorageDocument([
            'business_id' => $businessId,
            'location_id' => $session->location_id,
            'area_id' => $session->area_id,
            'document_no' => 'TMP-CCD-' . uniqid(),
            'document_type' => 'cycle_count',
            'source_type' => 'cycle_count_session',
            'source_id' => $session->id,
            'source_ref' => $session->session_no,
            'status' => 'open',
            'workflow_state' => 'review',
            'execution_mode' => optional($this->locationSettingForLocation($businessId, (int) $session->location_id))->execution_mode,
            'sync_status' => 'not_required',
            'approval_status' => 'pending',
            'requested_by' => $session->created_by,
            'created_by' => $userId,
            'notes' => $session->notes,
            'meta' => [
                'count_session_id' => $session->id,
                'location_name' => data_get($session->meta, 'location_name'),
            ],
        ]);
        $document->save();
        $document->forceFill([
            'document_no' => 'CCD-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
        ])->save();

        return $document;
    }

    protected function countableBuckets(int $businessId, int $locationId, ?int $areaId = null): Collection
    {
        return StorageSlotStock::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->when($areaId, fn ($query) => $query->where('area_id', $areaId))
            ->where('qty_on_hand', '>', 0)
            ->whereIn('inventory_status', ['available', 'quarantine', 'damaged', 'blocked'])
            ->orderBy('slot_id')
            ->orderBy('product_id')
            ->get();
    }

    protected function releaseHeldQuantity(
        int $businessId,
        StorageCountSession $session,
        StorageCountLine $line,
        float $quantity,
        int $userId,
        string $reasonCode
    ): void {
        $quantity = round($quantity, 4);
        if ($quantity <= 0) {
            return;
        }

        $originalStatus = (string) data_get($line->meta, 'original_inventory_status', 'available');
        $slot = $this->validatedSlot($businessId, (int) $session->location_id, (int) $line->slot_id);

        $this->inventoryMovementService->applyMovement([
            'business_id' => $businessId,
            'location_id' => $session->location_id,
            'source_type' => 'cycle_count_session',
            'source_id' => $session->id,
            'source_line_id' => $line->id,
            'movement_type' => 'cycle_count_release',
            'direction' => 'move',
            'product_id' => $line->product_id,
            'variation_id' => $line->variation_id,
            'from_area_id' => $slot->area_id,
            'to_area_id' => $slot->area_id,
            'from_slot_id' => $slot->id,
            'to_slot_id' => $slot->id,
            'from_status' => 'count_hold',
            'to_status' => $originalStatus,
            'lot_number' => $line->lot_number,
            'expiry_date' => optional($line->expiry_date)->toDateString(),
            'quantity' => $quantity,
            'reason_code' => $reasonCode,
            'idempotency_key' => $reasonCode . '-' . $session->id . '-line-' . $line->id,
            'created_by' => $userId,
        ]);
    }

    protected function removeHeldShortage(int $businessId, StorageCountSession $session, StorageCountLine $line, float $quantity, int $userId): void
    {
        $slot = $this->validatedSlot($businessId, (int) $session->location_id, (int) $line->slot_id);

        $this->inventoryMovementService->applyMovement([
            'business_id' => $businessId,
            'location_id' => $session->location_id,
            'source_type' => 'cycle_count_session',
            'source_id' => $session->id,
            'source_line_id' => $line->id,
            'movement_type' => 'cycle_count_shortage',
            'direction' => 'out',
            'product_id' => $line->product_id,
            'variation_id' => $line->variation_id,
            'from_area_id' => $slot->area_id,
            'from_slot_id' => $slot->id,
            'from_status' => 'count_hold',
            'lot_number' => $line->lot_number,
            'expiry_date' => optional($line->expiry_date)->toDateString(),
            'quantity' => $quantity,
            'reason_code' => 'cycle_count_shortage',
            'idempotency_key' => 'cycle-count-shortage-' . $session->id . '-line-' . $line->id,
            'created_by' => $userId,
        ]);
    }

    protected function removeSoftShortage(
        int $businessId,
        StorageCountSession $session,
        StorageCountLine $line,
        float $quantity,
        string $originalStatus,
        int $userId
    ): void {
        $slot = $this->validatedSlot($businessId, (int) $session->location_id, (int) $line->slot_id);

        $this->ensureLiveBucket(
            $businessId,
            (int) $session->location_id,
            (int) $line->slot_id,
            (int) $line->product_id,
            $line->variation_id ? (int) $line->variation_id : null,
            $originalStatus,
            $quantity,
            (string) $line->lot_number,
            optional($line->expiry_date)->toDateString()
        );

        $this->inventoryMovementService->applyMovement([
            'business_id' => $businessId,
            'location_id' => $session->location_id,
            'source_type' => 'cycle_count_session',
            'source_id' => $session->id,
            'source_line_id' => $line->id,
            'movement_type' => 'cycle_count_shortage',
            'direction' => 'out',
            'product_id' => $line->product_id,
            'variation_id' => $line->variation_id,
            'from_area_id' => $slot->area_id,
            'from_slot_id' => $slot->id,
            'from_status' => $originalStatus,
            'lot_number' => $line->lot_number,
            'expiry_date' => optional($line->expiry_date)->toDateString(),
            'quantity' => $quantity,
            'reason_code' => 'cycle_count_shortage',
            'idempotency_key' => 'cycle-count-shortage-' . $session->id . '-line-' . $line->id,
            'created_by' => $userId,
        ]);
    }

    protected function ensureLiveBucket(
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

        if (! $query->exists()) {
            throw new RuntimeException('The live slot stock no longer matches the cycle count shortage being approved. Recount before posting the variance.');
        }
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
