<?php

namespace Modules\StorageManager\Services;

use App\PurchaseLine;
use App\Transaction;
use App\Utils\ProductUtil;
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
use RuntimeException;

class ReceivingService
{
    public function __construct(
        protected SourceDocumentAdapterManager $adapterManager,
        protected InventoryMovementService $inventoryMovementService,
        protected PutawayService $putawayService,
        protected WarehouseSyncService $warehouseSyncService,
        protected ProductUtil $productUtil,
        protected TransactionUtil $transactionUtil
    ) {
    }

    public function expectedReceiptBoard(int $businessId, ?int $locationId = null): array
    {
        $settings = StorageLocationSetting::query()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->get()
            ->keyBy('location_id');

        $enabledLocationIds = $settings
            ->filter(fn (StorageLocationSetting $setting) => (string) $setting->execution_mode !== 'off')
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $receiptDocuments = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'receipt')
            ->whereIn('source_type', ['purchase', 'purchase_order'])
            ->get()
            ->keyBy(fn (StorageDocument $document) => $document->source_type . ':' . $document->source_id);

        $purchases = Transaction::query()
            ->with(['contact', 'location', 'purchase_lines'])
            ->where('business_id', $businessId)
            ->where('type', 'purchase')
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->whereIn('status', ['pending', 'ordered', 'received'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Transaction $transaction) => $transaction->purchase_lines->isNotEmpty())
            ->map(function (Transaction $transaction) use ($receiptDocuments, $settings) {
                $document = $receiptDocuments->get('purchase:' . $transaction->id);
                $setting = $settings->get($transaction->location_id);
                $executionMode = (string) ($setting?->execution_mode ?: 'off');
                $executionEnabled = $executionMode !== 'off';
                $sourceStatus = (string) $transaction->status;
                $planningOnly = $sourceStatus === 'ordered';

                return [
                    'source_type' => 'purchase',
                    'source_id' => (int) $transaction->id,
                    'source_ref' => (string) ($transaction->ref_no ?: $transaction->invoice_no ?: ('PUR-' . $transaction->id)),
                    'source_status' => $sourceStatus,
                    'location_name' => (string) optional($transaction->location)->name,
                    'supplier_name' => (string) (optional($transaction->contact)->supplier_business_name ?: optional($transaction->contact)->name ?: '—'),
                    'transaction_date' => ! empty($transaction->transaction_date)
                        ? Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i')
                        : null,
                    'line_count' => (int) $transaction->purchase_lines->count(),
                    'expected_qty' => round((float) $transaction->purchase_lines->sum('quantity'), 4),
                    'receipt_document_id' => $document?->id,
                    'receipt_document_no' => $document?->document_no,
                    'receipt_status' => $document?->status,
                    'execution_mode' => $executionMode,
                    'ready_for_execution' => $executionEnabled && ! $planningOnly,
                    'planning_only' => $planningOnly,
                    'can_open' => $executionEnabled && ! $planningOnly,
                    'action_note' => ! $executionEnabled
                        ? 'Enable storage execution for this location to receive and put away stock.'
                        : ($planningOnly ? 'Planning only. Purchase is still in ordered status after reversal.' : null),
                ];
            })
            ->values();

