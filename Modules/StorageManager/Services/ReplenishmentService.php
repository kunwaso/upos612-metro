<?php

namespace Modules\StorageManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLine;
use Modules\StorageManager\Entities\StorageReplenishmentRule;
use Modules\StorageManager\Entities\StorageSlot;
use Modules\StorageManager\Entities\StorageSlotStock;
use Modules\StorageManager\Entities\StorageTask;
use Modules\StorageManager\Entities\StorageTaskEvent;
use RuntimeException;

class ReplenishmentService
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService
    ) {
    }

    public function queueForLocation(int $businessId, ?int $locationId = null): array
    {
        $rules = StorageReplenishmentRule::query()
            ->with(['product', 'variation', 'sourceArea', 'destinationArea', 'sourceSlot.area', 'destinationSlot.area'])
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->orderBy('location_id')
            ->orderBy('id')
            ->get();

        $documents = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'replenishment')
            ->where('source_type', 'replenishment_rule')
            ->whereNotIn('status', ['closed', 'completed', 'cancelled'])
            ->get()
            ->keyBy('source_id');

        $rows = $rules->map(function (StorageReplenishmentRule $rule) use ($documents) {
            $destinationQty = $this->availableQty(
                (int) $rule->business_id,
                (int) $rule->location_id,
                (int) $rule->product_id,
                $rule->variation_id ? (int) $rule->variation_id : null,
                $rule->destination_slot_id ? [(int) $rule->destination_slot_id] : null,
                $rule->destination_area_id ? [(int) $rule->destination_area_id] : null
            );
            $sourceQty = $this->availableQty(
                (int) $rule->business_id,
                (int) $rule->location_id,
                (int) $rule->product_id,
                $rule->variation_id ? (int) $rule->variation_id : null,
                $rule->source_slot_id ? [(int) $rule->source_slot_id] : null,
                $rule->source_area_id ? [(int) $rule->source_area_id] : null
            );
            $recommendedQty = $this->recommendedQty($rule, $destinationQty, $sourceQty);
            $document = $documents->get($rule->id);

            return [
                'rule_id' => (int) $rule->id,
                'location_id' => (int) $rule->location_id,
                'product_label' => (string) optional($rule->product)->name,
                'sku' => (string) (optional($rule->variation)->sub_sku ?: optional($rule->product)->sku ?: '—'),
                'source_label' => $this->slotLabel($rule->sourceSlot) ?: (string) optional($rule->sourceArea)->name,
                'destination_label' => $this->slotLabel($rule->destinationSlot) ?: (string) optional($rule->destinationArea)->name,
                'destination_qty' => $destinationQty,
                'source_qty' => $sourceQty,
                'min_qty' => (float) $rule->min_qty,
                'max_qty' => (float) $rule->max_qty,
                'recommended_qty' => $recommendedQty,
                'needs_replenishment' => $recommendedQty > 0,
                'document_id' => $document?->id,
                'document_status' => $document?->status,
            ];
        })->filter(fn (array $row) => $row['needs_replenishment'])->values();

        return [
            'queueSummary' => [
                'rule_count' => (int) $rows->count(),
                'open_documents' => (int) $rows->filter(fn (array $row) => ! empty($row['document_id']))->count(),
                'recommended_qty' => round((float) $rows->sum('recommended_qty'), 4),
            ],
            'rows' => $rows,
        ];
    }

    public function getWorkbench(int $businessId, int $ruleId, int $userId): array
    {
        $rule = StorageReplenishmentRule::query()
            ->with(['product', 'variation', 'sourceArea', 'destinationArea', 'sourceSlot.area', 'destinationSlot.area'])
            ->where('business_id', $businessId)
            ->findOrFail($ruleId);

        $context = $this->buildRuleContext($rule);
        if ($context['recommended_qty'] <= 0) {
            throw new RuntimeException('This replenishment rule does not currently require replenishment.');
        }

        $document = $this->ensureDocumentForRule($rule, $context, $userId);
        $document = $document->fresh([
            'lines.product',
            'lines.variation',
            'lines.fromArea',
            'lines.fromSlot',
            'lines.toArea',
            'lines.toSlot',
        ]);

        $sourceSlotOptions = $this->slotOptions($businessId, (int) $rule->location_id, $rule->source_area_id ? [(int) $rule->source_area_id] : null);
        $destinationSlotOptions = $this->slotOptions($businessId, (int) $rule->location_id, $rule->destination_area_id ? [(int) $rule->destination_area_id] : null);

        $lineRows = $document->lines->map(function (StorageDocumentLine $line) use ($context) {
            return [
                'id' => (int) $line->id,
                'product_label' => (string) optional($line->product)->name,
                'sku' => (string) (optional($line->variation)->sub_sku ?: optional($line->product)->sku ?: '—'),
                'qty' => (float) ($line->executed_qty ?: $line->expected_qty),
                'source_slot_id' => $line->from_slot_id,
                'source_slot_label' => $this->slotLabel($line->fromSlot),
                'destination_slot_id' => $line->to_slot_id,
                'destination_slot_label' => $this->slotLabel($line->toSlot),
                'source_available_qty' => (float) $context['source_qty'],
                'destination_qty' => (float) $context['destination_qty'],
                'recommended_qty' => (float) $context['recommended_qty'],
                'result_status' => (string) $line->result_status,
            ];
        })->values();

        return [
            'rule' => $rule,
            'document' => $document,
            'sourceSummary' => $context,
            'lineRows' => $lineRows,
            'sourceSlotOptions' => $sourceSlotOptions,
            'destinationSlotOptions' => $destinationSlotOptions,
        ];
    }

    public function completeReplenishment(int $businessId, int $ruleId, array $payload, int $userId): StorageDocument
    {
        $rule = StorageReplenishmentRule::query()
            ->where('business_id', $businessId)
            ->findOrFail($ruleId);

        $context = $this->buildRuleContext($rule);
        $document = $this->ensureDocumentForRule($rule, $context, $userId);
        if (in_array($document->status, ['closed', 'completed', 'cancelled'], true)) {
            throw new RuntimeException('This replenishment document is already closed.');
        }

        $lineInputs = (array) ($payload['lines'] ?? []);
        $document->loadMissing(['lines.tasks']);

        DB::transaction(function () use ($businessId, $document, $lineInputs, $rule, $context, $userId) {
            foreach ($document->lines as $line) {
                $input = (array) ($lineInputs[$line->id] ?? []);
                $sourceSlotId = (int) ($input['source_slot_id'] ?? $line->from_slot_id);
                $destinationSlotId = (int) ($input['destination_slot_id'] ?? $line->to_slot_id);
                $quantity = round((float) ($input['executed_qty'] ?? $line->expected_qty), 4);

                if ($sourceSlotId <= 0 || $destinationSlotId <= 0) {
                    throw new RuntimeException("Source and destination slots are required for replenishment line [{$line->id}].");
                }

                if ($quantity <= 0) {
                    throw new RuntimeException("Executed quantity must be greater than zero for replenishment line [{$line->id}].");
                }

                if ($quantity > (float) $context['recommended_qty']) {
                    throw new RuntimeException('Executed replenishment quantity cannot exceed the current recommended quantity.');
                }

                $bucket = $this->availableBucket(
                    $businessId,
                    (int) $rule->location_id,
                    (int) $line->product_id,
                    $line->variation_id ? (int) $line->variation_id : null,
                    $quantity,
                    [$sourceSlotId]
                );

                if (! $bucket) {
                    throw new RuntimeException("No single source bin has enough stock for replenishment line [{$line->id}].");
                }

                $sourceSlot = $this->validatedSlot($businessId, (int) $rule->location_id, $sourceSlotId);
                $destinationSlot = $this->validatedSlot($businessId, (int) $rule->location_id, $destinationSlotId);

                $this->inventoryMovementService->applyMovement([
                    'business_id' => $businessId,
                    'location_id' => $rule->location_id,
                    'document_id' => $document->id,
                    'document_line_id' => $line->id,
                    'task_id' => optional($line->tasks->first())->id,
                    'source_type' => 'replenishment_rule',
                    'source_id' => $rule->id,
                    'movement_type' => 'replenishment',
                    'direction' => 'move',
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'from_area_id' => $sourceSlot->area_id,
                    'to_area_id' => $destinationSlot->area_id,
                    'from_slot_id' => $sourceSlotId,
                    'to_slot_id' => $destinationSlotId,
                    'from_status' => 'available',
                    'to_status' => 'available',
                    'lot_number' => $bucket->lot_number,
                    'expiry_date' => optional($bucket->expiry_date)->toDateString(),
                    'quantity' => $quantity,
                    'unit_cost' => $line->unit_cost,
                    'reason_code' => 'replenishment',
                    'idempotency_key' => 'replenishment-' . $document->id . '-line-' . $line->id,
                    'created_by' => $userId,
                ]);

                $line->forceFill([
                    'from_area_id' => $sourceSlot->area_id,
                    'to_area_id' => $destinationSlot->area_id,
                    'from_slot_id' => $sourceSlotId,
                    'to_slot_id' => $destinationSlotId,
                    'executed_qty' => $quantity,
                    'variance_qty' => 0,
                    'inventory_status' => 'available',
                    'result_status' => 'completed',
                    'lot_number' => (string) $bucket->lot_number,
                    'expiry_date' => optional($bucket->expiry_date)->toDateString(),
                ])->save();

                foreach ($line->tasks as $task) {
                    $task->forceFill([
                        'status' => 'done',
                        'completed_qty' => $quantity,
                        'completed_at' => now(),
                        'meta' => array_merge((array) $task->meta, [
                            'source_slot_id' => $sourceSlotId,
                            'destination_slot_id' => $destinationSlotId,
                        ]),
                    ])->save();

                    StorageTaskEvent::query()->create([
                        'business_id' => $businessId,
                        'task_id' => $task->id,
                        'event_type' => 'completed',
                        'user_id' => $userId,
                        'payload' => [
                            'source_slot_id' => $sourceSlotId,
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
            ])->save();
        });

        return $document->fresh([
            'lines.product',
            'lines.variation',
            'lines.fromArea',
            'lines.fromSlot',
            'lines.toArea',
            'lines.toSlot',
        ]);
    }

    protected function ensureDocumentForRule(StorageReplenishmentRule $rule, array $context, int $userId): StorageDocument
    {
        $document = StorageDocument::query()
            ->where('business_id', $rule->business_id)
            ->where('document_type', 'replenishment')
            ->where('source_type', 'replenishment_rule')
            ->where('source_id', $rule->id)
            ->whereNotIn('status', ['closed', 'completed', 'cancelled'])
            ->first();

        if (! $document) {
            $document = new StorageDocument([
                'business_id' => $rule->business_id,
                'location_id' => $rule->location_id,
                'area_id' => $rule->destination_area_id ?: $rule->source_area_id,
                'document_no' => 'TMP-REP-' . uniqid(),
                'document_type' => 'replenishment',
                'source_type' => 'replenishment_rule',
                'source_id' => $rule->id,
                'source_ref' => 'RULE-' . $rule->id,
                'status' => 'open',
                'workflow_state' => 'suggested',
                'execution_mode' => 'strict',
                'sync_status' => 'not_required',
                'requested_by' => $userId,
                'created_by' => $userId,
                'notes' => 'Generated from replenishment rule ' . $rule->id,
                'meta' => [
                    'product_label' => optional($rule->product)->name,
                ],
            ]);
            $document->save();
            $document->forceFill([
                'document_no' => 'REP-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
            ])->save();
        }

        $sourceBucket = $this->availableBucket(
            (int) $rule->business_id,
            (int) $rule->location_id,
            (int) $rule->product_id,
            $rule->variation_id ? (int) $rule->variation_id : null,
            (float) $context['recommended_qty'],
            $rule->source_slot_id ? [(int) $rule->source_slot_id] : null
        );
        $sourceSlotId = $sourceBucket?->slot_id ?: $rule->source_slot_id;
        $sourceAreaId = $sourceBucket?->area_id ?: $rule->source_area_id;
        $destinationSlotId = $rule->destination_slot_id;
        $destinationAreaId = $rule->destination_area_id ?: ($destinationSlotId ? StorageSlot::query()->where('id', $destinationSlotId)->value('area_id') : null);

        $line = StorageDocumentLine::query()->updateOrCreate(
            [
                'business_id' => $rule->business_id,
                'document_id' => $document->id,
                'source_line_id' => $rule->id,
            ],
            [
                'line_no' => 1,
                'product_id' => $rule->product_id,
                'variation_id' => $rule->variation_id,
                'from_area_id' => $sourceAreaId,
                'to_area_id' => $destinationAreaId,
                'from_slot_id' => $sourceSlotId,
                'to_slot_id' => $destinationSlotId,
                'expected_qty' => (float) $context['recommended_qty'],
                'executed_qty' => (float) $context['recommended_qty'],
                'variance_qty' => 0,
                'unit_cost' => 0,
                'inventory_status' => 'available',
                'result_status' => 'pending',
                'lot_number' => (string) ($sourceBucket?->lot_number ?: ''),
                'expiry_date' => optional($sourceBucket?->expiry_date)->toDateString(),
                'meta' => [
                    'destination_qty' => $context['destination_qty'],
                    'source_qty' => $context['source_qty'],
                ],
            ]
        );

        $task = StorageTask::query()->firstOrNew([
            'business_id' => $rule->business_id,
            'document_id' => $document->id,
            'document_line_id' => $line->id,
            'task_type' => 'replenishment',
        ]);
        $wasNew = ! $task->exists;
        $task->fill([
            'location_id' => $rule->location_id,
            'area_id' => $destinationAreaId,
            'slot_id' => $destinationSlotId,
            'status' => in_array($task->status, ['done', 'cancelled'], true) ? $task->status : 'open',
            'priority' => 'normal',
            'required_scan_mode' => 'optional',
            'queue_name' => 'replenishment',
            'requested_by' => $userId,
            'target_qty' => (float) $context['recommended_qty'],
            'meta' => [
                'suggested_source_slot_id' => $sourceSlotId,
                'suggested_destination_slot_id' => $destinationSlotId,
            ],
        ]);
        $task->save();

        if ($wasNew) {
            StorageTaskEvent::query()->create([
                'business_id' => $rule->business_id,
                'task_id' => $task->id,
                'event_type' => 'created',
                'user_id' => $userId,
                'payload' => [
                    'suggested_source_slot_id' => $sourceSlotId,
                    'suggested_destination_slot_id' => $destinationSlotId,
                ],
            ]);
        }

        return $document;
    }

    protected function buildRuleContext(StorageReplenishmentRule $rule): array
    {
        $destinationQty = $this->availableQty(
            (int) $rule->business_id,
            (int) $rule->location_id,
            (int) $rule->product_id,
            $rule->variation_id ? (int) $rule->variation_id : null,
            $rule->destination_slot_id ? [(int) $rule->destination_slot_id] : null,
            $rule->destination_area_id ? [(int) $rule->destination_area_id] : null
        );
        $sourceQty = $this->availableQty(
            (int) $rule->business_id,
            (int) $rule->location_id,
            (int) $rule->product_id,
            $rule->variation_id ? (int) $rule->variation_id : null,
            $rule->source_slot_id ? [(int) $rule->source_slot_id] : null,
            $rule->source_area_id ? [(int) $rule->source_area_id] : null
        );

        return [
            'rule_id' => (int) $rule->id,
            'location_id' => (int) $rule->location_id,
            'product_label' => (string) optional($rule->product)->name,
            'sku' => (string) (optional($rule->variation)->sub_sku ?: optional($rule->product)->sku ?: '—'),
            'source_label' => $this->slotLabel($rule->sourceSlot) ?: (string) optional($rule->sourceArea)->name,
            'destination_label' => $this->slotLabel($rule->destinationSlot) ?: (string) optional($rule->destinationArea)->name,
            'source_qty' => $sourceQty,
            'destination_qty' => $destinationQty,
            'min_qty' => (float) $rule->min_qty,
            'max_qty' => (float) $rule->max_qty,
            'recommended_qty' => $this->recommendedQty($rule, $destinationQty, $sourceQty),
        ];
    }

    protected function availableQty(
        int $businessId,
        int $locationId,
        int $productId,
        ?int $variationId,
        ?array $slotIds = null,
        ?array $areaIds = null
    ): float {
        return round((float) StorageSlotStock::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('inventory_status', 'available')
            ->when($variationId, fn ($query) => $query->where('variation_id', $variationId), fn ($query) => $query->whereNull('variation_id'))
            ->when(! empty($slotIds), fn ($query) => $query->whereIn('slot_id', $slotIds))
            ->when(! empty($areaIds), fn ($query) => $query->whereIn('area_id', $areaIds))
            ->sum('qty_on_hand'), 4);
    }

    protected function recommendedQty(StorageReplenishmentRule $rule, float $destinationQty, float $sourceQty): float
    {
        if ($destinationQty >= (float) $rule->min_qty || $sourceQty <= 0) {
            return 0.0;
        }

        $targetQty = $rule->replenish_qty !== null && (float) $rule->replenish_qty > 0
            ? (float) $rule->replenish_qty
            : max((float) $rule->max_qty - $destinationQty, 0);

        return round(min($targetQty, $sourceQty), 4);
    }

    protected function availableBucket(
        int $businessId,
        int $locationId,
        int $productId,
        ?int $variationId,
        float $requiredQty,
        ?array $slotIds = null
    ): ?StorageSlotStock {
        return StorageSlotStock::query()
            ->with('slot.area')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('inventory_status', 'available')
            ->when($variationId, fn ($query) => $query->where('variation_id', $variationId), fn ($query) => $query->whereNull('variation_id'))
            ->when(! empty($slotIds), fn ($query) => $query->whereIn('slot_id', array_values(array_filter(array_map('intval', $slotIds)))))
            ->where('qty_on_hand', '>=', $requiredQty)
            ->orderByDesc('qty_on_hand')
            ->orderBy('last_movement_at')
            ->first();
    }

    protected function validatedSlot(int $businessId, int $locationId, int $slotId): StorageSlot
    {
        $slot = StorageSlot::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->active()
            ->find($slotId);

        if (! $slot) {
            throw new RuntimeException("Slot [{$slotId}] is invalid for location [{$locationId}].");
        }

        return $slot;
    }

    protected function slotOptions(int $businessId, int $locationId, ?array $areaIds = null): array
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

    protected function slotLabel(?StorageSlot $slot): string
    {
        if (! $slot) {
            return '';
        }

        $slotCode = $slot->slot_code ?: trim(($slot->row ?: '') . '-' . ($slot->position ?: ''));
        $areaName = optional($slot->area)->name;

        return trim($slotCode . ($areaName ? ' — ' . $areaName : ''));
    }
}
