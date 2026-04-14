<?php

namespace Modules\StorageManager\Services;

use App\ProductRack;
use Illuminate\Support\Facades\DB;
use Modules\StorageManager\Entities\StorageArea;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLine;
use Modules\StorageManager\Entities\StorageLocationSetting;
use Modules\StorageManager\Entities\StorageSlot;
use Modules\StorageManager\Entities\StorageTask;
use Modules\StorageManager\Entities\StorageTaskEvent;
use Modules\StorageManager\Utils\StorageManagerUtil;
use RuntimeException;

class PutawayService
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService,
        protected StorageManagerUtil $storageManagerUtil
    ) {
    }

    public function queueForLocation(int $businessId, ?int $locationId = null): array
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
                'documents' => collect(),
                'queueSummary' => [
                    'open_documents' => 0,
                    'open_tasks' => 0,
                    'queued_qty' => 0,
                ],
            ];
        }

        $documents = StorageDocument::query()
            ->with(['parentDocument', 'lines', 'tasks'])
            ->where('business_id', $businessId)
            ->where('document_type', 'putaway')
            ->whereIn('location_id', $enabledLocationIds)
            ->whereNotIn('status', ['closed', 'completed', 'cancelled'])
            ->orderByDesc('id')
            ->get();

        $rows = $documents->map(function (StorageDocument $document) {
            $queuedQty = (float) $document->lines->sum(fn (StorageDocumentLine $line) => (float) ($line->executed_qty ?: $line->expected_qty));

            return [
                'id' => (int) $document->id,
                'document_no' => (string) $document->document_no,
                'parent_document_no' => (string) optional($document->parentDocument)->document_no,
                'source_ref' => (string) ($document->source_ref ?: optional($document->parentDocument)->source_ref),
                'location_name' => (string) data_get($document->meta, 'location_name', '#' . $document->location_id),
                'line_count' => (int) $document->lines->count(),
                'queued_qty' => $queuedQty,
                'status' => (string) $document->status,
                'workflow_state' => (string) $document->workflow_state,
                'sync_status' => (string) $document->sync_status,
            ];
        });

        return [
            'documents' => $rows,
            'queueSummary' => [
                'open_documents' => (int) $rows->count(),
                'open_tasks' => (int) $documents->sum(fn (StorageDocument $document) => $document->tasks->whereIn('status', ['open', 'assigned', 'in_progress'])->count()),
                'queued_qty' => round((float) $rows->sum('queued_qty'), 4),
            ],
        ];
    }

    public function getWorkbench(int $businessId, int $documentId): array
    {
        $document = StorageDocument::query()
            ->with([
                'lines.product',
                'lines.variation',
                'lines.fromArea',
                'lines.fromSlot',
                'lines.toArea',
                'lines.toSlot',
                'parentDocument',
            ])
            ->where('business_id', $businessId)
            ->where('document_type', 'putaway')
            ->findOrFail($documentId);

        $slotOptions = $this->slotOptionsForLocation($businessId, (int) $document->location_id);
        $lineRows = $document->lines->map(function (StorageDocumentLine $line) use ($businessId, $document) {
            $suggestedSlot = $line->toSlot ?: $this->suggestSlotForProduct(
                $businessId,
                (int) $document->location_id,
                (int) $line->product_id,
                $line->variation_id ? (int) $line->variation_id : null
            );

            return [
                'id' => (int) $line->id,
                'product_label' => (string) optional($line->product)->name,
                'sku' => (string) (optional($line->variation)->sub_sku ?: optional($line->product)->sku ?: '—'),
                'qty' => (float) ($line->executed_qty ?: $line->expected_qty),
                'from_slot_label' => $this->slotLabel($line->fromSlot),
                'from_area_label' => optional($line->fromArea)->name ?: '—',
                'suggested_slot_id' => $suggestedSlot?->id,
                'suggested_slot_label' => $this->slotLabel($suggestedSlot),
                'selected_slot_id' => $line->to_slot_id ?: $suggestedSlot?->id,
                'result_status' => (string) $line->result_status,
                'lot_number' => (string) ($line->lot_number ?: '—'),
                'expiry_date' => optional($line->expiry_date)->toDateString(),
            ];
        })->values();

        $canReopen = false;
        $reopenReason = null;
        if (! in_array((string) $document->status, ['closed', 'completed'], true)) {
            $reopenReason = 'Putaway can only be reopened after completion.';
        } elseif ((string) $document->sync_status === 'posted') {
            $reopenReason = 'Putaway is already posted to accounting and cannot be reopened.';
        } else {
            $canReopen = true;
        }

        return [
            'document' => $document,
            'parentDocument' => $document->parentDocument,
            'sourceSummary' => [
                'source_ref' => (string) ($document->source_ref ?: optional($document->parentDocument)->source_ref),
                'location_name' => (string) data_get($document->meta, 'location_name', '#' . $document->location_id),
                'receipt_document_no' => (string) optional($document->parentDocument)->document_no,
                'status' => (string) $document->status,
                'workflow_state' => (string) $document->workflow_state,
                'sync_status' => (string) ($document->sync_status ?: optional($document->parentDocument)->sync_status ?: 'not_required'),
                'can_reopen' => $canReopen,
                'reopen_reason' => $reopenReason,
            ],
            'lineRows' => $lineRows,
            'slotOptions' => $slotOptions,
        ];
    }

    public function ensureDocumentForReceipt(StorageDocument $receiptDocument, int $userId): StorageDocument
    {
        $document = StorageDocument::query()
            ->where('business_id', $receiptDocument->business_id)
            ->where('document_type', 'putaway')
            ->where('parent_document_id', $receiptDocument->id)
            ->first();

        if (! $document) {
            $document = new StorageDocument([
                'business_id' => $receiptDocument->business_id,
                'location_id' => $receiptDocument->location_id,
                'area_id' => $receiptDocument->area_id,
                'parent_document_id' => $receiptDocument->id,
                'document_no' => 'TMP-PUT-' . uniqid(),
                'document_type' => 'putaway',
                'source_type' => $receiptDocument->source_type,
                'source_id' => $receiptDocument->source_id,
                'source_ref' => $receiptDocument->source_ref,
                'status' => 'open',
                'workflow_state' => 'pending',
                'execution_mode' => $receiptDocument->execution_mode,
                'sync_status' => 'not_required',
                'requested_by' => $receiptDocument->requested_by,
                'created_by' => $userId,
                'notes' => 'Generated from receipt ' . $receiptDocument->document_no,
                'meta' => [
                    'location_name' => data_get($receiptDocument->meta, 'location_name'),
                    'generated_from_receipt' => true,
                ],
            ]);
            $document->save();
            $document->forceFill([
                'document_no' => 'PUT-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
            ])->save();
        }

        $this->syncLinesFromReceipt($document->fresh(), $receiptDocument->fresh('lines.product', 'lines.variation'), $userId);

        return $document->fresh([
            'lines.product',
            'lines.variation',
            'lines.fromArea',
            'lines.fromSlot',
            'lines.toArea',
            'lines.toSlot',
            'tasks',
            'parentDocument',
        ]);
    }

    public function completePutaway(int $businessId, StorageDocument $document, array $payload, int $userId): StorageDocument
    {
        if ($document->document_type !== 'putaway') {
            throw new RuntimeException('Only putaway documents can be completed from the putaway workbench.');
        }

        if (in_array($document->status, ['closed', 'completed', 'cancelled'], true)) {
            throw new RuntimeException('This putaway document is already closed.');
        }

        $lineInputs = (array) ($payload['lines'] ?? []);
        $document->loadMissing(['lines.tasks']);

        DB::transaction(function () use ($businessId, $document, $lineInputs, $userId) {
            $documentMeta = (array) $document->meta;
            $putawayAttempt = (int) data_get($documentMeta, 'putaway_attempt', 0) + 1;
            $documentMeta['putaway_attempt'] = $putawayAttempt;
            $documentMeta['last_putaway_completed_at'] = now()->toDateTimeString();

            foreach ($document->lines as $line) {
                $input = (array) ($lineInputs[$line->id] ?? []);
                $destinationSlotId = (int) ($input['destination_slot_id'] ?? $line->to_slot_id);
                if ($destinationSlotId <= 0) {
                    throw new RuntimeException("Destination slot is required for putaway line [{$line->id}].");
                }

                $destinationSlot = StorageSlot::query()
                    ->where('business_id', $businessId)
                    ->where('location_id', $document->location_id)
                    ->active()
                    ->find($destinationSlotId);

                if (! $destinationSlot) {
                    throw new RuntimeException("Destination slot [{$destinationSlotId}] is invalid for putaway line [{$line->id}].");
                }

                $quantity = round((float) ($line->executed_qty ?: $line->expected_qty), 4);
                if ($quantity <= 0) {
                    continue;
                }

                $this->inventoryMovementService->applyMovement([
                    'business_id' => $businessId,
                    'location_id' => $document->location_id,
                    'document_id' => $document->id,
                    'document_line_id' => $line->id,
                    'source_type' => $document->source_type,
                    'source_id' => $document->source_id,
                    'source_line_id' => $line->source_line_id,
                    'movement_type' => 'putaway',
                    'direction' => 'move',
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'from_area_id' => $line->from_area_id,
                    'to_area_id' => $destinationSlot->area_id,
                    'from_slot_id' => $line->from_slot_id,
                    'to_slot_id' => $destinationSlotId,
                    'from_status' => 'staged_in',
                    'to_status' => 'available',
                    'lot_number' => $line->lot_number,
                    'expiry_date' => optional($line->expiry_date)->toDateString(),
                    'quantity' => $quantity,
                    'unit_cost' => $line->unit_cost,
                    'reason_code' => 'putaway',
                    'idempotency_key' => 'putaway-' . $document->id . '-line-' . $line->id . '-attempt-' . $putawayAttempt,
                    'created_by' => $userId,
                ]);

                $this->storageManagerUtil->syncWarehouseMapSlotForProduct(
                    $businessId,
                    (int) $line->product_id,
                    (int) $document->location_id,
                    $destinationSlotId
                );

                $line->forceFill([
                    'to_area_id' => $destinationSlot->area_id,
                    'to_slot_id' => $destinationSlotId,
                    'inventory_status' => 'available',
                    'result_status' => 'completed',
                    'executed_qty' => $quantity,
                    'variance_qty' => 0,
                ])->save();

                foreach ($line->tasks as $task) {
                    $task->forceFill([
                        'status' => 'done',
                        'completed_qty' => $quantity,
                        'completed_at' => now(),
                        'meta' => array_merge((array) $task->meta, [
                            'destination_slot_id' => $destinationSlotId,
                        ]),
                    ])->save();

                    StorageTaskEvent::query()->create([
                        'business_id' => $businessId,
                        'task_id' => $task->id,
                        'event_type' => 'completed',
                        'user_id' => $userId,
                        'payload' => [
                            'destination_slot_id' => $destinationSlotId,
                            'quantity' => $quantity,
                        ],
                    ]);
                }
            }

            $document->forceFill([
                'status' => 'closed',
                'workflow_state' => 'completed',
                'completed_at' => now(),
                'closed_at' => now(),
                'closed_by' => $userId,
                'meta' => $documentMeta,
            ])->save();

            if ($document->parent_document_id) {
                $siblingsRemaining = StorageDocument::query()
                    ->where('business_id', $businessId)
                    ->where('document_type', 'putaway')
                    ->where('parent_document_id', $document->parent_document_id)
                    ->whereNotIn('status', ['closed', 'completed', 'cancelled'])
                    ->where('id', '!=', $document->id)
                    ->exists();

                if (! $siblingsRemaining) {
                    StorageDocument::query()
                        ->where('business_id', $businessId)
                        ->where('id', $document->parent_document_id)
                        ->update([
                            'status' => 'closed',
                            'workflow_state' => 'closed',
                            'closed_at' => now(),
                            'closed_by' => $userId,
                        ]);
                }
            }
        });

        return $document->fresh([
            'lines.product',
            'lines.variation',
            'lines.fromArea',
            'lines.fromSlot',
            'lines.toArea',
            'lines.toSlot',
            'parentDocument',
        ]);
    }

    public function reopenPutaway(int $businessId, StorageDocument $document, int $userId): StorageDocument
    {
        if ($document->document_type !== 'putaway') {
            throw new RuntimeException('Only putaway documents can be reopened from the putaway workbench.');
        }

        if (! in_array((string) $document->status, ['closed', 'completed'], true)) {
            throw new RuntimeException('Only completed putaway documents can be reopened.');
        }

        if ((string) $document->sync_status === 'posted') {
            throw new RuntimeException('This putaway is already posted to accounting and cannot be reopened.');
        }

        DB::transaction(function () use ($businessId, $document, $userId) {
            $document->loadMissing(['lines.tasks', 'parentDocument']);
            $documentMeta = (array) $document->meta;
            $putawayAttempt = max((int) data_get($documentMeta, 'putaway_attempt', 1), 1);

            foreach ($document->lines as $line) {
                $quantity = round((float) ($line->executed_qty ?: $line->expected_qty), 4);
                if ($quantity <= 0) {
                    continue;
                }

                $stagingSlotId = (int) ($line->from_slot_id ?: 0);
                $destinationSlotId = (int) ($line->to_slot_id ?: 0);
                if ($stagingSlotId <= 0 || $destinationSlotId <= 0) {
                    throw new RuntimeException("Putaway line [{$line->id}] is missing slot data and cannot be reopened.");
                }

                $stagingSlot = StorageSlot::query()
                    ->where('business_id', $businessId)
                    ->where('location_id', $document->location_id)
                    ->find($stagingSlotId);
                $destinationSlot = StorageSlot::query()
                    ->where('business_id', $businessId)
                    ->where('location_id', $document->location_id)
                    ->find($destinationSlotId);

                if (! $stagingSlot || ! $destinationSlot) {
                    throw new RuntimeException("Putaway line [{$line->id}] cannot be reopened because slots are no longer available.");
                }

                $this->inventoryMovementService->applyMovement([
                    'business_id' => $businessId,
                    'location_id' => $document->location_id,
                    'document_id' => $document->id,
                    'document_line_id' => $line->id,
                    'source_type' => $document->source_type,
                    'source_id' => $document->source_id,
                    'source_line_id' => $line->source_line_id,
                    'movement_type' => 'putaway_reopen',
                    'direction' => 'move',
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'from_area_id' => $destinationSlot->area_id ?: $line->to_area_id,
                    'to_area_id' => $stagingSlot->area_id ?: $line->from_area_id,
                    'from_slot_id' => $destinationSlotId,
                    'to_slot_id' => $stagingSlotId,
                    'from_status' => 'available',
                    'to_status' => 'staged_in',
                    'lot_number' => $line->lot_number,
                    'expiry_date' => optional($line->expiry_date)->toDateString(),
                    'quantity' => $quantity,
                    'unit_cost' => $line->unit_cost,
                    'reason_code' => 'putaway_reopen',
                    'idempotency_key' => 'putaway-reopen-' . $document->id . '-line-' . $line->id . '-attempt-' . $putawayAttempt,
                    'created_by' => $userId,
                ]);

                $this->storageManagerUtil->syncWarehouseMapSlotForProduct(
                    $businessId,
                    (int) $line->product_id,
                    (int) $document->location_id,
                    $stagingSlotId
                );

                $line->forceFill([
                    'result_status' => 'pending',
                    'executed_qty' => $quantity,
                    'variance_qty' => 0,
                ])->save();

                foreach ($line->tasks as $task) {
                    $task->forceFill([
                        'status' => 'open',
                        'completed_qty' => 0,
                        'completed_at' => null,
                        'meta' => array_merge((array) $task->meta, [
                            'reopened_at' => now()->toDateTimeString(),
                        ]),
                    ])->save();

                    StorageTaskEvent::query()->create([
                        'business_id' => $businessId,
                        'task_id' => $task->id,
                        'event_type' => 'reopened',
                        'user_id' => $userId,
                        'payload' => [
                            'source_slot_id' => $destinationSlotId,
                            'return_slot_id' => $stagingSlotId,
                            'quantity' => $quantity,
                        ],
                    ]);
                }
            }

            $nextSyncStatus = (string) $document->sync_status === 'not_required'
                ? 'not_required'
                : 'pending_sync';
            $documentMeta['last_putaway_reopened_at'] = now()->toDateTimeString();
            $documentMeta['last_putaway_reopened_attempt'] = $putawayAttempt;

            $document->forceFill([
                'status' => 'open',
                'workflow_state' => 'pending',
                'completed_at' => null,
                'closed_at' => null,
                'closed_by' => null,
                'sync_status' => $nextSyncStatus,
                'meta' => $documentMeta,
            ])->save();

            if ($document->parentDocument && $document->parentDocument->document_type === 'receipt') {
                $document->parentDocument->forceFill([
                    'status' => 'completed',
                    'workflow_state' => 'putaway_pending',
                    'closed_at' => null,
                    'closed_by' => null,
                ])->save();
            }
        });

        return $document->fresh([
            'lines.product',
            'lines.variation',
            'lines.fromArea',
            'lines.fromSlot',
            'lines.toArea',
            'lines.toSlot',
            'parentDocument',
        ]);
    }

    public function slotOptionsForLocation(int $businessId, int $locationId, ?array $areaIds = null): array
    {
        $slots = StorageSlot::query()
            ->with('area')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->active()
            ->when(! empty($areaIds), fn ($query) => $query->whereIn('area_id', array_values(array_filter(array_map('intval', $areaIds)))))
            ->orderBy('pick_sequence')
            ->orderBy('putaway_sequence')
            ->orderBy('row')
            ->orderBy('position')
            ->get();

        $options = [];
        foreach ($slots as $slot) {
            $options[$slot->id] = $this->slotLabel($slot);
        }

        return $options;
    }

    public function suggestSlotForProduct(int $businessId, int $locationId, int $productId, ?int $variationId = null): ?StorageSlot
    {
        $homeSlotId = ProductRack::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->value('slot_id');

        if ($homeSlotId) {
            $homeSlot = StorageSlot::query()
                ->with('area')
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->active()
                ->find($homeSlotId);

            if ($homeSlot) {
                return $homeSlot;
            }
        }

        $reserveAreaIds = StorageArea::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->whereIn('area_type', ['reserve', 'forward_pick', 'legacy_zone'])
            ->pluck('id')
            ->all();

        return StorageSlot::query()
            ->with('area')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->active()
            ->when(! empty($reserveAreaIds), fn ($query) => $query->whereIn('area_id', $reserveAreaIds))
            ->orderBy('putaway_sequence')
            ->orderBy('pick_sequence')
            ->orderBy('row')
            ->orderBy('position')
            ->first();
    }

    protected function syncLinesFromReceipt(StorageDocument $document, StorageDocument $receiptDocument, int $userId): void
    {
        $receiptDocument->loadMissing('lines');
        $activeLineIds = [];

        foreach ($receiptDocument->lines as $index => $receiptLine) {
            $quantity = round((float) ($receiptLine->executed_qty ?: $receiptLine->expected_qty), 4);
            if ($quantity <= 0) {
                continue;
            }

            $destinationSlot = $this->suggestSlotForProduct(
                (int) $document->business_id,
                (int) $document->location_id,
                (int) $receiptLine->product_id,
                $receiptLine->variation_id ? (int) $receiptLine->variation_id : null
            );

            $line = StorageDocumentLine::query()->updateOrCreate(
                [
                    'business_id' => $document->business_id,
                    'document_id' => $document->id,
                    'parent_line_id' => $receiptLine->id,
                ],
                [
                    'line_no' => $index + 1,
                    'source_line_id' => $receiptLine->source_line_id,
                    'product_id' => $receiptLine->product_id,
                    'variation_id' => $receiptLine->variation_id,
                    'from_area_id' => $receiptLine->to_area_id,
                    'to_area_id' => $destinationSlot?->area_id,
                    'from_slot_id' => $receiptLine->to_slot_id,
                    'to_slot_id' => $destinationSlot?->id,
                    'expected_qty' => $quantity,
                    'executed_qty' => $quantity,
                    'variance_qty' => 0,
                    'unit_cost' => $receiptLine->unit_cost,
                    'inventory_status' => 'available',
                    'result_status' => 'pending',
                    'lot_number' => $receiptLine->lot_number,
                    'expiry_date' => $receiptLine->expiry_date,
                    'meta' => [
                        'generated_from_receipt_line_id' => $receiptLine->id,
                    ],
                ]
            );

            $activeLineIds[] = (int) $line->id;

            $task = StorageTask::query()->firstOrNew([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'document_line_id' => $line->id,
                'task_type' => 'putaway',
            ]);

            $wasNew = ! $task->exists;
            $task->fill([
                'location_id' => $document->location_id,
                'area_id' => $receiptLine->to_area_id,
                'slot_id' => $receiptLine->to_slot_id,
                'status' => in_array($task->status, ['done', 'cancelled'], true) ? $task->status : 'open',
                'priority' => 'normal',
                'required_scan_mode' => 'optional',
                'queue_name' => 'putaway',
                'requested_by' => $document->requested_by ?: $userId,
                'target_qty' => $quantity,
                'meta' => [
                    'suggested_slot_id' => $destinationSlot?->id,
                    'suggested_slot_label' => $this->slotLabel($destinationSlot),
                ],
            ]);
            $task->save();

            if ($wasNew) {
                StorageTaskEvent::query()->create([
                    'business_id' => $document->business_id,
                    'task_id' => $task->id,
                    'event_type' => 'created',
                    'user_id' => $userId,
                    'payload' => [
                        'suggested_slot_id' => $destinationSlot?->id,
                    ],
                ]);
            }
        }

        StorageDocumentLine::query()
            ->where('business_id', $document->business_id)
            ->where('document_id', $document->id)
            ->when(! empty($activeLineIds), fn ($query) => $query->whereNotIn('id', $activeLineIds))
            ->delete();
    }

    protected function slotLabel(?StorageSlot $slot): string
    {
        if (! $slot) {
            return '—';
        }

        $slotCode = $slot->slot_code ?: trim(($slot->row ?: '') . '-' . ($slot->position ?: ''));
        $areaName = optional($slot->area)->name;

        return trim($slotCode . ($areaName ? ' — ' . $areaName : ''));
    }
}