        $purchaseOrders = Transaction::query()
            ->with(['contact', 'location', 'purchase_lines'])
            ->where('business_id', $businessId)
            ->where('type', 'purchase_order')
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->where('status', '!=', 'completed')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Transaction $transaction) use ($receiptDocuments, $settings) {
                $document = $receiptDocuments->get('purchase_order:' . $transaction->id);
                $setting = $settings->get($transaction->location_id);
                $executionMode = (string) ($setting?->execution_mode ?: 'off');
                $remainingQty = round((float) $transaction->purchase_lines->sum(function (PurchaseLine $line) {
                    return max((float) $line->quantity - (float) $line->po_quantity_purchased, 0);
                }), 4);
                $remainingLines = $transaction->purchase_lines->filter(function (PurchaseLine $line) {
                    return round(max((float) $line->quantity - (float) $line->po_quantity_purchased, 0), 4) > 0;
                });

                return [
                    'source_type' => 'purchase_order',
                    'source_id' => (int) $transaction->id,
                    'source_ref' => (string) ($transaction->ref_no ?: $transaction->invoice_no ?: ('PO-' . $transaction->id)),
                    'source_status' => (string) $transaction->status,
                    'location_name' => (string) optional($transaction->location)->name,
                    'supplier_name' => (string) (optional($transaction->contact)->supplier_business_name ?: optional($transaction->contact)->name ?: '—'),
                    'transaction_date' => ! empty($transaction->transaction_date)
                        ? Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i')
                        : null,
                    'line_count' => (int) $remainingLines->count(),
                    'expected_qty' => $remainingQty,
                    'receipt_document_id' => $document?->id,
                    'receipt_document_no' => $document?->document_no,
                    'receipt_status' => $document?->status,
                    'execution_mode' => $executionMode,
                    'ready_for_execution' => false,
                    'planning_only' => true,
                    'can_open' => $remainingQty > 0,
                    'action_note' => $remainingQty > 0
                        ? 'Planning only. Confirm receipt and putaway become available after creating/receiving the purchase from this PO.'
                        : 'No remaining quantity on this PO.',
                ];
            })
            ->values();

        $planningPurchases = $purchases
            ->filter(fn (array $row) => ! empty($row['planning_only']))
            ->values();
        $purchases = $purchases
            ->reject(fn (array $row) => ! empty($row['planning_only']))
            ->values();
        if ($planningPurchases->isNotEmpty()) {
            $purchaseOrders = $planningPurchases->concat($purchaseOrders)->values();
        }

        return [
            'executionSummary' => [
                'enabled_location_count' => (int) count($enabledLocationIds),
                'purchase_count' => (int) $purchases->count(),
                'purchase_order_count' => (int) $purchaseOrders->count(),
            ],
            'purchases' => $purchases,
            'purchaseOrders' => $purchaseOrders,
        ];
    }

    public function getReceiptWorkbench(int $businessId, string $sourceType, int $sourceId, int $userId): array
    {
        $sourceDocument = $this->loadSourceDocumentByKey($businessId, $sourceType, $sourceId);
        $settings = $this->locationSettingForSource(
            $businessId,
            (int) $sourceDocument->location_id,
            $sourceType === 'purchase_order'
        );
        $document = $this->ensureReceiptDocument($businessId, $sourceType, $sourceDocument, $settings, $userId);
        $this->syncReceiptLinesFromSource($document, $sourceType, $sourceDocument, $settings);

        $document = $document->fresh([
            'lines.product',
            'lines.variation',
            'lines.toArea',
            'lines.toSlot',
        ]);

        $stagingSlotOptions = $this->putawayService->slotOptionsForLocation(
            $businessId,
            (int) $document->location_id,
            array_filter([
                $settings->default_staging_area_id,
                $settings->default_receiving_area_id,
            ])
        );

        if (empty($stagingSlotOptions)) {
            $stagingSlotOptions = $this->putawayService->slotOptionsForLocation($businessId, (int) $document->location_id);
        }

        $putawayDocument = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'putaway')
            ->where('parent_document_id', $document->id)
            ->first();

        $canReopen = false;
        $reopenReason = null;
        if (! in_array((string) $document->status, ['completed', 'closed'], true)) {
            $reopenReason = 'Receipt can only be reopened after confirmation.';
        } elseif ((string) $document->sync_status === 'posted') {
            $reopenReason = 'Receipt is already posted to accounting and cannot be reopened.';
        } elseif ($putawayDocument && in_array((string) $putawayDocument->status, ['closed', 'completed'], true)) {
            $reopenReason = 'Reverse putaway first before reopening this receipt.';
        } else {
            $canReopen = true;
        }

        $lineRows = $document->lines->map(function (StorageDocumentLine $line) use ($businessId, $document) {
            $suggestedDestination = $this->putawayService->suggestSlotForProduct(
                $businessId,
                (int) $document->location_id,
                (int) $line->product_id,
                $line->variation_id ? (int) $line->variation_id : null
            );

            return [
                'id' => (int) $line->id,
                'product_label' => (string) optional($line->product)->name,
                'sku' => (string) (optional($line->variation)->sub_sku ?: optional($line->product)->sku ?: '—'),
                'expected_qty' => (float) $line->expected_qty,
                'executed_qty' => (float) ($line->executed_qty ?: $line->expected_qty),
                'lot_number' => (string) ($line->lot_number ?: ''),
                'expiry_date' => optional($line->expiry_date)->toDateString(),
                'staging_slot_id' => $line->to_slot_id,
                'staging_area_label' => (string) (optional($line->toArea)->name ?: '—'),
                'source_status' => (string) $document->status,
                'destination_hint' => $suggestedDestination ? ($suggestedDestination->slot_code ?: $suggestedDestination->id) : '—',
            ];
        })->values();

        return [
            'document' => $document,
            'sourceSummary' => [
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_ref' => (string) ($document->source_ref ?: $sourceId),
                'status' => (string) $sourceDocument->status,
                'transaction_date' => ! empty($sourceDocument->transaction_date)
                    ? Carbon::parse($sourceDocument->transaction_date)->format('Y-m-d H:i')
                    : null,
                'supplier_name' => (string) (optional($sourceDocument->contact)->supplier_business_name ?: optional($sourceDocument->contact)->name ?: '—'),
                'location_name' => (string) optional($sourceDocument->location)->name,
                'execution_mode' => (string) $settings->execution_mode,
                'planning_only' => $sourceType === 'purchase_order',
                'can_confirm' => $sourceType === 'purchase'
                    && ! in_array($document->status, ['completed', 'closed', 'cancelled'], true),
                'requires_purchase_permission' => $sourceType === 'purchase' && $sourceDocument->status !== 'received',
                'has_putaway_document' => (bool) $putawayDocument,
                'putaway_document_id' => $putawayDocument?->id,
                'can_reopen' => $canReopen,
                'reopen_reason' => $reopenReason,
            ],
            'lineRows' => $lineRows,
            'stagingSlotOptions' => $stagingSlotOptions,
        ];
    }

    public function loadSourceDocument(StorageDocument $document)
    {
        return $this->loadSourceDocumentByKey((int) $document->business_id, (string) $document->source_type, (int) $document->source_id);
    }

    public function confirmReceipt(
        int $businessId,
        StorageDocument $document,
        array $payload,
        int $userId,
        bool $allowSourceStatusUpdate = false
    ): StorageDocument {
        if ($document->document_type !== 'receipt') {
            throw new RuntimeException('Only receipt documents can be confirmed from the receiving workbench.');
        }

        if (in_array($document->status, ['completed', 'closed', 'cancelled'], true)) {
            throw new RuntimeException('This receipt document is already completed.');
        }

        $sourceDocument = $this->loadSourceDocument($document);
        if ($document->source_type !== 'purchase') {
            throw new RuntimeException('Purchase orders are planning-only in this phase. Create or link a purchase before receiving stock.');
        }

        $settings = $this->locationSettingForSource($businessId, (int) $document->location_id);
        $lineInputs = (array) ($payload['lines'] ?? []);

        DB::transaction(function () use ($businessId, $document, $sourceDocument, $settings, $lineInputs, $userId, $allowSourceStatusUpdate) {
            $document->loadMissing(['lines']);
            $sourceDocument->loadMissing('purchase_lines');
            $documentMeta = (array) $document->meta;
            $receiptAttempt = (int) data_get($documentMeta, 'receipt_attempt', 0) + 1;
            $documentMeta['receipt_attempt'] = $receiptAttempt;
            $documentMeta['last_receipt_confirmed_at'] = now()->toDateTimeString();
            $sourceStatusBeforeReceipt = (string) $sourceDocument->status;
            $documentMeta['source_status_before_receipt'] = $sourceStatusBeforeReceipt;
            $documentMeta['source_status_changed_by_receipt'] = false;

            foreach ($document->lines as $line) {
                $input = (array) ($lineInputs[$line->id] ?? []);
                $stagingSlotId = isset($input['staging_slot_id']) && $input['staging_slot_id'] !== ''
                    ? (int) $input['staging_slot_id']
                    : ((int) ($line->to_slot_id ?: $this->defaultStagingSlotId($businessId, (int) $document->location_id, $settings)));
                $executedQty = round((float) ($input['executed_qty'] ?? $line->expected_qty), 4);
                $expectedQty = round((float) $line->expected_qty, 4);

                if ($stagingSlotId <= 0) {
                    throw new RuntimeException("A staging slot is required for receipt line [{$line->id}].");
                }

                if ($executedQty <= 0) {
                    throw new RuntimeException("Executed quantity must be greater than zero for receipt line [{$line->id}].");
                }

                if ($executedQty !== $expectedQty) {
                    throw new RuntimeException('This phase requires executed receipt quantity to match the purchase quantity exactly.');
                }

                $stagingSlot = StorageSlot::query()
                    ->where('business_id', $businessId)
                    ->where('location_id', $document->location_id)
                    ->active()
                    ->find($stagingSlotId);

                if (! $stagingSlot) {
                    throw new RuntimeException("Staging slot [{$stagingSlotId}] is invalid for receipt line [{$line->id}].");
                }

                $lotNumber = trim((string) ($input['lot_number'] ?? $line->lot_number));
                $expiryDate = ! empty($input['expiry_date'])
                    ? Carbon::parse($input['expiry_date'])->toDateString()
                    : optional($line->expiry_date)->toDateString();

                if ($settings->require_lot_tracking && $lotNumber === '') {
                    throw new RuntimeException('Lot number is required for this warehouse location.');
                }

                if ($settings->require_expiry_tracking && empty($expiryDate)) {
                    throw new RuntimeException('Expiry date is required for this warehouse location.');
                }

                $line->forceFill([
                    'to_area_id' => $stagingSlot->area_id,
                    'to_slot_id' => $stagingSlotId,
                    'executed_qty' => $executedQty,
                    'variance_qty' => 0,
                    'inventory_status' => 'staged_in',
                    'result_status' => 'received',
                    'lot_number' => $lotNumber,
                    'expiry_date' => $expiryDate,
                ])->save();

                PurchaseLine::query()
                    ->where('id', $line->source_line_id)
                    ->where('transaction_id', $sourceDocument->id)
                    ->update([
                        'lot_number' => $lotNumber !== '' ? $lotNumber : null,
                        'exp_date' => $expiryDate,
                    ]);
            }

            if ($sourceDocument->status !== 'received') {
                if (! $allowSourceStatusUpdate) {
                    throw new RuntimeException('This purchase must be marked received before warehouse receipt can be completed.');
                }

                $this->markPurchaseAsReceived($businessId, $sourceDocument);
                $documentMeta['source_status_changed_by_receipt'] = true;
            }
            $documentMeta['source_status_after_receipt'] = (string) $sourceDocument->status;

            foreach ($document->lines as $line) {
                $this->inventoryMovementService->applyMovement([
                    'business_id' => $businessId,
                    'location_id' => $document->location_id,
                    'document_id' => $document->id,
                    'document_line_id' => $line->id,
                    'source_type' => $document->source_type,
                    'source_id' => $document->source_id,
                    'source_line_id' => $line->source_line_id,
                    'movement_type' => 'receipt',
                    'direction' => 'in',
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'to_area_id' => $line->to_area_id,
                    'to_slot_id' => $line->to_slot_id,
                    'to_status' => 'staged_in',
                    'lot_number' => $line->lot_number,
                    'expiry_date' => optional($line->expiry_date)->toDateString(),
                    'quantity' => $line->executed_qty,
                    'unit_cost' => $line->unit_cost,
                    'reason_code' => 'receipt',
                    'idempotency_key' => 'receipt-' . $document->id . '-line-' . $line->id . '-attempt-' . $receiptAttempt,
                    'created_by' => $userId,
                ]);
            }

            $document->forceFill([
                'status' => 'completed',
                'workflow_state' => 'putaway_pending',
                'completed_at' => now(),
                'approved_by' => $userId,
                'meta' => $documentMeta,
            ])->save();
        });

        $receiptDocument = $document->fresh([
            'lines.product',
            'lines.variation',
            'lines.toArea',
            'lines.toSlot',
        ]);

        $this->putawayService->ensureDocumentForReceipt($receiptDocument, $userId);

        try {
            $this->warehouseSyncService->syncDocument($receiptDocument, $userId);
        } catch (\Throwable $exception) {
            // Sync errors are already logged in StorageSyncLog and surfaced in Control Tower.
        }

        return $receiptDocument->fresh([
            'lines.product',
            'lines.variation',
            'lines.toArea',
            'lines.toSlot',
        ]);
    }

    public function reopenReceipt(int $businessId, StorageDocument $document, int $userId): StorageDocument
    {
        if ($document->document_type !== 'receipt') {
            throw new RuntimeException('Only receipt documents can be reopened from inbound receiving.');
        }

        if (! in_array((string) $document->status, ['completed', 'closed'], true)) {
            throw new RuntimeException('Only completed receipts can be reopened.');
        }

        if ((string) $document->sync_status === 'posted') {
            throw new RuntimeException('This receipt is already posted to accounting and cannot be reopened.');
        }

        $putawayDocument = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'putaway')
            ->where('parent_document_id', $document->id)
            ->first();

        if ($putawayDocument && in_array((string) $putawayDocument->status, ['closed', 'completed'], true)) {
            throw new RuntimeException('Reverse putaway first before reopening this receipt.');
        }

        $sourceDocument = $this->loadSourceDocument($document);

        DB::transaction(function () use ($businessId, $document, $putawayDocument, $sourceDocument, $userId) {
            $document->loadMissing('lines');
            $documentMeta = (array) $document->meta;
            $receiptAttempt = max((int) data_get($documentMeta, 'receipt_attempt', 1), 1);

            foreach ($document->lines as $line) {
                $quantity = round((float) ($line->executed_qty ?: $line->expected_qty), 4);
                if ($quantity <= 0) {
                    continue;
                }

                $stagingSlotId = (int) ($line->to_slot_id ?: 0);
                if ($stagingSlotId <= 0) {
                    throw new RuntimeException("Staging slot is missing for receipt line [{$line->id}].");
                }

                $this->inventoryMovementService->applyMovement([
                    'business_id' => $businessId,
                    'location_id' => $document->location_id,
                    'document_id' => $document->id,
                    'document_line_id' => $line->id,
                    'source_type' => $document->source_type,
                    'source_id' => $document->source_id,
                    'source_line_id' => $line->source_line_id,
                    'movement_type' => 'receipt_reopen',
                    'direction' => 'out',
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'from_area_id' => $line->to_area_id,
                    'from_slot_id' => $stagingSlotId,
                    'from_status' => 'staged_in',
                    'lot_number' => $line->lot_number,
                    'expiry_date' => optional($line->expiry_date)->toDateString(),
                    'quantity' => $quantity,
                    'unit_cost' => $line->unit_cost,
                    'reason_code' => 'receipt_reopen',
                    'idempotency_key' => 'receipt-reopen-' . $document->id . '-line-' . $line->id . '-attempt-' . $receiptAttempt,
                    'created_by' => $userId,
                ]);

                $line->forceFill([
                    'executed_qty' => round((float) $line->expected_qty, 4),
                    'variance_qty' => 0,
                    'inventory_status' => 'receiving',
                    'result_status' => 'pending',
                ])->save();
            }

            $sourceStatusBeforeReceipt = strtolower(trim((string) data_get($documentMeta, 'source_status_before_receipt', '')));
            $sourceStatusChangedByReceipt = (bool) data_get($documentMeta, 'source_status_changed_by_receipt', false);
            if (
                $document->source_type === 'purchase'
                && $sourceStatusChangedByReceipt
                && $sourceStatusBeforeReceipt !== ''
                && $sourceStatusBeforeReceipt !== 'received'
                && (string) $sourceDocument->status === 'received'
            ) {
                $this->markPurchaseAsStatus($businessId, $sourceDocument, $sourceStatusBeforeReceipt);
                $documentMeta['source_status_restored_on_reopen'] = $sourceStatusBeforeReceipt;
            }
            $documentMeta['source_status_after_receipt'] = (string) $sourceDocument->status;
            $documentMeta['source_status_changed_by_receipt'] = false;

            $nextSyncStatus = (string) $document->sync_status === 'not_required'
                ? 'not_required'
                : 'pending_sync';
            $documentMeta['last_receipt_reopened_at'] = now()->toDateTimeString();
            $documentMeta['last_receipt_reopened_attempt'] = $receiptAttempt;

            $document->forceFill([
                'status' => 'open',
                'workflow_state' => 'pending_receipt',
                'completed_at' => null,
                'closed_at' => null,
                'approved_by' => null,
                'closed_by' => null,
                'sync_status' => $nextSyncStatus,
                'meta' => $documentMeta,
            ])->save();

            if ($putawayDocument) {
                $putawayDocument->forceFill([
                    'status' => 'open',
                    'workflow_state' => 'pending',
                    'completed_at' => null,
                    'closed_at' => null,
                    'closed_by' => null,
                ])->save();
            }
        });

        $receiptDocument = $document->fresh([
            'lines.product',
            'lines.variation',
            'lines.toArea',
            'lines.toSlot',
        ]);

        $this->putawayService->ensureDocumentForReceipt($receiptDocument, $userId);

        return $receiptDocument->fresh([
            'lines.product',
            'lines.variation',
            'lines.toArea',
            'lines.toSlot',
        ]);
    }

    protected function ensureReceiptDocument(
        int $businessId,
        string $sourceType,
        Transaction $sourceDocument,
        StorageLocationSetting $settings,
        int $userId
    ): StorageDocument {
        $document = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'receipt')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceDocument->id)
            ->first();

        if (! $document) {
            $sourceRef = (string) ($sourceDocument->ref_no ?: $sourceDocument->invoice_no ?: ($sourceType . '-' . $sourceDocument->id));
            $document = new StorageDocument([
                'business_id' => $businessId,
                'location_id' => $sourceDocument->location_id,
                'area_id' => $settings->default_staging_area_id ?: $settings->default_receiving_area_id,
                'document_no' => 'TMP-RCV-' . Str::uuid(),
                'document_type' => 'receipt',
                'source_type' => $sourceType,
                'source_id' => $sourceDocument->id,
                'source_ref' => $sourceRef,
                'status' => $sourceType === 'purchase_order' ? 'draft' : 'open',
                'workflow_state' => $sourceType === 'purchase_order' ? 'expected' : 'pending_receipt',
                'execution_mode' => $settings->execution_mode,
                'sync_status' => 'not_required',
                'requested_by' => $userId,
                'created_by' => $userId,
                'notes' => 'Generated from ' . $sourceType . ' ' . $sourceRef,
                'meta' => [
                    'location_name' => optional($sourceDocument->location)->name,
                    'supplier_name' => optional($sourceDocument->contact)->supplier_business_name ?: optional($sourceDocument->contact)->name,
                    'planning_only' => $sourceType === 'purchase_order',
                ],
            ]);
            $document->save();
            $document->forceFill([
                'document_no' => 'RCV-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
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
                'linked_type' => $sourceType,
                'linked_id' => $sourceDocument->id,
                'linked_ref' => (string) ($sourceDocument->ref_no ?: $sourceDocument->invoice_no ?: $sourceDocument->id),
                'sync_status' => 'not_required',
                'meta' => [
                    'source_status' => $sourceDocument->status,
                ],
            ]
        );

        return $document;
    }

    protected function syncReceiptLinesFromSource(
        StorageDocument $document,
        string $sourceType,
        Transaction $sourceDocument,
        StorageLocationSetting $settings
    ): void {
        if (in_array($document->status, ['completed', 'closed', 'cancelled'], true)) {
            return;
        }

        $sourceLines = $this->sourceLinesForDocument($sourceType, $sourceDocument);
        $defaultStagingAreaId = $settings->default_staging_area_id ?: $settings->default_receiving_area_id;
        $defaultStagingSlotId = $this->defaultStagingSlotId((int) $document->business_id, (int) $document->location_id, $settings);
        $activeLineIds = [];

        foreach ($sourceLines as $index => $sourceLine) {
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
                    'to_area_id' => $defaultStagingAreaId,
                    'to_slot_id' => $defaultStagingSlotId,
                    'expected_qty' => $sourceLine['expected_qty'],
                    'executed_qty' => $sourceLine['executed_qty'],
                    'variance_qty' => 0,
                    'unit_cost' => $sourceLine['unit_cost'],
                    'inventory_status' => 'receiving',
                    'result_status' => 'pending',
                    'lot_number' => $sourceLine['lot_number'],
                    'expiry_date' => $sourceLine['expiry_date'],
                    'meta' => [
                        'source_status' => $sourceDocument->status,
                    ],
                ]
            );

            $activeLineIds[] = (int) $line->id;
        }

        StorageDocumentLine::query()
            ->where('business_id', $document->business_id)
            ->where('document_id', $document->id)
            ->when(! empty($activeLineIds), fn ($query) => $query->whereNotIn('id', $activeLineIds))
            ->delete();
    }

    protected function sourceLinesForDocument(string $sourceType, Transaction $sourceDocument): Collection
    {
        $sourceDocument->loadMissing(['purchase_lines.product', 'purchase_lines.variations']);

        return $sourceDocument->purchase_lines
            ->map(function (PurchaseLine $line) use ($sourceType) {
                $expectedQty = round((float) $line->quantity, 4);

                if ($sourceType === 'purchase_order') {
                    $expectedQty = round(max((float) $line->quantity - (float) $line->po_quantity_purchased, 0), 4);
                }

                return [
                    'source_line_id' => (int) $line->id,
                    'product_id' => (int) $line->product_id,
                    'variation_id' => $line->variation_id ? (int) $line->variation_id : null,
                    'expected_qty' => $expectedQty,
                    'executed_qty' => $sourceType === 'purchase_order' ? 0 : $expectedQty,
                    'unit_cost' => round((float) $line->purchase_price, 4),
                    'lot_number' => (string) ($line->lot_number ?: ''),
                    'expiry_date' => $line->exp_date,
                ];
            })
            ->filter(fn (array $line) => $line['expected_qty'] > 0)
            ->values();
    }

    protected function loadSourceDocumentByKey(int $businessId, string $sourceType, int $sourceId): Transaction
    {
        if (! in_array($sourceType, ['purchase', 'purchase_order'], true)) {
            throw new RuntimeException("Unsupported inbound source type [{$sourceType}].");
        }

        $sourceDocument = $this->adapterManager->resolve($sourceType)->load($businessId, $sourceId);
        if (! $sourceDocument instanceof Transaction) {
            throw new RuntimeException('Inbound source document must resolve to a transaction.');
        }

        $sourceDocument->loadMissing(['contact', 'location', 'purchase_lines.product', 'purchase_lines.variations']);

        return $sourceDocument;
    }

    protected function locationSettingForSource(int $businessId, int $locationId, bool $allowDisabled = false): StorageLocationSetting
    {
        $settings = StorageLocationSetting::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->first();

        if (! $settings && $allowDisabled) {
            $settings = new StorageLocationSetting([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'execution_mode' => 'off',
                'status' => 'active',
            ]);
        }

        if (! $settings || ($settings->execution_mode === 'off' && ! $allowDisabled)) {
            throw new RuntimeException('Storage execution is not enabled for this location.');
        }

        return $settings;
    }

    protected function defaultStagingSlotId(int $businessId, int $locationId, StorageLocationSetting $settings): ?int
    {
        $preferredAreaIds = array_values(array_filter([
            $settings->default_staging_area_id,
            $settings->default_receiving_area_id,
        ]));

        $slotId = StorageSlot::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->active()
            ->when(! empty($preferredAreaIds), fn ($query) => $query->whereIn('area_id', $preferredAreaIds))
            ->orderBy('putaway_sequence')
            ->orderBy('pick_sequence')
            ->orderBy('row')
            ->orderBy('position')
            ->value('id');

        if ($slotId) {
            return (int) $slotId;
        }

        $fallback = StorageSlot::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->active()
            ->orderBy('putaway_sequence')
            ->orderBy('pick_sequence')
            ->orderBy('row')
            ->orderBy('position')
            ->value('id');

        return $fallback ? (int) $fallback : null;
    }

    protected function markPurchaseAsReceived(int $businessId, Transaction $purchase): void
    {
        $this->markPurchaseAsStatus($businessId, $purchase, 'received');
    }

    protected function markPurchaseAsStatus(int $businessId, Transaction $purchase, string $targetStatus): void
    {
        $targetStatus = strtolower(trim($targetStatus));
        if (! in_array($targetStatus, ['pending', 'ordered', 'received'], true)) {
            throw new RuntimeException("Unsupported purchase status [{$targetStatus}] for receiving rollback.");
        }

        $purchase->loadMissing('purchase_lines');
        $beforeStatus = (string) $purchase->status;
        if ($beforeStatus === $targetStatus) {
            return;
        }

        $purchase->update(['status' => $targetStatus]);

        $currencyDetails = $this->transactionUtil->purchaseCurrencyDetails($businessId);
        foreach ($purchase->purchase_lines as $purchaseLine) {
            $this->productUtil->updateProductStock(
                $beforeStatus,
                $purchase,
                $purchaseLine->product_id,
                $purchaseLine->variation_id,
                $purchaseLine->quantity,
                $purchaseLine->quantity,
                $currencyDetails
            );
        }

        $this->transactionUtil->adjustMappingPurchaseSellAfterEditingPurchase($beforeStatus, $purchase, null);
        if ($targetStatus === 'received') {
            $this->productUtil->adjustStockOverSelling($purchase);
        }
    }
}
