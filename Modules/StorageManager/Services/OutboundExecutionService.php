<?php

namespace Modules\StorageManager\Services;

use App\Transaction;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLine;
use Modules\StorageManager\Entities\StorageDocumentLink;
use Modules\StorageManager\Entities\StorageLocationSetting;
use Modules\StorageManager\Entities\StorageSlot;
use Modules\StorageManager\Entities\StorageSlotStock;
use Modules\StorageManager\Entities\StorageTask;
use Modules\StorageManager\Entities\StorageTaskEvent;
use RuntimeException;

class OutboundExecutionService
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService,
        protected PutawayService $putawayService,
        protected ReconciliationService $reconciliationService,
        protected TransactionUtil $transactionUtil
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
                'summary' => [
                    'enabled_location_count' => 0,
                    'pick_count' => 0,
                    'pack_count' => 0,
                    'ship_count' => 0,
                ],
                'pickRows' => collect(),
                'packRows' => collect(),
                'shipRows' => collect(),
            ];
        }

        $pickDocuments = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'pick')
            ->get()
            ->keyBy('source_id');

        $packDocuments = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'pack')
            ->get()
            ->keyBy('source_id');

        $shipDocuments = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'ship')
            ->get()
            ->keyBy('source_id');

        $salesOrders = Transaction::query()
            ->with(['contact', 'location', 'sell_lines.product', 'sell_lines.variations'])
            ->where('business_id', $businessId)
            ->where('type', 'sales_order')
            ->whereIn('location_id', $enabledLocationIds)
            ->whereIn('status', ['ordered', 'partial'])
            ->where(function ($query) {
                $query->whereNull('sub_status')
                    ->orWhere('sub_status', '!=', 'on_hold');
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Transaction $order) => $this->remainingSalesOrderLines($order)->isNotEmpty())
            ->values();

        $pickRows = $salesOrders
            ->map(function (Transaction $order) use ($pickDocuments, $settings) {
                return $this->outboundRow(
                    $order,
                    $pickDocuments->get($order->id),
                    (string) ($settings->get($order->location_id)?->execution_mode ?: 'off')
                );
            })
            ->values();

        $packRows = $salesOrders
            ->filter(fn (Transaction $order) => in_array((string) ($pickDocuments->get($order->id)?->status ?? ''), ['closed', 'completed'], true))
            ->map(function (Transaction $order) use ($packDocuments, $settings) {
                return $this->outboundRow(
                    $order,
                    $packDocuments->get($order->id),
                    (string) ($settings->get($order->location_id)?->execution_mode ?: 'off')
                );
            })
            ->values();

        $shipRows = $salesOrders
            ->filter(fn (Transaction $order) => in_array((string) ($packDocuments->get($order->id)?->status ?? ''), ['closed', 'completed'], true))
            ->map(function (Transaction $order) use ($shipDocuments, $settings) {
                return $this->outboundRow(
                    $order,
                    $shipDocuments->get($order->id),
                    (string) ($settings->get($order->location_id)?->execution_mode ?: 'off')
                );
            })
            ->values();

        return [
            'summary' => [
                'enabled_location_count' => count($enabledLocationIds),
                'pick_count' => (int) $pickRows->count(),
                'pack_count' => (int) $packRows->count(),
                'ship_count' => (int) $shipRows->count(),
            ],
            'pickRows' => $pickRows,
            'packRows' => $packRows,
            'shipRows' => $shipRows,
        ];
    }

    public function getPickWorkbench(int $businessId, int $salesOrderId, int $userId): array
    {
        $order = $this->loadSalesOrder($businessId, $salesOrderId);
        $settings = $this->locationSettingForOrder($businessId, (int) $order->location_id);
        $document = $this->ensurePickDocument($businessId, $order, $settings, $userId);
        $this->syncPickLines($document, $order, $userId);

        $document = $document->fresh(['lines.product', 'lines.variation', 'lines.fromSlot', 'lines.toSlot']);
        $sourceSlotOptions = $this->putawayService->slotOptionsForLocation($businessId, (int) $order->location_id);

        $lineRows = $document->lines->map(function (StorageDocumentLine $line) use ($businessId, $order) {
            $bucket = $this->pickAvailableBucket(
                $businessId,
                (int) $order->location_id,
                (int) $line->product_id,
                $line->variation_id ? (int) $line->variation_id : null,
                (float) $line->expected_qty,
                'available',
                $line->from_slot_id ? [(int) $line->from_slot_id] : null
            );

            return [
                'id' => (int) $line->id,
                'product_label' => (string) optional($line->product)->name,
                'sku' => (string) (optional($line->variation)->sub_sku ?: optional($line->product)->sku ?: '—'),
                'qty' => (float) $line->expected_qty,
                'source_slot_id' => $line->from_slot_id ?: $bucket?->slot_id,
                'source_slot_label' => $this->slotLabel($line->fromSlot ?: $bucket?->slot),
                'available_qty' => round((float) ($bucket?->qty_on_hand ?? 0), 4),
                'lot_number' => (string) ($line->lot_number ?: ($bucket?->lot_number ?: '')),
                'expiry_date' => optional($line->expiry_date ?: $bucket?->expiry_date)->toDateString(),
                'result_status' => (string) $line->result_status,
            ];
        })->values();

        return [
            'document' => $document,
            'orderSummary' => $this->orderSummary($order, (string) $settings->execution_mode, [
                'can_confirm' => ! in_array($document->status, ['closed', 'completed', 'cancelled'], true),
            ]),
            'lineRows' => $lineRows,
            'sourceSlotOptions' => $sourceSlotOptions,
        ];
    }

    public function confirmPick(int $businessId, StorageDocument $document, array $payload, int $userId): StorageDocument
    {
        if ($document->document_type !== 'pick') {
            throw new RuntimeException('Only pick documents can be confirmed here.');
        }

        if (in_array($document->status, ['closed', 'completed', 'cancelled'], true)) {
            throw new RuntimeException('This pick document is already closed.');
        }

        $order = $this->loadSalesOrder($businessId, (int) $document->source_id);
        $settings = $this->locationSettingForOrder($businessId, (int) $order->location_id);
        $lineInputs = (array) ($payload['lines'] ?? []);
        $document->loadMissing(['lines.tasks']);

        DB::transaction(function () use ($businessId, $document, $order, $lineInputs, $userId) {
            foreach ($document->lines as $line) {
                $input = (array) ($lineInputs[$line->id] ?? []);
                $sourceSlotId = (int) ($input['source_slot_id'] ?? $line->from_slot_id);
                $quantity = round((float) ($input['executed_qty'] ?? $line->expected_qty), 4);

                if ($sourceSlotId <= 0) {
                    throw new RuntimeException("Source slot is required for pick line [{$line->id}].");
                }

                if ($quantity !== round((float) $line->expected_qty, 4)) {
                    throw new RuntimeException('This phase requires pick quantity to match the sales order quantity exactly.');
                }

                $sourceSlot = $this->validatedSlot($businessId, (int) $order->location_id, $sourceSlotId);
                $bucket = $this->pickAvailableBucket(
                    $businessId,
                    (int) $order->location_id,
                    (int) $line->product_id,
                    $line->variation_id ? (int) $line->variation_id : null,
                    $quantity,
                    'available',
                    [$sourceSlotId]
                );

                if (! $bucket) {
                    throw new RuntimeException("No single available bin or lot has enough stock to pick line [{$line->id}].");
                }

                $this->inventoryMovementService->applyMovement([
                    'business_id' => $businessId,
                    'location_id' => $order->location_id,
                    'document_id' => $document->id,
                    'document_line_id' => $line->id,
                    'source_type' => 'sales_order',
                    'source_id' => $order->id,
                    'source_line_id' => $line->source_line_id,
                    'movement_type' => 'pick',
                    'direction' => 'move',
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'from_area_id' => $sourceSlot->area_id,
                    'to_area_id' => $sourceSlot->area_id,
                    'from_slot_id' => $sourceSlotId,
                    'to_slot_id' => $sourceSlotId,
                    'from_status' => 'available',
                    'to_status' => 'picked',
                    'lot_number' => $bucket->lot_number,
                    'expiry_date' => optional($bucket->expiry_date)->toDateString(),
                    'quantity' => $quantity,
                    'reason_code' => 'sales_order_pick',
                    'idempotency_key' => 'pick-' . $document->id . '-line-' . $line->id,
                    'created_by' => $userId,
                ]);

                $line->forceFill([
                    'from_area_id' => $sourceSlot->area_id,
                    'to_area_id' => $sourceSlot->area_id,
                    'from_slot_id' => $sourceSlotId,
                    'to_slot_id' => $sourceSlotId,
                    'executed_qty' => $quantity,
                    'inventory_status' => 'picked',
                    'result_status' => 'completed',
                    'lot_number' => $bucket->lot_number,
                    'expiry_date' => $bucket->expiry_date,
                ])->save();

                foreach ($line->tasks as $task) {
                    $this->completeTask($task, $userId, $quantity, [
                        'source_slot_id' => $sourceSlotId,
                    ]);
                }
            }

            $document->forceFill([
                'status' => 'closed',
                'workflow_state' => 'picked',
                'completed_at' => now(),
                'closed_at' => now(),
                'closed_by' => $userId,
            ])->save();
        });

        $this->reconciliationService->reconcileLocation((int) $document->business_id, (int) $document->location_id);
        $this->ensurePackDocument($businessId, $order, $document->fresh(['lines.product', 'lines.variation']), $settings, $userId);

        return $document->fresh(['lines.product', 'lines.variation', 'lines.fromSlot', 'lines.toSlot']);
    }

    public function getPackWorkbench(int $businessId, int $salesOrderId, int $userId): array
    {
        $order = $this->loadSalesOrder($businessId, $salesOrderId);
        $settings = $this->locationSettingForOrder($businessId, (int) $order->location_id);
        $pickDocument = $this->ensurePickDocument($businessId, $order, $settings, $userId);
        $this->syncPickLines($pickDocument, $order, $userId);

        $packDocument = $this->ensurePackDocument($businessId, $order, $pickDocument->fresh(['lines.product', 'lines.variation']), $settings, $userId);
        $packDocument = $packDocument->fresh(['lines.product', 'lines.variation', 'lines.fromSlot', 'lines.toSlot']);

        $packSlotOptions = $this->putawayService->slotOptionsForLocation(
            $businessId,
            (int) $order->location_id,
            $settings->default_packing_area_id ? [(int) $settings->default_packing_area_id] : null
        );
        if (empty($packSlotOptions)) {
            $packSlotOptions = $this->putawayService->slotOptionsForLocation($businessId, (int) $order->location_id);
        }
        $sourceSlotOptions = $this->putawayService->slotOptionsForLocation($businessId, (int) $order->location_id);

        $lineRows = $packDocument->lines->map(function (StorageDocumentLine $line) use ($businessId, $order) {
            $bucket = $this->pickAvailableBucket(
                $businessId,
                (int) $order->location_id,
                (int) $line->product_id,
                $line->variation_id ? (int) $line->variation_id : null,
                (float) $line->expected_qty,
                'picked',
                $line->from_slot_id ? [(int) $line->from_slot_id] : null
            );

            return [
                'id' => (int) $line->id,
                'product_label' => (string) optional($line->product)->name,
                'sku' => (string) (optional($line->variation)->sub_sku ?: optional($line->product)->sku ?: '—'),
                'qty' => (float) $line->expected_qty,
                'source_slot_id' => $line->from_slot_id ?: $bucket?->slot_id,
                'source_slot_label' => $this->slotLabel($line->fromSlot ?: $bucket?->slot),
                'pack_slot_id' => $line->to_slot_id,
                'pack_slot_label' => $this->slotLabel($line->toSlot),
                'available_qty' => round((float) ($bucket?->qty_on_hand ?? 0), 4),
                'lot_number' => (string) ($line->lot_number ?: ($bucket?->lot_number ?: '')),
                'expiry_date' => optional($line->expiry_date ?: $bucket?->expiry_date)->toDateString(),
                'result_status' => (string) $line->result_status,
            ];
        })->values();

        return [
            'document' => $packDocument,
            'orderSummary' => $this->orderSummary($order, (string) $settings->execution_mode, [
                'pick_document_no' => $pickDocument->document_no,
                'pick_document_status' => $pickDocument->status,
                'can_confirm' => in_array((string) $pickDocument->status, ['closed', 'completed'], true)
                    && ! in_array($packDocument->status, ['closed', 'completed', 'cancelled'], true),
            ]),
            'lineRows' => $lineRows,
            'sourceSlotOptions' => $sourceSlotOptions,
            'packSlotOptions' => $packSlotOptions,
        ];
    }

    public function confirmPack(int $businessId, StorageDocument $document, array $payload, int $userId, bool $allowShippingStatusUpdate = false): StorageDocument
    {
        if ($document->document_type !== 'pack') {
            throw new RuntimeException('Only pack documents can be confirmed here.');
        }

        if (in_array($document->status, ['closed', 'completed', 'cancelled'], true)) {
            throw new RuntimeException('This pack document is already closed.');
        }

        $order = $this->loadSalesOrder($businessId, (int) $document->source_id);
        $settings = $this->locationSettingForOrder($businessId, (int) $order->location_id);
        $pickDocument = $this->ensurePickDocument($businessId, $order, $settings, $userId);
        if (! in_array((string) $pickDocument->status, ['closed', 'completed'], true)) {
            throw new RuntimeException('The pick step must be completed before packing can begin.');
        }

        $lineInputs = (array) ($payload['lines'] ?? []);
        $document->loadMissing(['lines.tasks']);

        DB::transaction(function () use ($businessId, $document, $order, $lineInputs, $userId, $allowShippingStatusUpdate) {
            foreach ($document->lines as $line) {
                $input = (array) ($lineInputs[$line->id] ?? []);
                $sourceSlotId = (int) ($input['source_slot_id'] ?? $line->from_slot_id);
                $packSlotId = (int) ($input['pack_slot_id'] ?? $line->to_slot_id);
                $quantity = round((float) ($input['executed_qty'] ?? $line->expected_qty), 4);

                if ($sourceSlotId <= 0 || $packSlotId <= 0) {
                    throw new RuntimeException("Source slot and packing slot are required for pack line [{$line->id}].");
                }

                if ($quantity !== round((float) $line->expected_qty, 4)) {
                    throw new RuntimeException('This phase requires pack quantity to match the picked quantity exactly.');
                }

                $sourceSlot = $this->validatedSlot($businessId, (int) $order->location_id, $sourceSlotId);
                $packSlot = $this->validatedSlot($businessId, (int) $order->location_id, $packSlotId);
                $bucket = $this->pickAvailableBucket(
                    $businessId,
                    (int) $order->location_id,
                    (int) $line->product_id,
                    $line->variation_id ? (int) $line->variation_id : null,
                    $quantity,
                    'picked',
                    [$sourceSlotId]
                );

                if (! $bucket) {
                    throw new RuntimeException("No single picked bin or lot has enough stock to pack line [{$line->id}].");
                }

                $this->inventoryMovementService->applyMovement([
                    'business_id' => $businessId,
                    'location_id' => $order->location_id,
                    'document_id' => $document->id,
                    'document_line_id' => $line->id,
                    'source_type' => 'sales_order',
                    'source_id' => $order->id,
                    'source_line_id' => $line->source_line_id,
                    'movement_type' => 'pack',
                    'direction' => 'move',
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'from_area_id' => $sourceSlot->area_id,
                    'to_area_id' => $packSlot->area_id,
                    'from_slot_id' => $sourceSlotId,
                    'to_slot_id' => $packSlotId,
                    'from_status' => 'picked',
                    'to_status' => 'packed',
                    'lot_number' => $bucket->lot_number,
                    'expiry_date' => optional($bucket->expiry_date)->toDateString(),
                    'quantity' => $quantity,
                    'reason_code' => 'sales_order_pack',
                    'idempotency_key' => 'pack-' . $document->id . '-line-' . $line->id,
                    'created_by' => $userId,
                ]);

                $line->forceFill([
                    'from_area_id' => $sourceSlot->area_id,
                    'to_area_id' => $packSlot->area_id,
                    'from_slot_id' => $sourceSlotId,
                    'to_slot_id' => $packSlotId,
                    'executed_qty' => $quantity,
                    'inventory_status' => 'packed',
                    'result_status' => 'completed',
                    'lot_number' => $bucket->lot_number,
                    'expiry_date' => $bucket->expiry_date,
                ])->save();

                foreach ($line->tasks as $task) {
                    $this->completeTask($task, $userId, $quantity, [
                        'source_slot_id' => $sourceSlotId,
                        'pack_slot_id' => $packSlotId,
                    ]);
                }
            }

            $document->forceFill([
                'status' => 'closed',
                'workflow_state' => 'packed',
                'completed_at' => now(),
                'closed_at' => now(),
                'closed_by' => $userId,
            ])->save();

            $this->updateOrderShippingStatus($order, 'packed', $userId, $allowShippingStatusUpdate);
        });

        $this->reconciliationService->reconcileLocation((int) $document->business_id, (int) $document->location_id);
        $this->ensureShipDocument($businessId, $order, $document->fresh(['lines.product', 'lines.variation']), $settings, $userId);

        return $document->fresh(['lines.product', 'lines.variation', 'lines.fromSlot', 'lines.toSlot']);
    }

    public function getShipWorkbench(int $businessId, int $salesOrderId, int $userId): array
    {
        $order = $this->loadSalesOrder($businessId, $salesOrderId);
        $settings = $this->locationSettingForOrder($businessId, (int) $order->location_id);
        $pickDocument = $this->ensurePickDocument($businessId, $order, $settings, $userId);
        $this->syncPickLines($pickDocument, $order, $userId);
        $packDocument = $this->ensurePackDocument($businessId, $order, $pickDocument->fresh(['lines.product', 'lines.variation']), $settings, $userId);
        $shipDocument = $this->ensureShipDocument($businessId, $order, $packDocument->fresh(['lines.product', 'lines.variation']), $settings, $userId);
        $shipDocument = $shipDocument->fresh(['lines.product', 'lines.variation', 'lines.fromSlot', 'lines.toSlot']);

        $dispatchSlotOptions = $this->putawayService->slotOptionsForLocation(
            $businessId,
            (int) $order->location_id,
            $settings->default_dispatch_area_id ? [(int) $settings->default_dispatch_area_id] : null
        );
        if (empty($dispatchSlotOptions)) {
            $dispatchSlotOptions = $this->putawayService->slotOptionsForLocation($businessId, (int) $order->location_id);
        }
        $sourceSlotOptions = $this->putawayService->slotOptionsForLocation($businessId, (int) $order->location_id);

        $lineRows = $shipDocument->lines->map(function (StorageDocumentLine $line) use ($businessId, $order) {
            $bucket = $this->pickAvailableBucket(
                $businessId,
                (int) $order->location_id,
                (int) $line->product_id,
                $line->variation_id ? (int) $line->variation_id : null,
                (float) $line->expected_qty,
                'packed',
                $line->from_slot_id ? [(int) $line->from_slot_id] : null
            );

            return [
                'id' => (int) $line->id,
                'product_label' => (string) optional($line->product)->name,
                'sku' => (string) (optional($line->variation)->sub_sku ?: optional($line->product)->sku ?: '—'),
                'qty' => (float) $line->expected_qty,
                'source_slot_id' => $line->from_slot_id ?: $bucket?->slot_id,
                'source_slot_label' => $this->slotLabel($line->fromSlot ?: $bucket?->slot),
                'dispatch_slot_id' => $line->to_slot_id,
                'dispatch_slot_label' => $this->slotLabel($line->toSlot),
                'available_qty' => round((float) ($bucket?->qty_on_hand ?? 0), 4),
                'lot_number' => (string) ($line->lot_number ?: ($bucket?->lot_number ?: '')),
                'expiry_date' => optional($line->expiry_date ?: $bucket?->expiry_date)->toDateString(),
                'result_status' => (string) $line->result_status,
            ];
        })->values();

        return [
            'document' => $shipDocument,
            'orderSummary' => $this->orderSummary($order, (string) $settings->execution_mode, [
                'pack_document_no' => $packDocument->document_no,
                'pack_document_status' => $packDocument->status,
                'can_confirm' => in_array((string) $packDocument->status, ['closed', 'completed'], true)
                    && ! in_array($shipDocument->status, ['closed', 'completed', 'cancelled'], true),
            ]),
            'lineRows' => $lineRows,
            'sourceSlotOptions' => $sourceSlotOptions,
            'dispatchSlotOptions' => $dispatchSlotOptions,
        ];
    }

    public function confirmShip(int $businessId, StorageDocument $document, array $payload, int $userId, bool $allowShippingStatusUpdate = false): StorageDocument
    {
        if ($document->document_type !== 'ship') {
            throw new RuntimeException('Only ship documents can be confirmed here.');
        }

        if (in_array($document->status, ['closed', 'completed', 'cancelled'], true)) {
            throw new RuntimeException('This ship document is already closed.');
        }

        $order = $this->loadSalesOrder($businessId, (int) $document->source_id);
        $settings = $this->locationSettingForOrder($businessId, (int) $order->location_id);
        $packDocument = $this->ensurePackDocument(
            $businessId,
            $order,
            $this->ensurePickDocument($businessId, $order, $settings, $userId)->fresh(['lines.product', 'lines.variation']),
            $settings,
            $userId
        );

        if (! in_array((string) $packDocument->status, ['closed', 'completed'], true)) {
            throw new RuntimeException('The pack step must be completed before shipping can begin.');
        }

        $lineInputs = (array) ($payload['lines'] ?? []);
        $document->loadMissing(['lines.tasks']);

        DB::transaction(function () use ($businessId, $document, $order, $lineInputs, $userId, $allowShippingStatusUpdate) {
            foreach ($document->lines as $line) {
                $input = (array) ($lineInputs[$line->id] ?? []);
                $sourceSlotId = (int) ($input['source_slot_id'] ?? $line->from_slot_id);
                $dispatchSlotId = (int) ($input['dispatch_slot_id'] ?? $line->to_slot_id);
                $quantity = round((float) ($input['executed_qty'] ?? $line->expected_qty), 4);

                if ($sourceSlotId <= 0 || $dispatchSlotId <= 0) {
                    throw new RuntimeException("Source slot and dispatch slot are required for ship line [{$line->id}].");
                }

                if ($quantity !== round((float) $line->expected_qty, 4)) {
                    throw new RuntimeException('This phase requires ship quantity to match the packed quantity exactly.');
                }

                $sourceSlot = $this->validatedSlot($businessId, (int) $order->location_id, $sourceSlotId);
                $dispatchSlot = $this->validatedSlot($businessId, (int) $order->location_id, $dispatchSlotId);
                $bucket = $this->pickAvailableBucket(
                    $businessId,
                    (int) $order->location_id,
                    (int) $line->product_id,
                    $line->variation_id ? (int) $line->variation_id : null,
                    $quantity,
                    'packed',
                    [$sourceSlotId]
                );

                if (! $bucket) {
                    throw new RuntimeException("No single packed bin or lot has enough stock to ship line [{$line->id}].");
                }

                $this->inventoryMovementService->applyMovement([
                    'business_id' => $businessId,
                    'location_id' => $order->location_id,
                    'document_id' => $document->id,
                    'document_line_id' => $line->id,
                    'source_type' => 'sales_order',
                    'source_id' => $order->id,
                    'source_line_id' => $line->source_line_id,
                    'movement_type' => 'ship',
                    'direction' => 'move',
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'from_area_id' => $sourceSlot->area_id,
                    'to_area_id' => $dispatchSlot->area_id,
                    'from_slot_id' => $sourceSlotId,
                    'to_slot_id' => $dispatchSlotId,
                    'from_status' => 'packed',
                    'to_status' => 'staged_out',
                    'lot_number' => $bucket->lot_number,
                    'expiry_date' => optional($bucket->expiry_date)->toDateString(),
                    'quantity' => $quantity,
                    'reason_code' => 'sales_order_ship',
                    'idempotency_key' => 'ship-' . $document->id . '-line-' . $line->id,
                    'created_by' => $userId,
                ]);

                $line->forceFill([
                    'from_area_id' => $sourceSlot->area_id,
                    'to_area_id' => $dispatchSlot->area_id,
                    'from_slot_id' => $sourceSlotId,
                    'to_slot_id' => $dispatchSlotId,
                    'executed_qty' => $quantity,
                    'inventory_status' => 'staged_out',
                    'result_status' => 'completed',
                    'lot_number' => $bucket->lot_number,
                    'expiry_date' => $bucket->expiry_date,
                ])->save();

                foreach ($line->tasks as $task) {
                    $this->completeTask($task, $userId, $quantity, [
                        'source_slot_id' => $sourceSlotId,
                        'dispatch_slot_id' => $dispatchSlotId,
                    ]);
                }
            }

            $document->forceFill([
                'status' => 'closed',
                'workflow_state' => 'shipped',
                'completed_at' => now(),
                'closed_at' => now(),
                'closed_by' => $userId,
            ])->save();

            $this->updateOrderShippingStatus($order, 'shipped', $userId, $allowShippingStatusUpdate);
        });

        $this->reconciliationService->reconcileLocation((int) $document->business_id, (int) $document->location_id);

        return $document->fresh(['lines.product', 'lines.variation', 'lines.fromSlot', 'lines.toSlot']);
    }

    protected function ensurePickDocument(int $businessId, Transaction $order, StorageLocationSetting $settings, int $userId): StorageDocument
    {
        $document = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'pick')
            ->where('source_type', 'sales_order')
            ->where('source_id', $order->id)
            ->first();

        if (! $document) {
            $sourceRef = (string) ($order->ref_no ?: $order->invoice_no ?: ('SO-' . $order->id));
            $document = new StorageDocument([
                'business_id' => $businessId,
                'location_id' => $order->location_id,
                'area_id' => null,
                'document_no' => 'TMP-PICK-' . Str::uuid(),
                'document_type' => 'pick',
                'source_type' => 'sales_order',
                'source_id' => $order->id,
                'source_ref' => $sourceRef,
                'status' => 'open',
                'workflow_state' => 'pending_pick',
                'execution_mode' => $settings->execution_mode,
                'sync_status' => 'not_required',
                'requested_by' => $order->created_by ?: $userId,
                'created_by' => $userId,
                'notes' => 'Generated from sales order ' . $sourceRef,
                'meta' => [
                    'location_name' => optional($order->location)->name,
                    'customer_name' => optional($order->contact)->supplier_business_name ?: optional($order->contact)->name,
                ],
            ]);
            $document->save();
            $document->forceFill([
                'document_no' => 'PICK-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
            ])->save();
        }

        StorageDocumentLink::query()->updateOrCreate(
            [
                'business_id' => $businessId,
                'document_id' => $document->id,
                'linked_system' => 'upos',
                'link_role' => 'source',
            ],
            [
                'linked_type' => 'sales_order',
                'linked_id' => $order->id,
                'linked_ref' => (string) ($order->ref_no ?: $order->invoice_no ?: $order->id),
                'sync_status' => 'not_required',
                'meta' => [
                    'source_status' => $order->status,
                    'shipping_status' => $order->shipping_status,
                ],
            ]
        );

        return $document;
    }

    protected function ensurePackDocument(int $businessId, Transaction $order, StorageDocument $pickDocument, StorageLocationSetting $settings, int $userId): StorageDocument
    {
        $document = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'pack')
            ->where('source_type', 'sales_order')
            ->where('source_id', $order->id)
            ->first();

        if (! $document) {
            $document = new StorageDocument([
                'business_id' => $businessId,
                'location_id' => $order->location_id,
                'area_id' => $settings->default_packing_area_id,
                'parent_document_id' => $pickDocument->id,
                'document_no' => 'TMP-PACK-' . Str::uuid(),
                'document_type' => 'pack',
                'source_type' => 'sales_order',
                'source_id' => $order->id,
                'source_ref' => $pickDocument->source_ref,
                'status' => 'open',
                'workflow_state' => 'pending_pack',
                'execution_mode' => $settings->execution_mode,
                'sync_status' => 'not_required',
                'requested_by' => $pickDocument->requested_by,
                'created_by' => $userId,
                'notes' => 'Generated from pick document ' . $pickDocument->document_no,
                'meta' => [
                    'location_name' => optional($order->location)->name,
                    'customer_name' => optional($order->contact)->supplier_business_name ?: optional($order->contact)->name,
                    'pick_document_id' => $pickDocument->id,
                ],
            ]);
            $document->save();
            $document->forceFill([
                'document_no' => 'PACK-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
            ])->save();
        }

        $packingSlotId = $this->defaultAreaSlotId($businessId, (int) $order->location_id, $settings->default_packing_area_id ? (int) $settings->default_packing_area_id : null);
        $packingAreaId = $packingSlotId ? StorageSlot::query()->where('id', $packingSlotId)->value('area_id') : $settings->default_packing_area_id;

        $this->syncChildLines(
            $document,
            $pickDocument,
            $userId,
            'pack',
            'pack',
            $packingAreaId,
            $packingSlotId,
            'packed',
            'suggested_pack_slot_id'
        );

        return $document->fresh(['lines.product', 'lines.variation', 'lines.fromSlot', 'lines.toSlot', 'tasks']);
    }

    protected function ensureShipDocument(int $businessId, Transaction $order, StorageDocument $packDocument, StorageLocationSetting $settings, int $userId): StorageDocument
    {
        $document = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'ship')
            ->where('source_type', 'sales_order')
            ->where('source_id', $order->id)
            ->first();

        if (! $document) {
            $document = new StorageDocument([
                'business_id' => $businessId,
                'location_id' => $order->location_id,
                'area_id' => $settings->default_dispatch_area_id,
                'parent_document_id' => $packDocument->id,
                'document_no' => 'TMP-SHIP-' . Str::uuid(),
                'document_type' => 'ship',
                'source_type' => 'sales_order',
                'source_id' => $order->id,
                'source_ref' => $packDocument->source_ref,
                'status' => 'open',
                'workflow_state' => 'pending_ship',
                'execution_mode' => $settings->execution_mode,
                'sync_status' => 'not_required',
                'requested_by' => $packDocument->requested_by,
                'created_by' => $userId,
                'notes' => 'Generated from pack document ' . $packDocument->document_no,
                'meta' => [
                    'location_name' => optional($order->location)->name,
                    'customer_name' => optional($order->contact)->supplier_business_name ?: optional($order->contact)->name,
                    'pack_document_id' => $packDocument->id,
                ],
            ]);
            $document->save();
            $document->forceFill([
                'document_no' => 'SHIP-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
            ])->save();
        }

        $dispatchSlotId = $this->defaultAreaSlotId($businessId, (int) $order->location_id, $settings->default_dispatch_area_id ? (int) $settings->default_dispatch_area_id : null);
        $dispatchAreaId = $dispatchSlotId ? StorageSlot::query()->where('id', $dispatchSlotId)->value('area_id') : $settings->default_dispatch_area_id;

        $this->syncChildLines(
            $document,
            $packDocument,
            $userId,
            'ship',
            'ship',
            $dispatchAreaId,
            $dispatchSlotId,
            'staged_out',
            'suggested_dispatch_slot_id'
        );

        return $document->fresh(['lines.product', 'lines.variation', 'lines.fromSlot', 'lines.toSlot', 'tasks']);
    }

    protected function syncPickLines(StorageDocument $document, Transaction $order, int $userId): void
    {
        if (in_array($document->status, ['closed', 'completed', 'cancelled'], true)) {
            return;
        }

        $activeLineIds = [];

        foreach ($this->remainingSalesOrderLines($order) as $index => $sourceLine) {
            $bucket = $this->pickAvailableBucket(
                (int) $document->business_id,
                (int) $document->location_id,
                (int) $sourceLine['product_id'],
                $sourceLine['variation_id'],
                (float) $sourceLine['expected_qty'],
                'available'
            );

            $line = StorageDocumentLine::query()->updateOrCreate(
                [
                    'business_id' => $document->business_id,
                    'document_id' => $document->id,
                    'source_line_id' => $sourceLine['source_line_id'],
                ],
                [
                    'line_no' => $index + 1,
                    'product_id' => $sourceLine['product_id'],
                    'variation_id' => $sourceLine['variation_id'],
                    'from_area_id' => $bucket?->area_id,
                    'to_area_id' => $bucket?->area_id,
                    'from_slot_id' => $bucket?->slot_id,
                    'to_slot_id' => $bucket?->slot_id,
                    'expected_qty' => $sourceLine['expected_qty'],
                    'executed_qty' => $sourceLine['expected_qty'],
                    'variance_qty' => 0,
                    'unit_cost' => $sourceLine['unit_cost'],
                    'inventory_status' => 'picked',
                    'result_status' => 'pending',
                    'lot_number' => (string) ($bucket?->lot_number ?: ''),
                    'expiry_date' => $bucket?->expiry_date,
                    'meta' => [
                        'source_variation_sku' => $sourceLine['sku'],
                        'ordered_qty' => $sourceLine['ordered_qty'],
                        'invoiced_qty' => $sourceLine['invoiced_qty'],
                    ],
                ]
            );

            $activeLineIds[] = (int) $line->id;

            $task = StorageTask::query()->firstOrNew([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'document_line_id' => $line->id,
                'task_type' => 'pick',
            ]);

            $wasNew = ! $task->exists;
            $task->fill([
                'location_id' => $document->location_id,
                'area_id' => $line->from_area_id,
                'slot_id' => $line->from_slot_id,
                'status' => in_array($task->status, ['done', 'cancelled'], true) ? $task->status : 'open',
                'priority' => 'high',
                'required_scan_mode' => 'optional',
                'queue_name' => 'pick',
                'requested_by' => $document->requested_by ?: $userId,
                'target_qty' => (float) $sourceLine['expected_qty'],
                'meta' => [
                    'suggested_source_slot_id' => $line->from_slot_id,
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
                        'suggested_source_slot_id' => $line->from_slot_id,
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

    protected function syncChildLines(
        StorageDocument $document,
        StorageDocument $parentDocument,
        int $userId,
        string $taskType,
        string $queueName,
        ?int $targetAreaId,
        ?int $targetSlotId,
        string $inventoryStatus,
        string $suggestedSlotMetaKey
    ): void {
        if (in_array($document->status, ['closed', 'completed', 'cancelled'], true)) {
            return;
        }

        $parentDocument->loadMissing('lines');
        $activeLineIds = [];

        foreach ($parentDocument->lines as $index => $parentLine) {
            $quantity = round((float) ($parentLine->executed_qty ?: $parentLine->expected_qty), 4);
            if ($quantity <= 0) {
                continue;
            }

            $line = StorageDocumentLine::query()->updateOrCreate(
                [
                    'business_id' => $document->business_id,
                    'document_id' => $document->id,
                    'parent_line_id' => $parentLine->id,
                ],
                [
                    'line_no' => $index + 1,
                    'source_line_id' => $parentLine->source_line_id,
                    'product_id' => $parentLine->product_id,
                    'variation_id' => $parentLine->variation_id,
                    'from_area_id' => $parentLine->to_area_id ?: $parentLine->from_area_id,
                    'to_area_id' => $targetAreaId ?: ($parentLine->to_area_id ?: $parentLine->from_area_id),
                    'from_slot_id' => $parentLine->to_slot_id ?: $parentLine->from_slot_id,
                    'to_slot_id' => $targetSlotId ?: ($parentLine->to_slot_id ?: $parentLine->from_slot_id),
                    'expected_qty' => $quantity,
                    'executed_qty' => $quantity,
                    'variance_qty' => 0,
                    'unit_cost' => $parentLine->unit_cost,
                    'inventory_status' => $inventoryStatus,
                    'result_status' => 'pending',
                    'lot_number' => $parentLine->lot_number,
                    'expiry_date' => $parentLine->expiry_date,
                    'meta' => [
                        'generated_from_parent_line_id' => $parentLine->id,
                    ],
                ]
            );

            $activeLineIds[] = (int) $line->id;

            $task = StorageTask::query()->firstOrNew([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'document_line_id' => $line->id,
                'task_type' => $taskType,
            ]);

            $wasNew = ! $task->exists;
            $task->fill([
                'location_id' => $document->location_id,
                'area_id' => $line->to_area_id,
                'slot_id' => $line->to_slot_id,
                'status' => in_array($task->status, ['done', 'cancelled'], true) ? $task->status : 'open',
                'priority' => 'high',
                'required_scan_mode' => 'optional',
                'queue_name' => $queueName,
                'requested_by' => $document->requested_by ?: $userId,
                'target_qty' => $quantity,
                'meta' => [
                    $suggestedSlotMetaKey => $line->to_slot_id,
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
                        $suggestedSlotMetaKey => $line->to_slot_id,
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

    protected function loadSalesOrder(int $businessId, int $salesOrderId): Transaction
    {
        return Transaction::query()
            ->with(['contact', 'location', 'sell_lines.product', 'sell_lines.variations'])
            ->where('business_id', $businessId)
            ->where('type', 'sales_order')
            ->findOrFail($salesOrderId);
    }

    protected function locationSettingForOrder(int $businessId, int $locationId): StorageLocationSetting
    {
        $setting = StorageLocationSetting::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->first();

        if (! $setting || $setting->execution_mode === 'off') {
            throw new RuntimeException('Warehouse execution is not enabled for this location.');
        }

        return $setting;
    }

    protected function outboundRow(Transaction $order, ?StorageDocument $document, string $executionMode): array
    {
        $remainingLines = $this->remainingSalesOrderLines($order);

        return [
            'source_id' => (int) $order->id,
            'source_ref' => (string) ($order->ref_no ?: $order->invoice_no ?: ('SO-' . $order->id)),
            'source_status' => (string) $order->status,
            'shipping_status' => (string) ($order->shipping_status ?: 'ordered'),
            'customer_name' => (string) (optional($order->contact)->supplier_business_name ?: optional($order->contact)->name ?: '—'),
            'location_name' => (string) optional($order->location)->name,
            'transaction_date' => ! empty($order->transaction_date)
                ? Carbon::parse($order->transaction_date)->format('Y-m-d H:i')
                : null,
            'line_count' => (int) $remainingLines->count(),
            'expected_qty' => round((float) $remainingLines->sum('expected_qty'), 4),
            'document_id' => $document?->id,
            'document_no' => $document?->document_no,
            'document_status' => $document?->status,
            'execution_mode' => $executionMode,
        ];
    }

    protected function orderSummary(Transaction $order, string $executionMode, array $extra = []): array
    {
        $summary = [
            'source_id' => (int) $order->id,
            'source_ref' => (string) ($order->ref_no ?: $order->invoice_no ?: ('SO-' . $order->id)),
            'source_status' => (string) $order->status,
            'shipping_status' => (string) ($order->shipping_status ?: 'ordered'),
            'customer_name' => (string) (optional($order->contact)->supplier_business_name ?: optional($order->contact)->name ?: '—'),
            'location_name' => (string) optional($order->location)->name,
            'transaction_date' => ! empty($order->transaction_date)
                ? Carbon::parse($order->transaction_date)->format('Y-m-d H:i')
                : null,
            'execution_mode' => $executionMode,
        ];

        return array_merge($summary, $extra);
    }

    protected function pickAvailableBucket(
        int $businessId,
        int $locationId,
        int $productId,
        ?int $variationId,
        float $requiredQty,
        string $inventoryStatus,
        ?array $slotIds = null
    ): ?StorageSlotStock {
        return StorageSlotStock::query()
            ->with('slot.area')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->where('inventory_status', $inventoryStatus)
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

    protected function defaultAreaSlotId(int $businessId, int $locationId, ?int $preferredAreaId = null): ?int
    {
        if ($preferredAreaId) {
            $slotId = StorageSlot::query()
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->where('area_id', $preferredAreaId)
                ->active()
                ->orderBy('pick_sequence')
                ->orderBy('putaway_sequence')
                ->orderBy('row')
                ->orderBy('position')
                ->value('id');

            if ($slotId) {
                return (int) $slotId;
            }
        }

        $fallback = StorageSlot::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->active()
            ->orderBy('pick_sequence')
            ->orderBy('putaway_sequence')
            ->orderBy('row')
            ->orderBy('position')
            ->value('id');

        return $fallback ? (int) $fallback : null;
    }

    protected function remainingSalesOrderLines(Transaction $order): Collection
    {
        $order->loadMissing(['sell_lines.product', 'sell_lines.variations']);

        return $order->sell_lines
            ->map(function ($line) {
                $orderedQty = round((float) $line->quantity, 4);
                $invoicedQty = round((float) ($line->so_quantity_invoiced ?? 0), 4);
                $remainingQty = round(max($orderedQty - $invoicedQty, 0), 4);

                return [
                    'source_line_id' => (int) $line->id,
                    'product_id' => (int) $line->product_id,
                    'variation_id' => $line->variation_id ? (int) $line->variation_id : null,
                    'expected_qty' => $remainingQty,
                    'ordered_qty' => $orderedQty,
                    'invoiced_qty' => $invoicedQty,
                    'unit_cost' => round((float) ($line->unit_price ?: 0), 4),
                    'sku' => (string) optional($line->variations)->sub_sku,
                ];
            })
            ->filter(fn (array $line) => $line['expected_qty'] > 0)
            ->values();
    }

    protected function updateOrderShippingStatus(Transaction $order, string $status, int $userId, bool $allowUpdate): void
    {
        if (! $allowUpdate) {
            return;
        }

        $rank = [
            'ordered' => 1,
            'packed' => 2,
            'shipped' => 3,
            'delivered' => 4,
            'cancelled' => 5,
        ];

        $currentStatus = (string) ($order->shipping_status ?: 'ordered');
        if (($rank[$currentStatus] ?? 0) >= ($rank[$status] ?? 0)) {
            return;
        }

        $before = $order->replicate();
        $order->shipping_status = $status;
        $order->save();

        $this->transactionUtil->activityLog(
            $order,
            'shipping_edited',
            $before,
            [
                'update_note' => 'Updated by StorageManager outbound execution.',
                'updated_by' => $userId,
            ]
        );
    }

    protected function completeTask(StorageTask $task, int $userId, float $quantity, array $payload = []): void
    {
        $task->forceFill([
            'status' => 'done',
            'completed_qty' => $quantity,
            'completed_at' => now(),
            'meta' => array_merge((array) $task->meta, $payload),
        ])->save();

        StorageTaskEvent::query()->create([
            'business_id' => $task->business_id,
            'task_id' => $task->id,
            'event_type' => 'completed',
            'user_id' => $userId,
            'payload' => array_merge($payload, ['quantity' => $quantity]),
        ]);
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
