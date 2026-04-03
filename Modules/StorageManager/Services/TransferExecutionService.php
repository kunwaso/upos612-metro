<?php

namespace Modules\StorageManager\Services;

use App\Transaction;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLine;
use Modules\StorageManager\Entities\StorageLocationSetting;
use Modules\StorageManager\Entities\StorageSlot;
use Modules\StorageManager\Entities\StorageSlotStock;
use Modules\StorageManager\Entities\StorageTask;
use Modules\StorageManager\Entities\StorageTaskEvent;
use RuntimeException;

class TransferExecutionService
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService,
        protected PutawayService $putawayService,
        protected WarehouseSyncService $warehouseSyncService,
        protected ReconciliationService $reconciliationService,
        protected ProductUtil $productUtil,
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
                'dispatchSummary' => [
                    'enabled_location_count' => 0,
                    'dispatch_count' => 0,
                    'receipt_count' => 0,
                ],
                'dispatchRows' => collect(),
                'receiptRows' => collect(),
            ];
        }

        $dispatchDocuments = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'transfer_dispatch')
            ->get()
            ->keyBy('source_id');

        $receiptDocuments = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'transfer_receipt')
            ->get()
            ->keyBy('source_id');

        $sellTransfers = Transaction::query()
            ->with([
                'location',
                'sell_lines.product',
                'sell_lines.variations',
            ])
            ->where('business_id', $businessId)
            ->where('type', 'sell_transfer')
            ->where(function ($query) use ($enabledLocationIds, $businessId) {
                $query->whereIn('location_id', $enabledLocationIds)
                    ->orWhereIn('id', function ($subQuery) use ($enabledLocationIds, $businessId) {
                        $subQuery->select('transfer_parent_id')
                            ->from('transactions')
                            ->where('business_id', $businessId)
                            ->where('type', 'purchase_transfer')
                            ->whereIn('location_id', $enabledLocationIds);
                    });
            })
            ->whereNotIn('status', ['draft'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        $purchaseTransfers = Transaction::query()
            ->with('location')
            ->where('business_id', $businessId)
            ->where('type', 'purchase_transfer')
            ->whereIn('transfer_parent_id', $sellTransfers->pluck('id'))
            ->get()
            ->keyBy('transfer_parent_id');

        $dispatchRows = $sellTransfers
            ->filter(fn (Transaction $transfer) => in_array($transfer->status, ['pending', 'in_transit'], true))
            ->filter(fn (Transaction $transfer) => in_array((int) $transfer->location_id, $enabledLocationIds, true))
            ->map(function (Transaction $transfer) use ($purchaseTransfers, $dispatchDocuments, $settings) {
                $purchaseTransfer = $purchaseTransfers->get($transfer->id);
                $document = $dispatchDocuments->get($transfer->id);
                $setting = $settings->get($transfer->location_id);

                return [
                    'source_id' => (int) $transfer->id,
                    'source_ref' => (string) ($transfer->ref_no ?: $transfer->invoice_no ?: ('TRF-' . $transfer->id)),
                    'status' => (string) $transfer->status,
                    'source_location_name' => (string) optional($transfer->location)->name,
                    'destination_location_name' => (string) optional($purchaseTransfer?->location)->name,
                    'transaction_date' => ! empty($transfer->transaction_date)
                        ? Carbon::parse($transfer->transaction_date)->format('Y-m-d H:i')
                        : null,
                    'line_count' => (int) $transfer->sell_lines->count(),
                    'expected_qty' => round((float) $transfer->sell_lines->sum('quantity'), 4),
                    'document_id' => $document?->id,
                    'document_no' => $document?->document_no,
                    'document_status' => $document?->status,
                    'execution_mode' => (string) ($setting?->execution_mode ?: 'off'),
                ];
            })
            ->values();

        $receiptRows = $sellTransfers
            ->filter(function (Transaction $transfer) use ($purchaseTransfers, $enabledLocationIds) {
                $purchaseTransfer = $purchaseTransfers->get($transfer->id);

                return $purchaseTransfer
                    && in_array((int) $purchaseTransfer->location_id, $enabledLocationIds, true)
                    && in_array($purchaseTransfer->status, ['in_transit', 'received'], true);
            })
            ->map(function (Transaction $transfer) use ($purchaseTransfers, $receiptDocuments, $settings) {
                $purchaseTransfer = $purchaseTransfers->get($transfer->id);
                $document = $receiptDocuments->get($transfer->id);
                $setting = $settings->get($purchaseTransfer->location_id);

                return [
                    'source_id' => (int) $transfer->id,
                    'source_ref' => (string) ($transfer->ref_no ?: $transfer->invoice_no ?: ('TRF-' . $transfer->id)),
                    'status' => (string) $purchaseTransfer->status,
                    'source_location_name' => (string) optional($transfer->location)->name,
                    'destination_location_name' => (string) optional($purchaseTransfer->location)->name,
                    'transaction_date' => ! empty($transfer->transaction_date)
                        ? Carbon::parse($transfer->transaction_date)->format('Y-m-d H:i')
                        : null,
                    'line_count' => (int) $transfer->sell_lines->count(),
                    'expected_qty' => round((float) $transfer->sell_lines->sum('quantity'), 4),
                    'document_id' => $document?->id,
                    'document_no' => $document?->document_no,
                    'document_status' => $document?->status,
                    'execution_mode' => (string) ($setting?->execution_mode ?: 'off'),
                ];
            })
            ->values();

        return [
            'dispatchSummary' => [
                'enabled_location_count' => count($enabledLocationIds),
                'dispatch_count' => (int) $dispatchRows->count(),
                'receipt_count' => (int) $receiptRows->count(),
            ],
            'dispatchRows' => $dispatchRows,
            'receiptRows' => $receiptRows,
        ];
    }

    public function getDispatchWorkbench(int $businessId, int $transferId, int $userId): array
    {
        [$sellTransfer, $purchaseTransfer] = $this->loadTransferPair($businessId, $transferId);
        $settings = $this->locationSettingForLocation($businessId, (int) $sellTransfer->location_id);
        $document = $this->ensureDispatchDocument($businessId, $sellTransfer, $purchaseTransfer, $settings, $userId);
        $this->syncDispatchLines($document, $sellTransfer, $settings, $userId);

        $document = $document->fresh([
            'lines.product',
            'lines.variation',
            'lines.fromArea',
            'lines.fromSlot',
            'lines.toArea',
            'lines.toSlot',
        ]);

        $dispatchSlotOptions = $this->putawayService->slotOptionsForLocation(
            $businessId,
            (int) $sellTransfer->location_id,
            $settings->default_dispatch_area_id ? [$settings->default_dispatch_area_id] : null
        );
        if (empty($dispatchSlotOptions)) {
            $dispatchSlotOptions = $this->putawayService->slotOptionsForLocation($businessId, (int) $sellTransfer->location_id);
        }

        $sourceSlotOptions = $this->putawayService->slotOptionsForLocation($businessId, (int) $sellTransfer->location_id);

        $lineRows = $document->lines->map(function (StorageDocumentLine $line) use ($businessId, $sellTransfer) {
            $bucket = $this->pickAvailableSourceBucket(
                $businessId,
                (int) $sellTransfer->location_id,
                (int) $line->product_id,
                $line->variation_id ? (int) $line->variation_id : null,
                (float) ($line->executed_qty ?: $line->expected_qty),
                $line->from_slot_id ? [(int) $line->from_slot_id] : null
            );

            return [
                'id' => (int) $line->id,
                'product_label' => (string) optional($line->product)->name,
                'sku' => (string) (optional($line->variation)->sub_sku ?: optional($line->product)->sku ?: '—'),
                'qty' => (float) ($line->executed_qty ?: $line->expected_qty),
                'source_slot_id' => $line->from_slot_id ?: $bucket?->slot_id,
                'source_slot_label' => $line->fromSlot ? $this->slotLabel($line->fromSlot) : $this->slotLabel($bucket?->slot),
                'dispatch_slot_id' => $line->to_slot_id ?: $this->defaultDispatchSlotId($businessId, (int) $sellTransfer->location_id, $settings),
                'dispatch_slot_label' => $this->slotLabel($line->toSlot),
                'available_qty' => round((float) ($bucket?->qty_on_hand ?? 0), 4),
                'lot_number' => (string) ($line->lot_number ?: ($bucket?->lot_number ?: '')),
                'expiry_date' => optional($line->expiry_date ?: $bucket?->expiry_date)->toDateString(),
                'result_status' => (string) $line->result_status,
            ];
        })->values();

        return [
            'document' => $document,
            'sourceSummary' => [
                'source_ref' => (string) ($sellTransfer->ref_no ?: $sellTransfer->invoice_no ?: ('TRF-' . $sellTransfer->id)),
                'source_id' => (int) $sellTransfer->id,
                'source_status' => (string) $sellTransfer->status,
                'source_location_name' => (string) optional($sellTransfer->location)->name,
                'destination_location_name' => (string) optional($purchaseTransfer->location)->name,
                'transaction_date' => ! empty($sellTransfer->transaction_date)
                    ? Carbon::parse($sellTransfer->transaction_date)->format('Y-m-d H:i')
                    : null,
                'execution_mode' => (string) $settings->execution_mode,
                'can_confirm' => ! in_array($document->status, ['closed', 'completed', 'cancelled'], true),
            ],
            'lineRows' => $lineRows,
            'sourceSlotOptions' => $sourceSlotOptions,
            'dispatchSlotOptions' => $dispatchSlotOptions,
        ];
    }

    public function confirmDispatch(
        int $businessId,
        StorageDocument $document,
        array $payload,
        int $userId,
        bool $allowSourceStatusUpdate = false
    ): StorageDocument {
        if ($document->document_type !== 'transfer_dispatch') {
            throw new RuntimeException('Only transfer dispatch documents can be confirmed here.');
        }

        if (in_array($document->status, ['closed', 'completed', 'cancelled'], true)) {
            throw new RuntimeException('This transfer dispatch document is already closed.');
        }

        [$sellTransfer, $purchaseTransfer] = $this->loadTransferPair($businessId, (int) $document->source_id);
        $settings = $this->locationSettingForLocation($businessId, (int) $sellTransfer->location_id);
        $lineInputs = (array) ($payload['lines'] ?? []);

        DB::transaction(function () use (
            $businessId,
            $document,
            $sellTransfer,
            $purchaseTransfer,
            $settings,
            $lineInputs,
            $userId,
            $allowSourceStatusUpdate
        ) {
            $document->loadMissing(['lines.tasks']);

            foreach ($document->lines as $line) {
                $input = (array) ($lineInputs[$line->id] ?? []);
                $sourceSlotId = (int) ($input['source_slot_id'] ?? $line->from_slot_id);
                $dispatchSlotId = (int) ($input['dispatch_slot_id'] ?? $line->to_slot_id ?: $this->defaultDispatchSlotId($businessId, (int) $sellTransfer->location_id, $settings));
                $quantity = round((float) ($input['executed_qty'] ?? $line->expected_qty), 4);

                if ($sourceSlotId <= 0 || $dispatchSlotId <= 0) {
                    throw new RuntimeException("Source and dispatch slots are required for transfer line [{$line->id}].");
                }

                if ($quantity <= 0) {
                    throw new RuntimeException("Executed quantity must be greater than zero for transfer line [{$line->id}].");
                }

                if ($quantity !== round((float) $line->expected_qty, 4)) {
                    throw new RuntimeException('This phase requires dispatch quantity to match the stock transfer quantity exactly.');
                }

                $sourceSlot = $this->validatedSlot($businessId, (int) $sellTransfer->location_id, $sourceSlotId);
                $dispatchSlot = $this->validatedSlot($businessId, (int) $sellTransfer->location_id, $dispatchSlotId);
                $bucket = $this->pickAvailableSourceBucket(
                    $businessId,
                    (int) $sellTransfer->location_id,
                    (int) $line->product_id,
                    $line->variation_id ? (int) $line->variation_id : null,
                    $quantity,
                    [$sourceSlotId]
                );

                if (! $bucket) {
                    throw new RuntimeException("No single available bin/lot has enough stock to dispatch transfer line [{$line->id}].");
                }

                $this->inventoryMovementService->applyMovement([
                    'business_id' => $businessId,
                    'location_id' => $sellTransfer->location_id,
                    'document_id' => $document->id,
                    'document_line_id' => $line->id,
                    'task_id' => optional($line->tasks->first())->id,
                    'source_type' => 'stock_transfer',
                    'source_id' => $sellTransfer->id,
                    'source_line_id' => $line->source_line_id,
                    'movement_type' => 'transfer_dispatch',
                    'direction' => 'move',
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'from_area_id' => $sourceSlot->area_id,
                    'to_area_id' => $dispatchSlot->area_id,
                    'from_slot_id' => $sourceSlotId,
                    'to_slot_id' => $dispatchSlotId,
                    'from_status' => 'available',
                    'to_status' => 'staged_out',
                    'lot_number' => $bucket->lot_number,
                    'expiry_date' => optional($bucket->expiry_date)->toDateString(),
                    'quantity' => $quantity,
                    'unit_cost' => $line->unit_cost,
                    'reason_code' => 'transfer_dispatch',
                    'idempotency_key' => 'transfer-dispatch-' . $document->id . '-line-' . $line->id,
                    'created_by' => $userId,
                ]);

                $line->forceFill([
                    'from_area_id' => $sourceSlot->area_id,
                    'to_area_id' => $dispatchSlot->area_id,
                    'from_slot_id' => $sourceSlotId,
                    'to_slot_id' => $dispatchSlotId,
                    'executed_qty' => $quantity,
                    'variance_qty' => 0,
                    'inventory_status' => 'staged_out',
                    'result_status' => 'dispatched',
                    'lot_number' => (string) $bucket->lot_number,
                    'expiry_date' => optional($bucket->expiry_date)->toDateString(),
                ])->save();

                foreach ($line->tasks as $task) {
                    $this->completeTask($task, $userId, $quantity, [
                        'source_slot_id' => $sourceSlotId,
                        'dispatch_slot_id' => $dispatchSlotId,
                    ]);
                }
            }

            if ($sellTransfer->status !== 'in_transit') {
                if (! $allowSourceStatusUpdate && $sellTransfer->status !== 'in_transit') {
                    throw new RuntimeException('This stock transfer must be moved to in-transit before warehouse dispatch can be completed.');
                }

                $this->markTransferInTransit($sellTransfer, $purchaseTransfer);
            }

            $document->forceFill([
                'status' => 'closed',
                'workflow_state' => 'dispatched',
                'completed_at' => now(),
                'closed_at' => now(),
                'closed_by' => $userId,
            ])->save();

            $reconcileResult = $this->reconciliationService->reconcileLocation($businessId, (int) $sellTransfer->location_id);
            if (! empty($reconcileResult['has_blockers'])) {
                throw new RuntimeException('Dispatch would leave the source warehouse out of reconciliation. Please review slot balances before closing.');
            }
        });

        $dispatchDocument = $document->fresh([
            'lines.product',
            'lines.variation',
            'lines.fromArea',
            'lines.fromSlot',
            'lines.toArea',
            'lines.toSlot',
        ]);

        $this->ensureReceiptDocumentFromDispatch($dispatchDocument, $userId);

        try {
            $this->warehouseSyncService->syncDocument($dispatchDocument, $userId);
        } catch (\Throwable $exception) {
        }

        return $dispatchDocument->fresh([
            'lines.product',
            'lines.variation',
            'lines.fromArea',
            'lines.fromSlot',
            'lines.toArea',
            'lines.toSlot',
        ]);
    }

    public function getReceiptWorkbench(int $businessId, int $transferId, int $userId): array
    {
        [$sellTransfer, $purchaseTransfer] = $this->loadTransferPair($businessId, $transferId);
        $settings = $this->locationSettingForLocation($businessId, (int) $purchaseTransfer->location_id);
        $dispatchDocument = $this->ensureDispatchDocument(
            $businessId,
            $sellTransfer,
            $purchaseTransfer,
            $this->locationSettingForLocation($businessId, (int) $sellTransfer->location_id),
            $userId
        );
        $receiptDocument = $this->ensureReceiptDocumentFromDispatch($dispatchDocument, $userId);
        $this->syncReceiptLinesFromDispatch($receiptDocument->fresh(), $dispatchDocument->fresh('lines.product', 'lines.variation'), $settings, $userId);

        $receiptDocument = $receiptDocument->fresh([
            'lines.product',
            'lines.variation',
            'lines.fromArea',
            'lines.fromSlot',
            'lines.toArea',
            'lines.toSlot',
            'parentDocument',
        ]);

        $stagingSlotOptions = $this->putawayService->slotOptionsForLocation(
            $businessId,
            (int) $purchaseTransfer->location_id,
            array_filter([
                $settings->default_staging_area_id,
                $settings->default_receiving_area_id,
            ])
        );
        if (empty($stagingSlotOptions)) {
            $stagingSlotOptions = $this->putawayService->slotOptionsForLocation($businessId, (int) $purchaseTransfer->location_id);
        }

        $lineRows = $receiptDocument->lines->map(function (StorageDocumentLine $line) {
            return [
                'id' => (int) $line->id,
                'product_label' => (string) optional($line->product)->name,
                'sku' => (string) (optional($line->variation)->sub_sku ?: optional($line->product)->sku ?: '—'),
                'qty' => (float) ($line->executed_qty ?: $line->expected_qty),
                'dispatch_slot_label' => $this->slotLabel($line->fromSlot),
                'dispatch_area_label' => (string) (optional($line->fromArea)->name ?: '—'),
                'staging_slot_id' => $line->to_slot_id,
                'staging_slot_label' => $this->slotLabel($line->toSlot),
                'lot_number' => (string) ($line->lot_number ?: '—'),
                'expiry_date' => optional($line->expiry_date)->toDateString(),
                'result_status' => (string) $line->result_status,
            ];
        })->values();

        $putawayDocument = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'putaway')
            ->where('parent_document_id', $receiptDocument->id)
            ->first();

        return [
            'document' => $receiptDocument,
            'parentDocument' => $dispatchDocument,
            'sourceSummary' => [
                'source_ref' => (string) ($sellTransfer->ref_no ?: $sellTransfer->invoice_no ?: ('TRF-' . $sellTransfer->id)),
                'source_status' => (string) $purchaseTransfer->status,
                'source_location_name' => (string) optional($sellTransfer->location)->name,
                'destination_location_name' => (string) optional($purchaseTransfer->location)->name,
                'transaction_date' => ! empty($sellTransfer->transaction_date)
                    ? Carbon::parse($sellTransfer->transaction_date)->format('Y-m-d H:i')
                    : null,
                'execution_mode' => (string) $settings->execution_mode,
                'can_confirm' => ! in_array($receiptDocument->status, ['closed', 'completed', 'cancelled'], true),
                'has_putaway_document' => (bool) $putawayDocument,
                'putaway_document_id' => $putawayDocument?->id,
            ],
            'lineRows' => $lineRows,
            'stagingSlotOptions' => $stagingSlotOptions,
        ];
    }

    public function confirmReceipt(
        int $businessId,
        StorageDocument $document,
        array $payload,
        int $userId,
        bool $allowSourceStatusUpdate = false
    ): StorageDocument {
        if ($document->document_type !== 'transfer_receipt') {
            throw new RuntimeException('Only transfer receipt documents can be confirmed here.');
        }

        if (in_array($document->status, ['closed', 'completed', 'cancelled'], true)) {
            throw new RuntimeException('This transfer receipt document is already completed.');
        }

        [$sellTransfer, $purchaseTransfer] = $this->loadTransferPair($businessId, (int) $document->source_id);
        $settings = $this->locationSettingForLocation($businessId, (int) $purchaseTransfer->location_id);
        $lineInputs = (array) ($payload['lines'] ?? []);

        DB::transaction(function () use (
            $businessId,
            $document,
            $sellTransfer,
            $purchaseTransfer,
            $settings,
            $lineInputs,
            $userId,
            $allowSourceStatusUpdate
        ) {
            $document->loadMissing(['lines.tasks']);

            foreach ($document->lines as $line) {
                $input = (array) ($lineInputs[$line->id] ?? []);
                $stagingSlotId = (int) ($input['staging_slot_id'] ?? $line->to_slot_id ?: $this->defaultTransferReceiptSlotId($businessId, (int) $purchaseTransfer->location_id, $settings));
                $quantity = round((float) ($input['executed_qty'] ?? $line->expected_qty), 4);

                if ($stagingSlotId <= 0) {
                    throw new RuntimeException("Staging slot is required for transfer receipt line [{$line->id}].");
                }

                if ($quantity <= 0) {
                    throw new RuntimeException("Executed quantity must be greater than zero for transfer receipt line [{$line->id}].");
                }

                if ($quantity !== round((float) $line->expected_qty, 4)) {
                    throw new RuntimeException('This phase requires receipt quantity to match the stock transfer quantity exactly.');
                }

                $stagingSlot = $this->validatedSlot($businessId, (int) $purchaseTransfer->location_id, $stagingSlotId);

                $this->inventoryMovementService->applyMovement([
                    'business_id' => $businessId,
                    'location_id' => $sellTransfer->location_id,
                    'document_id' => $document->id,
                    'document_line_id' => $line->id,
                    'task_id' => optional($line->tasks->first())->id,
                    'source_type' => 'stock_transfer',
                    'source_id' => $sellTransfer->id,
                    'source_line_id' => $line->source_line_id,
                    'movement_type' => 'transfer_receipt',
                    'direction' => 'out',
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'from_area_id' => $line->from_area_id,
                    'from_slot_id' => $line->from_slot_id,
                    'from_status' => 'staged_out',
                    'lot_number' => $line->lot_number,
                    'expiry_date' => optional($line->expiry_date)->toDateString(),
                    'quantity' => $quantity,
                    'unit_cost' => $line->unit_cost,
                    'reason_code' => 'transfer_receipt_source_release',
                    'idempotency_key' => 'transfer-receipt-out-' . $document->id . '-line-' . $line->id,
                    'created_by' => $userId,
                ]);

                $this->inventoryMovementService->applyMovement([
                    'business_id' => $businessId,
                    'location_id' => $purchaseTransfer->location_id,
                    'document_id' => $document->id,
                    'document_line_id' => $line->id,
                    'task_id' => optional($line->tasks->first())->id,
                    'source_type' => 'stock_transfer',
                    'source_id' => $sellTransfer->id,
                    'source_line_id' => $line->source_line_id,
                    'movement_type' => 'transfer_receipt',
                    'direction' => 'in',
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'to_area_id' => $stagingSlot->area_id,
                    'to_slot_id' => $stagingSlotId,
                    'to_status' => 'staged_in',
                    'lot_number' => $line->lot_number,
                    'expiry_date' => optional($line->expiry_date)->toDateString(),
                    'quantity' => $quantity,
                    'unit_cost' => $line->unit_cost,
                    'reason_code' => 'transfer_receipt_destination_stage',
                    'idempotency_key' => 'transfer-receipt-in-' . $document->id . '-line-' . $line->id,
                    'created_by' => $userId,
                ]);

                $line->forceFill([
                    'to_area_id' => $stagingSlot->area_id,
                    'to_slot_id' => $stagingSlotId,
                    'executed_qty' => $quantity,
                    'variance_qty' => 0,
                    'inventory_status' => 'staged_in',
                    'result_status' => 'received',
                ])->save();

                foreach ($line->tasks as $task) {
                    $this->completeTask($task, $userId, $quantity, [
                        'staging_slot_id' => $stagingSlotId,
                    ]);
                }
            }

            if ($purchaseTransfer->status !== 'received') {
                if (! $allowSourceStatusUpdate && $purchaseTransfer->status !== 'received') {
                    throw new RuntimeException('This stock transfer must be completed in the source module before warehouse receipt can be finalized.');
                }

                $this->markTransferCompleted(
                    $businessId,
                    $sellTransfer->fresh(['sell_lines.product']),
                    $purchaseTransfer->fresh(['purchase_lines'])
                );
            }

            $sourceReconcile = $this->reconciliationService->reconcileLocation($businessId, (int) $sellTransfer->location_id);
            $destinationReconcile = $this->reconciliationService->reconcileLocation($businessId, (int) $purchaseTransfer->location_id);
            if (! empty($sourceReconcile['has_blockers']) || ! empty($destinationReconcile['has_blockers'])) {
                throw new RuntimeException('Transfer receipt would leave warehouse stock out of reconciliation. Please review source and destination bin balances.');
            }

            $document->forceFill([
                'status' => 'completed',
                'workflow_state' => 'putaway_pending',
                'completed_at' => now(),
                'approved_by' => $userId,
            ])->save();
        });

        $receiptDocument = $document->fresh([
            'lines.product',
            'lines.variation',
            'lines.fromArea',
            'lines.fromSlot',
            'lines.toArea',
            'lines.toSlot',
        ]);

        $this->putawayService->ensureDocumentForReceipt($receiptDocument, $userId);

        try {
            $this->warehouseSyncService->syncDocument($receiptDocument, $userId);
        } catch (\Throwable $exception) {
        }

        return $receiptDocument->fresh([
            'lines.product',
            'lines.variation',
            'lines.fromArea',
            'lines.fromSlot',
            'lines.toArea',
            'lines.toSlot',
        ]);
    }

    protected function ensureDispatchDocument(
        int $businessId,
        Transaction $sellTransfer,
        Transaction $purchaseTransfer,
        StorageLocationSetting $settings,
        int $userId
    ): StorageDocument {
        $document = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'transfer_dispatch')
            ->where('source_type', 'stock_transfer')
            ->where('source_id', $sellTransfer->id)
            ->first();

        if (! $document) {
            $sourceRef = (string) ($sellTransfer->ref_no ?: $sellTransfer->invoice_no ?: ('TRF-' . $sellTransfer->id));
            $document = new StorageDocument([
                'business_id' => $businessId,
                'location_id' => $sellTransfer->location_id,
                'area_id' => $settings->default_dispatch_area_id,
                'document_no' => 'TMP-TXD-' . uniqid(),
                'document_type' => 'transfer_dispatch',
                'source_type' => 'stock_transfer',
                'source_id' => $sellTransfer->id,
                'source_ref' => $sourceRef,
                'status' => 'open',
                'workflow_state' => 'pending_dispatch',
                'execution_mode' => $settings->execution_mode,
                'sync_status' => 'not_required',
                'requested_by' => $sellTransfer->created_by,
                'created_by' => $userId,
                'notes' => 'Generated from stock transfer ' . $sourceRef,
                'meta' => [
                    'location_name' => optional($sellTransfer->location)->name,
                    'destination_location_id' => $purchaseTransfer->location_id,
                    'destination_location_name' => optional($purchaseTransfer->location)->name,
                ],
            ]);
            $document->save();
            $document->forceFill([
                'document_no' => 'TXD-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
            ])->save();
        }

        return $document;
    }

    protected function syncDispatchLines(StorageDocument $document, Transaction $sellTransfer, StorageLocationSetting $settings, int $userId): void
    {
        $sellTransfer->loadMissing(['sell_lines.product', 'sell_lines.variations']);
        $activeLineIds = [];

        foreach ($sellTransfer->sell_lines as $index => $sellLine) {
            $quantity = round((float) $sellLine->quantity, 4);
            if ($quantity <= 0) {
                continue;
            }

            $bucket = $this->pickAvailableSourceBucket(
                (int) $document->business_id,
                (int) $sellTransfer->location_id,
                (int) $sellLine->product_id,
                $sellLine->variation_id ? (int) $sellLine->variation_id : null,
                $quantity
            );
            $dispatchSlot = $this->defaultDispatchSlotId((int) $document->business_id, (int) $sellTransfer->location_id, $settings);
            $dispatchAreaId = $dispatchSlot ? StorageSlot::query()->where('id', $dispatchSlot)->value('area_id') : null;

            $line = StorageDocumentLine::query()->updateOrCreate(
                [
                    'business_id' => $document->business_id,
                    'document_id' => $document->id,
                    'source_line_id' => $sellLine->id,
                ],
                [
                    'line_no' => $index + 1,
                    'product_id' => $sellLine->product_id,
                    'variation_id' => $sellLine->variation_id,
                    'from_area_id' => $bucket?->area_id,
                    'to_area_id' => $dispatchAreaId,
                    'from_slot_id' => $bucket?->slot_id,
                    'to_slot_id' => $dispatchSlot,
                    'expected_qty' => $quantity,
                    'executed_qty' => $quantity,
                    'variance_qty' => 0,
                    'unit_cost' => (float) ($sellLine->unit_price ?: 0),
                    'inventory_status' => 'staged_out',
                    'result_status' => 'pending',
                    'lot_number' => (string) ($bucket?->lot_number ?: ''),
                    'expiry_date' => optional($bucket?->expiry_date)->toDateString(),
                    'meta' => [
                        'source_variation_sku' => optional($sellLine->variations)->sub_sku,
                    ],
                ]
            );

            $activeLineIds[] = (int) $line->id;

            $task = StorageTask::query()->firstOrNew([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'document_line_id' => $line->id,
                'task_type' => 'transfer_dispatch',
            ]);

            $wasNew = ! $task->exists;
            $task->fill([
                'location_id' => $document->location_id,
                'area_id' => $line->to_area_id,
                'slot_id' => $line->to_slot_id,
                'status' => in_array($task->status, ['done', 'cancelled'], true) ? $task->status : 'open',
                'priority' => 'high',
                'required_scan_mode' => 'optional',
                'queue_name' => 'transfer_dispatch',
                'requested_by' => $document->requested_by ?: $userId,
                'target_qty' => $quantity,
                'meta' => [
                    'suggested_source_slot_id' => $bucket?->slot_id,
                    'suggested_dispatch_slot_id' => $line->to_slot_id,
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
                        'suggested_source_slot_id' => $bucket?->slot_id,
                        'suggested_dispatch_slot_id' => $line->to_slot_id,
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

    protected function ensureReceiptDocumentFromDispatch(StorageDocument $dispatchDocument, int $userId): StorageDocument
    {
        [$sellTransfer, $purchaseTransfer] = $this->loadTransferPair((int) $dispatchDocument->business_id, (int) $dispatchDocument->source_id);
        $settings = $this->locationSettingForLocation((int) $dispatchDocument->business_id, (int) $purchaseTransfer->location_id);

        $document = StorageDocument::query()
            ->where('business_id', $dispatchDocument->business_id)
            ->where('document_type', 'transfer_receipt')
            ->where('source_type', 'stock_transfer')
            ->where('source_id', $dispatchDocument->source_id)
            ->first();

        if (! $document) {
            $document = new StorageDocument([
                'business_id' => $dispatchDocument->business_id,
                'location_id' => $purchaseTransfer->location_id,
                'area_id' => $settings->default_staging_area_id ?: $settings->default_receiving_area_id,
                'parent_document_id' => $dispatchDocument->id,
                'document_no' => 'TMP-TXR-' . uniqid(),
                'document_type' => 'transfer_receipt',
                'source_type' => 'stock_transfer',
                'source_id' => $dispatchDocument->source_id,
                'source_ref' => $dispatchDocument->source_ref,
                'status' => 'open',
                'workflow_state' => 'pending_receipt',
                'execution_mode' => $settings->execution_mode,
                'sync_status' => 'not_required',
                'requested_by' => $dispatchDocument->requested_by,
                'created_by' => $userId,
                'notes' => 'Generated from transfer dispatch ' . $dispatchDocument->document_no,
                'meta' => [
                    'location_name' => optional($purchaseTransfer->location)->name,
                    'source_location_id' => $sellTransfer->location_id,
                    'source_location_name' => optional($sellTransfer->location)->name,
                    'dispatch_document_id' => $dispatchDocument->id,
                ],
            ]);
            $document->save();
            $document->forceFill([
                'document_no' => 'TXR-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
            ])->save();
        }

        $this->syncReceiptLinesFromDispatch($document->fresh(), $dispatchDocument->fresh('lines.product', 'lines.variation'), $settings, $userId);

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

    protected function syncReceiptLinesFromDispatch(
        StorageDocument $document,
        StorageDocument $dispatchDocument,
        StorageLocationSetting $settings,
        int $userId
    ): void {
        $dispatchDocument->loadMissing('lines');
        $activeLineIds = [];
        $stagingSlotId = $this->defaultTransferReceiptSlotId((int) $document->business_id, (int) $document->location_id, $settings);
        $stagingAreaId = $stagingSlotId ? StorageSlot::query()->where('id', $stagingSlotId)->value('area_id') : ($settings->default_staging_area_id ?: $settings->default_receiving_area_id);

        foreach ($dispatchDocument->lines as $index => $dispatchLine) {
            $quantity = round((float) ($dispatchLine->executed_qty ?: $dispatchLine->expected_qty), 4);
            if ($quantity <= 0) {
                continue;
            }

            $line = StorageDocumentLine::query()->updateOrCreate(
                [
                    'business_id' => $document->business_id,
                    'document_id' => $document->id,
                    'parent_line_id' => $dispatchLine->id,
                ],
                [
                    'line_no' => $index + 1,
                    'source_line_id' => $dispatchLine->source_line_id,
                    'product_id' => $dispatchLine->product_id,
                    'variation_id' => $dispatchLine->variation_id,
                    'from_area_id' => $dispatchLine->to_area_id,
                    'to_area_id' => $stagingAreaId,
                    'from_slot_id' => $dispatchLine->to_slot_id,
                    'to_slot_id' => $stagingSlotId,
                    'expected_qty' => $quantity,
                    'executed_qty' => $quantity,
                    'variance_qty' => 0,
                    'unit_cost' => $dispatchLine->unit_cost,
                    'inventory_status' => 'staged_in',
                    'result_status' => 'pending',
                    'lot_number' => $dispatchLine->lot_number,
                    'expiry_date' => $dispatchLine->expiry_date,
                    'meta' => [
                        'generated_from_dispatch_line_id' => $dispatchLine->id,
                    ],
                ]
            );

            $activeLineIds[] = (int) $line->id;

            $task = StorageTask::query()->firstOrNew([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'document_line_id' => $line->id,
                'task_type' => 'transfer_receipt',
            ]);

            $wasNew = ! $task->exists;
            $task->fill([
                'location_id' => $document->location_id,
                'area_id' => $line->to_area_id,
                'slot_id' => $line->to_slot_id,
                'status' => in_array($task->status, ['done', 'cancelled'], true) ? $task->status : 'open',
                'priority' => 'high',
                'required_scan_mode' => 'optional',
                'queue_name' => 'transfer_receipt',
                'requested_by' => $document->requested_by ?: $userId,
                'target_qty' => $quantity,
                'meta' => [
                    'suggested_staging_slot_id' => $line->to_slot_id,
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
                        'suggested_staging_slot_id' => $line->to_slot_id,
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

    protected function loadTransferPair(int $businessId, int $transferId): array
    {
        $sellTransfer = Transaction::query()
            ->with([
                'location',
                'sell_lines.product',
                'sell_lines.variations',
            ])
            ->where('business_id', $businessId)
            ->where('type', 'sell_transfer')
            ->findOrFail($transferId);

        $purchaseTransfer = Transaction::query()
            ->with([
                'location',
                'purchase_lines',
            ])
            ->where('business_id', $businessId)
            ->where('transfer_parent_id', $transferId)
            ->where('type', 'purchase_transfer')
            ->first();

        if (! $purchaseTransfer) {
            throw new RuntimeException('Linked destination transfer document was not found.');
        }

        return [$sellTransfer, $purchaseTransfer];
    }

    protected function markTransferInTransit(Transaction $sellTransfer, Transaction $purchaseTransfer): void
    {
        if ($sellTransfer->status === 'final' || $purchaseTransfer->status === 'received') {
            return;
        }

        $sellTransfer->status = 'in_transit';
        $sellTransfer->save();

        $purchaseTransfer->status = 'in_transit';
        $purchaseTransfer->save();
    }

    protected function markTransferCompleted(int $businessId, Transaction $sellTransfer, Transaction $purchaseTransfer): void
    {
        if ($purchaseTransfer->status === 'received' && $sellTransfer->status === 'final') {
            return;
        }

        foreach ($sellTransfer->sell_lines as $sellLine) {
            if ($sellLine->product && $sellLine->product->enable_stock) {
                $this->productUtil->decreaseProductQuantity(
                    $sellLine->product_id,
                    $sellLine->variation_id,
                    $sellTransfer->location_id,
                    $sellLine->quantity
                );

                $this->productUtil->updateProductQuantity(
                    $purchaseTransfer->location_id,
                    $sellLine->product_id,
                    $sellLine->variation_id,
                    $sellLine->quantity,
                    0,
                    null,
                    false
                );
            }
        }

        $this->productUtil->adjustStockOverSelling($purchaseTransfer);

        $business = [
            'id' => $businessId,
            'accounting_method' => session()->get('business.accounting_method'),
            'location_id' => $sellTransfer->location_id,
        ];
        $this->transactionUtil->mapPurchaseSell($business, $sellTransfer->sell_lines, 'purchase');

        $purchaseTransfer->status = 'received';
        $purchaseTransfer->save();

        $sellTransfer->status = 'final';
        $sellTransfer->save();
    }

    protected function locationSettingForLocation(int $businessId, int $locationId): StorageLocationSetting
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

    protected function pickAvailableSourceBucket(
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

    protected function defaultDispatchSlotId(int $businessId, int $locationId, StorageLocationSetting $settings): ?int
    {
        if ($settings->default_dispatch_area_id) {
            $slotId = StorageSlot::query()
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->where('area_id', $settings->default_dispatch_area_id)
                ->active()
                ->orderBy('pick_sequence')
                ->orderBy('putaway_sequence')
                ->value('id');

            if ($slotId) {
                return (int) $slotId;
            }
        }

        return StorageSlot::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->active()
            ->orderBy('pick_sequence')
            ->orderBy('putaway_sequence')
            ->value('id');
    }

    protected function defaultTransferReceiptSlotId(int $businessId, int $locationId, StorageLocationSetting $settings): ?int
    {
        $preferredAreaIds = array_filter([
            $settings->default_staging_area_id,
            $settings->default_receiving_area_id,
        ]);

        if (! empty($preferredAreaIds)) {
            $slotId = StorageSlot::query()
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->whereIn('area_id', $preferredAreaIds)
                ->active()
                ->orderBy('putaway_sequence')
                ->orderBy('pick_sequence')
                ->value('id');

            if ($slotId) {
                return (int) $slotId;
            }
        }

        return StorageSlot::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->active()
            ->orderBy('putaway_sequence')
            ->orderBy('pick_sequence')
            ->value('id');
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
