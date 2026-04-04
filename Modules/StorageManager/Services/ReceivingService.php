<?php

namespace Modules\StorageManager\Services;

use App\PurchaseLine;
use App\Transaction;
use App\User;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLine;
use Modules\StorageManager\Entities\StorageDocumentLink;
use Modules\StorageManager\Entities\StorageLocationSetting;
use Modules\StorageManager\Entities\StorageSlot;
use RuntimeException;

class ReceivingService
{
    public const GENERATED_PURCHASE_SOURCE = 'storage_manager_purchase_order_receiving';

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

        $openGeneratedPurchasesByOrderId = Transaction::query()
            ->with(['contact', 'location', 'purchase_lines'])
            ->where('business_id', $businessId)
            ->where('type', 'purchase')
            ->where('source', self::GENERATED_PURCHASE_SOURCE)
            ->whereIn('status', ['pending', 'ordered'])
            ->orderByDesc('id')
            ->get()
            ->reduce(function (array $carry, Transaction $transaction) {
                foreach ((array) $transaction->purchase_order_ids as $purchaseOrderId) {
                    $purchaseOrderId = (int) $purchaseOrderId;
                    if ($purchaseOrderId > 0 && ! isset($carry[$purchaseOrderId])) {
                        $carry[$purchaseOrderId] = $transaction;
                    }
                }

                return $carry;
            }, []);

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
            ->reject(fn (Transaction $transaction) => (string) $transaction->source === self::GENERATED_PURCHASE_SOURCE)
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
            ->map(function (Transaction $transaction) use ($receiptDocuments, $settings, $openGeneratedPurchasesByOrderId) {
                $document = $receiptDocuments->get('purchase_order:' . $transaction->id);
                $setting = $settings->get($transaction->location_id);
                $executionMode = (string) ($setting?->execution_mode ?: 'off');
                $executionEnabled = $executionMode !== 'off';
                $generatedPurchase = $openGeneratedPurchasesByOrderId[(int) $transaction->id] ?? null;
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
                    'ready_for_execution' => $executionEnabled && $remainingQty > 0,
                    'planning_only' => false,
                    'can_open' => $executionEnabled && $remainingQty > 0,
                    'has_open_generated_purchase' => (bool) $generatedPurchase,
                    'generated_purchase_id' => $generatedPurchase?->id,
                    'action_note' => ! $executionEnabled
                        ? 'Enable storage execution for this location to receive goods from this purchase order.'
                        : ($remainingQty > 0
                            ? null
                            : 'No remaining quantity on this purchase order.'),
                    'receive_action_label' => $generatedPurchase
                        ? 'Continue Receiving'
                        : 'Receive Goods',
                    'receive_action_note' => $generatedPurchase
                        ? 'Reopen the existing warehouse receipt for this purchase order.'
                        : 'Create a warehouse receipt from this purchase order.',
                ];
            })
            ->values();

        $purchases = $purchases
            ->reject(fn (array $row) => ! empty($row['planning_only']))
            ->values();

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

    public function startPurchaseOrderReceiving(int $businessId, int $purchaseOrderId, int $userId): Transaction
    {
        $purchaseOrder = $this->loadSourceDocumentByKey($businessId, 'purchase_order', $purchaseOrderId);
        $purchaseOrder->loadMissing(['contact', 'location', 'purchase_lines.product', 'purchase_lines.variations']);
        $settings = $this->locationSettingForSource($businessId, (int) $purchaseOrder->location_id);

        $remainingLines = $this->remainingPurchaseOrderLines($purchaseOrder);
        if ($remainingLines->isEmpty()) {
            throw new RuntimeException('This purchase order has no remaining quantity available to receive.');
        }

        return DB::transaction(function () use ($businessId, $purchaseOrder, $settings, $remainingLines, $userId) {
            $generatedPurchase = $this->findOpenGeneratedPurchaseForOrder($businessId, (int) $purchaseOrder->id);
            $purchaseLineLinks = [];

            if (! $generatedPurchase) {
                [$generatedPurchase, $purchaseLineLinks] = $this->createGeneratedPurchaseFromPurchaseOrder(
                    $businessId,
                    $purchaseOrder,
                    $remainingLines,
                    $userId
                );
            }

            $receiptDocument = $this->ensureReceiptDocument($businessId, 'purchase', $generatedPurchase, $settings, $userId);
            $this->syncGeneratedReceiptMetadata($receiptDocument, $purchaseOrder, $purchaseLineLinks);

            return $generatedPurchase->fresh(['contact', 'location', 'purchase_lines.product', 'purchase_lines.variations']);
        });
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

        $generatedReceiptMeta = $this->generatedPurchaseOrderReceiptMeta($document);
        $generatedFromPurchaseOrder = ! empty($generatedReceiptMeta['generated_from_purchase_order']);
        $canReopen = false;
        $reopenReason = null;
        if (! in_array((string) $document->status, ['completed', 'closed'], true)) {
            $reopenReason = 'Receipt can only be reopened after confirmation.';
        } elseif ($generatedFromPurchaseOrder) {
            $reopenReason = 'Receipts generated from purchase orders cannot be reopened in this phase.';
        } elseif ((string) $document->sync_status === 'posted') {
            $reopenReason = 'Receipt is already posted to accounting. Unlink accounting first, then reopen.';
        } elseif ($putawayDocument && in_array((string) $putawayDocument->status, ['closed', 'completed'], true)) {
            $reopenReason = 'Reverse putaway first before reopening this receipt.';
        } else {
            $canReopen = true;
        }

        $vasFlags = $this->resolveVasSyncFlags($document);

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
                'received_qty' => (float) $line->executed_qty,
                'destination_hint' => $suggestedDestination ? ($suggestedDestination->slot_code ?: $suggestedDestination->id) : '—',
            ];
        })->values();

        $grnFields = $this->defaultGrnFields($document, $userId, $sourceDocument);
        $grnRoute = in_array((string) $document->status, ['completed', 'closed'], true)
            && $document->source_type === 'purchase'
            && Route::has('storage-manager.inbound.grn.show')
            ? route('storage-manager.inbound.grn.show', $document->id)
            : null;

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
                'generated_from_purchase_order' => $generatedFromPurchaseOrder,
                'purchase_order_id' => ! empty($generatedReceiptMeta['purchase_order_id']) ? (int) $generatedReceiptMeta['purchase_order_id'] : null,
                'purchase_order_ref' => $generatedReceiptMeta['purchase_order_ref'] ?? null,
                'can_confirm' => $sourceType === 'purchase'
                    && ! in_array($document->status, ['completed', 'closed', 'cancelled'], true),
                'requires_purchase_permission' => $sourceType === 'purchase'
                    && $sourceDocument->status !== 'received'
                    && ! $generatedFromPurchaseOrder,
                'has_putaway_document' => (bool) $putawayDocument,
                'putaway_document_id' => $putawayDocument?->id,
                'can_reopen' => $canReopen,
                'reopen_reason' => $reopenReason,
                'can_sync_vas' => $vasFlags['can_sync'],
                'sync_vas_block_reason' => $vasFlags['sync_block_reason'],
                'can_unlink_vas' => $vasFlags['can_unlink'],
                'unlink_vas_block_reason' => $vasFlags['unlink_block_reason'],
                'vas_inventory_document_id' => $vasFlags['vas_inventory_document_id'],
                'vas_document_show_route' => $vasFlags['vas_document_show_route'],
                'can_view_grn' => ! empty($grnRoute),
                'grn_route' => $grnRoute,
            ],
            'lineRows' => $lineRows,
            'stagingSlotOptions' => $stagingSlotOptions,
            'grnFields' => $grnFields,
        ];
    }

    public function loadSourceDocument(StorageDocument $document)
    {
        return $this->loadSourceDocumentByKey((int) $document->business_id, (string) $document->source_type, (int) $document->source_id);
    }

    public function goodsReceivedNoteContext(int $businessId, StorageDocument $document): array
    {
        if ($document->business_id !== $businessId || $document->document_type !== 'receipt') {
            throw new RuntimeException('Goods received notes are only available for warehouse receipt documents.');
        }

        if (! in_array((string) $document->status, ['completed', 'closed'], true)) {
            throw new RuntimeException('Complete the receipt before printing the goods received note.');
        }

        if ((string) $document->source_type !== 'purchase') {
            throw new RuntimeException('Goods received notes are only available for purchase receipts in this phase.');
        }

        $document = $document->fresh(['lines.product.unit', 'lines.variation']);
        $sourceDocument = $this->loadSourceDocument($document);
        $sourceDocument->loadMissing(['contact', 'location', 'purchase_lines']);

        $grn = $this->defaultGrnFields($document, (int) ($document->approved_by ?: $document->created_by), $sourceDocument);
        $supplier = $sourceDocument->contact;
        $supplierAddress = collect([
            $supplier?->supplier_business_name,
            $supplier?->name,
            $supplier?->address_line_1,
            $supplier?->address_line_2,
            $supplier?->city,
            $supplier?->state,
            $supplier?->country,
            $supplier?->zip_code,
        ])->filter()->unique()->implode(', ');
        $supplierContact = collect([
            $supplier?->mobile,
            $supplier?->landline,
            $supplier?->email,
        ])->filter()->implode(' | ');

        $purchaseLines = $sourceDocument->purchase_lines->keyBy('id');
        $items = $document->lines->map(function (StorageDocumentLine $line) use ($purchaseLines) {
            /** @var \App\PurchaseLine|null $purchaseLine */
            $purchaseLine = $purchaseLines->get($line->source_line_id);
            $unitPrice = round((float) ($purchaseLine?->purchase_price ?: $line->unit_cost), 4);
            $receivedQty = round((float) $line->executed_qty, 4);
            $orderedQty = round((float) $line->expected_qty, 4);

            return [
                'item' => (string) (optional($line->product)->name ?: '—'),
                'description' => (string) (optional($line->variation)->name ?: optional($line->variation)->sub_sku ?: ''),
                'unit_of_measure' => (string) (optional(optional($line->product)->unit)->short_name ?: ''),
                'quantity_ordered' => $orderedQty,
                'quantity_received' => $receivedQty,
                'unit_price' => $unitPrice,
                'total_price' => round($receivedQty * $unitPrice, 4),
            ];
        })->values();

        return [
            'document' => $document,
            'sourceDocument' => $sourceDocument,
            'grn' => $grn,
            'supplierName' => (string) ($supplier?->supplier_business_name ?: $supplier?->name ?: '—'),
            'supplierAddress' => $supplierAddress ?: '—',
            'supplierContact' => $supplierContact ?: '—',
            'items' => $items,
            'totalItems' => round((float) $items->sum('quantity_received'), 4),
            'totalAmount' => round((float) $items->sum('total_price'), 4),
        ];
    }

    protected function resolveVasSyncFlags(StorageDocument $document): array
    {
        $isCompleted = in_array((string) $document->status, ['completed', 'closed'], true);
        $syncStatus = (string) ($document->sync_status ?? 'not_required');
        $hasVasLink = ! empty($document->vas_inventory_document_id);
        $vasTablesExist = Schema::hasTable('vas_inventory_documents') && Schema::hasTable('vas_warehouses');

        $canSync = false;
        $syncBlockReason = null;
        $canUnlink = false;
        $unlinkBlockReason = null;

        if (! $vasTablesExist) {
            $syncBlockReason = __('lang_v1.vas_tables_not_available');
            $unlinkBlockReason = $syncBlockReason;
        } elseif (! $isCompleted) {
            $syncBlockReason = __('lang_v1.receipt_must_be_completed_before_sync');
            $unlinkBlockReason = $syncBlockReason;
        } elseif ($syncStatus === 'posted') {
            $syncBlockReason = __('lang_v1.already_posted_to_accounting');
            $canUnlink = true;
        } elseif (in_array($syncStatus, ['synced_unposted'], true)) {
            $canSync = true;
            $canUnlink = true;
        } else {
            $canSync = true;
            if ($hasVasLink) {
                $canUnlink = true;
            }
        }

        $vasDocumentShowRoute = null;
        if ($hasVasLink && Route::has('vasaccounting.inventory.documents.show')) {
            $vasDocumentShowRoute = route('vasaccounting.inventory.documents.show', $document->vas_inventory_document_id);
        }

        return [
            'can_sync' => $canSync,
            'sync_block_reason' => $syncBlockReason,
            'can_unlink' => $canUnlink,
            'unlink_block_reason' => $unlinkBlockReason,
            'vas_inventory_document_id' => $hasVasLink ? (int) $document->vas_inventory_document_id : null,
            'vas_document_show_route' => $vasDocumentShowRoute,
        ];
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

        $generatedFromPurchaseOrder = $this->isGeneratedPurchaseOrderReceipt($document);
        $settings = $this->locationSettingForSource($businessId, (int) $document->location_id);
        $lineInputs = (array) ($payload['lines'] ?? []);

        DB::transaction(function () use ($businessId, $document, $sourceDocument, $settings, $lineInputs, $userId, $allowSourceStatusUpdate, $payload, $generatedFromPurchaseOrder) {
            $document->loadMissing(['lines']);
            $sourceDocument->loadMissing('purchase_lines');
            $documentMeta = (array) $document->meta;
            $receiptAttempt = (int) data_get($documentMeta, 'receipt_attempt', 0) + 1;
            $documentMeta['receipt_attempt'] = $receiptAttempt;
            $documentMeta['last_receipt_confirmed_at'] = now()->toDateTimeString();
            $sourceStatusBeforeReceipt = (string) $sourceDocument->status;
            $documentMeta['source_status_before_receipt'] = $sourceStatusBeforeReceipt;
            $documentMeta['source_status_changed_by_receipt'] = false;
            $documentMeta['grn'] = $this->normalizeGrnPayload($document, $payload, $userId, $sourceDocument);
            $receivedLineCount = 0;

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

                if ($generatedFromPurchaseOrder && $executedQty > $expectedQty) {
                    throw new RuntimeException('Received quantity cannot exceed the remaining purchase order quantity.');
                }

                if (! $generatedFromPurchaseOrder && $executedQty !== $expectedQty) {
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

                if ($executedQty > 0) {
                    $receivedLineCount++;
                }

                $line->forceFill([
                    'to_area_id' => $stagingSlot->area_id,
                    'to_slot_id' => $stagingSlotId,
                    'executed_qty' => $executedQty,
                    'variance_qty' => round($expectedQty - $executedQty, 4),
                    'inventory_status' => 'staged_in',
                    'result_status' => 'received',
                    'lot_number' => $lotNumber,
                    'expiry_date' => $expiryDate,
                ])->save();

                if (! $generatedFromPurchaseOrder) {
                    PurchaseLine::query()
                        ->where('id', $line->source_line_id)
                        ->where('transaction_id', $sourceDocument->id)
                        ->update([
                            'lot_number' => $lotNumber !== '' ? $lotNumber : null,
                            'exp_date' => $expiryDate,
                        ]);
                }
            }

            if ($generatedFromPurchaseOrder && $receivedLineCount === 0) {
                throw new RuntimeException('Enter at least one received quantity before confirming this purchase order receipt.');
            }

            if ($generatedFromPurchaseOrder) {
                $this->applyGeneratedPurchaseReceiptAdjustments($sourceDocument, $document);
                $purchaseOrderId = (int) data_get($documentMeta, 'storage_manager.purchase_order_id', 0);
                if ($purchaseOrderId > 0) {
                    $this->transactionUtil->updatePurchaseOrderStatus([$purchaseOrderId]);
                }
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
                'notes' => $documentMeta['grn']['comments'] ?? $document->notes,
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

        if (config('storagemanager.inbound_vas_sync', 'manual') === 'auto') {
            try {
                $this->warehouseSyncService->syncDocument($receiptDocument, $userId);
            } catch (\Throwable $exception) {
            }
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
        return $this->reopenReceiptInternal($businessId, $document, $userId, false, true);
    }

    public function reopenGeneratedPurchaseOrderReceiptForDeletion(int $businessId, StorageDocument $document, int $userId): StorageDocument
    {
        return $this->reopenReceiptInternal($businessId, $document, $userId, true, false);
    }

    protected function reopenReceiptInternal(
        int $businessId,
        StorageDocument $document,
        int $userId,
        bool $allowGeneratedPurchaseOrderReversal,
        bool $ensurePutawayDocument
    ): StorageDocument
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

        if ($this->isGeneratedPurchaseOrderReceipt($document) && ! $allowGeneratedPurchaseOrderReversal) {
            throw new RuntimeException('Receipts generated from purchase orders cannot be reopened in this phase.');
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

        if ($ensurePutawayDocument) {
            $this->putawayService->ensureDocumentForReceipt($receiptDocument, $userId);
        }

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
            $lineMeta = [
                'source_status' => $sourceDocument->status,
            ];
            $purchaseOrderLineId = (int) data_get($document->meta, 'storage_manager.purchase_line_links.' . $sourceLine['source_line_id'], 0);
            if ($purchaseOrderLineId > 0) {
                $lineMeta['source_purchase_order_line_id'] = $purchaseOrderLineId;
            }

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
                    'meta' => $lineMeta,
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

    protected function remainingPurchaseOrderLines(Transaction $purchaseOrder): Collection
    {
        $purchaseOrder->loadMissing(['purchase_lines.product', 'purchase_lines.variations']);

        return $purchaseOrder->purchase_lines
            ->map(function (PurchaseLine $line) {
                $remainingQty = round(max((float) $line->quantity - (float) $line->po_quantity_purchased, 0), 4);
                $secondaryUnitQuantity = round((float) $line->secondary_unit_quantity, 4);
                if ((float) $line->quantity > 0 && $secondaryUnitQuantity > 0) {
                    $secondaryUnitQuantity = round($secondaryUnitQuantity * ($remainingQty / (float) $line->quantity), 4);
                }

                return [
                    'purchase_order_line_id' => (int) $line->id,
                    'product_id' => (int) $line->product_id,
                    'variation_id' => $line->variation_id ? (int) $line->variation_id : null,
                    'quantity' => $remainingQty,
                    'secondary_unit_quantity' => $secondaryUnitQuantity,
                    'pp_without_discount' => round((float) $line->pp_without_discount, 4),
                    'discount_percent' => round((float) $line->discount_percent, 4),
                    'purchase_price' => round((float) $line->purchase_price, 4),
                    'purchase_price_inc_tax' => round((float) $line->purchase_price_inc_tax, 4),
                    'item_tax' => round((float) $line->item_tax, 4),
                    'tax_id' => $line->tax_id ? (int) $line->tax_id : null,
                    'lot_number' => (string) ($line->lot_number ?: ''),
                    'exp_date' => $line->exp_date,
                    'sub_unit_id' => $line->sub_unit_id ? (int) $line->sub_unit_id : null,
                    'product_unit_id' => optional($line->product)->unit_id ? (int) optional($line->product)->unit_id : null,
                ];
            })
            ->filter(fn (array $line) => $line['quantity'] > 0)
            ->values();
    }

    protected function findOpenGeneratedPurchaseForOrder(int $businessId, int $purchaseOrderId): ?Transaction
    {
        return Transaction::query()
            ->with(['contact', 'location', 'purchase_lines.product', 'purchase_lines.variations'])
            ->where('business_id', $businessId)
            ->where('type', 'purchase')
            ->where('source', self::GENERATED_PURCHASE_SOURCE)
            ->whereIn('status', ['pending', 'ordered'])
            ->orderByDesc('id')
            ->get()
            ->first(function (Transaction $transaction) use ($purchaseOrderId) {
                return collect((array) $transaction->purchase_order_ids)
                    ->map(fn ($id) => (int) $id)
                    ->contains($purchaseOrderId);
            });
    }

    protected function createGeneratedPurchaseFromPurchaseOrder(
        int $businessId,
        Transaction $purchaseOrder,
        Collection $remainingLines,
        int $userId
    ): array {
        $refCount = $this->productUtil->setAndGetReferenceCount('purchase');
        $refNo = $this->productUtil->generateReferenceNumber('purchase', $refCount);
        $totalBeforeTax = round((float) $remainingLines->sum(fn (array $line) => $line['purchase_price'] * $line['quantity']), 4);
        $taxAmount = round((float) $remainingLines->sum(fn (array $line) => $line['item_tax'] * $line['quantity']), 4);
        $finalTotal = round((float) $remainingLines->sum(fn (array $line) => $line['purchase_price_inc_tax'] * $line['quantity']), 4);
        $sourceRef = (string) ($purchaseOrder->ref_no ?: $purchaseOrder->invoice_no ?: ('PO-' . $purchaseOrder->id));

        $purchase = Transaction::create([
            'business_id' => $businessId,
            'location_id' => $purchaseOrder->location_id,
            'type' => 'purchase',
            'status' => 'pending',
            'payment_status' => 'due',
            'contact_id' => $purchaseOrder->contact_id,
            'ref_no' => $refNo,
            'source' => self::GENERATED_PURCHASE_SOURCE,
            'transaction_date' => ! empty($purchaseOrder->transaction_date)
                ? Carbon::parse($purchaseOrder->transaction_date)
                : now(),
            'total_before_tax' => $totalBeforeTax,
            'tax_id' => $purchaseOrder->tax_id,
            'tax_amount' => $taxAmount,
            'discount_type' => null,
            'discount_amount' => 0,
            'shipping_details' => $purchaseOrder->shipping_details,
            'shipping_address' => $purchaseOrder->shipping_address,
            'delivery_date' => $purchaseOrder->delivery_date,
            'shipping_charges' => 0,
            'additional_notes' => 'Generated by StorageManager from purchase order ' . $sourceRef,
            'final_total' => $finalTotal,
            'exchange_rate' => $purchaseOrder->exchange_rate ?: 1,
            'created_by' => $userId,
            'pay_term_number' => $purchaseOrder->pay_term_number,
            'pay_term_type' => $purchaseOrder->pay_term_type,
            'purchase_order_ids' => [(int) $purchaseOrder->id],
        ]);

        $purchaseLineLinks = [];
        foreach ($remainingLines as $line) {
            $purchaseLine = $purchase->purchase_lines()->create([
                'product_id' => $line['product_id'],
                'variation_id' => $line['variation_id'],
                'quantity' => $line['quantity'],
                'secondary_unit_quantity' => $line['secondary_unit_quantity'],
                'pp_without_discount' => $line['pp_without_discount'],
                'discount_percent' => $line['discount_percent'],
                'purchase_price' => $line['purchase_price'],
                'purchase_price_inc_tax' => $line['purchase_price_inc_tax'],
                'item_tax' => $line['item_tax'],
                'tax_id' => $line['tax_id'],
                'purchase_order_line_id' => null,
                'lot_number' => $line['lot_number'] !== '' ? $line['lot_number'] : null,
                'exp_date' => $line['exp_date'],
                'sub_unit_id' => $line['sub_unit_id'],
            ]);

            $purchaseLineLinks[(string) $purchaseLine->id] = (int) $line['purchase_order_line_id'];
        }

        return [$purchase->fresh(['contact', 'location', 'purchase_lines.product', 'purchase_lines.variations']), $purchaseLineLinks];
    }

    protected function syncGeneratedReceiptMetadata(
        StorageDocument $document,
        Transaction $purchaseOrder,
        array $purchaseLineLinks = []
    ): void {
        $documentMeta = (array) $document->meta;
        $storageManagerMeta = (array) data_get($documentMeta, 'storage_manager', []);
        $mergedLinks = (array) ($storageManagerMeta['purchase_line_links'] ?? []);
        foreach ($purchaseLineLinks as $purchaseLineId => $purchaseOrderLineId) {
            if ((int) $purchaseOrderLineId > 0) {
                $mergedLinks[(string) $purchaseLineId] = (int) $purchaseOrderLineId;
            }
        }

        $storageManagerMeta['generated_from_purchase_order'] = true;
        $storageManagerMeta['purchase_order_id'] = (int) $purchaseOrder->id;
        $storageManagerMeta['purchase_order_ref'] = (string) ($purchaseOrder->ref_no ?: $purchaseOrder->invoice_no ?: ('PO-' . $purchaseOrder->id));
        $storageManagerMeta['purchase_line_links'] = $mergedLinks;
        $documentMeta['storage_manager'] = $storageManagerMeta;

        $document->forceFill(['meta' => $documentMeta])->save();
    }

    protected function generatedPurchaseOrderReceiptMeta(StorageDocument $document): array
    {
        return (array) data_get((array) $document->meta, 'storage_manager', []);
    }

    protected function isGeneratedPurchaseOrderReceipt(StorageDocument $document): bool
    {
        return (bool) data_get((array) $document->meta, 'storage_manager.generated_from_purchase_order', false);
    }

    protected function defaultGrnFields(StorageDocument $document, int $userId, ?Transaction $sourceDocument = null): array
    {
        $stored = (array) data_get((array) $document->meta, 'grn', []);
        $userName = $this->displayUserName(User::find($userId));

        return [
            'grn_number' => (string) $document->document_no,
            'grn_date' => optional($document->completed_at)->toDateString() ?: now()->toDateString(),
            'delivery_note_number' => (string) ($stored['delivery_note_number'] ?? ''),
            'delivery_date' => ! empty($stored['delivery_date'])
                ? Carbon::parse($stored['delivery_date'])->toDateString()
                : (! empty($sourceDocument?->delivery_date) ? Carbon::parse($sourceDocument->delivery_date)->toDateString() : ''),
            'carrier_driver_name' => (string) ($stored['carrier_driver_name'] ?? ''),
            'received_by_name' => (string) ($stored['received_by_name'] ?? $userName),
            'receiving_department' => (string) ($stored['receiving_department'] ?? 'Warehouse Receiving'),
            'received_condition' => (string) ($stored['received_condition'] ?? ''),
            'comments' => (string) ($stored['comments'] ?? ''),
        ];
    }

    protected function normalizeGrnPayload(StorageDocument $document, array $payload, int $userId, ?Transaction $sourceDocument = null): array
    {
        $defaults = $this->defaultGrnFields($document, $userId, $sourceDocument);

        return [
            'delivery_note_number' => trim((string) ($payload['delivery_note_number'] ?? $defaults['delivery_note_number'] ?? '')),
            'delivery_date' => ! empty($payload['delivery_date'])
                ? Carbon::parse($payload['delivery_date'])->toDateString()
                : ($defaults['delivery_date'] ?: null),
            'carrier_driver_name' => trim((string) ($payload['carrier_driver_name'] ?? $defaults['carrier_driver_name'] ?? '')),
            'received_by_name' => trim((string) ($payload['received_by_name'] ?? $defaults['received_by_name'] ?? '')),
            'receiving_department' => trim((string) ($payload['receiving_department'] ?? $defaults['receiving_department'] ?? '')),
            'received_condition' => trim((string) ($payload['received_condition'] ?? $defaults['received_condition'] ?? '')),
            'comments' => trim((string) ($payload['comments'] ?? $defaults['comments'] ?? '')),
        ];
    }

    protected function displayUserName(?User $user): string
    {
        if (! $user) {
            return '';
        }

        $parts = array_filter([
            $user->surname,
            $user->first_name,
            $user->last_name,
        ]);

        return trim(implode(' ', $parts));
    }

    protected function applyGeneratedPurchaseReceiptAdjustments(Transaction $purchase, StorageDocument $document): void
    {
        $purchase->loadMissing('purchase_lines');
        $document->loadMissing('lines');
        $purchaseLineLinks = (array) data_get((array) $document->meta, 'storage_manager.purchase_line_links', []);

        foreach ($document->lines as $line) {
            /** @var \App\PurchaseLine|null $purchaseLine */
            $purchaseLine = $purchase->purchase_lines->firstWhere('id', (int) $line->source_line_id);
            if (! $purchaseLine) {
                throw new RuntimeException("Unable to match purchase line [{$line->source_line_id}] for generated PO receipt.");
            }

            $purchaseLine->quantity = round((float) $line->executed_qty, 4);
            $purchaseLine->lot_number = $line->lot_number !== '' ? $line->lot_number : null;
            $purchaseLine->exp_date = optional($line->expiry_date)->toDateString();
            $purchaseLine->purchase_order_line_id = ! empty($purchaseLineLinks[(string) $purchaseLine->id])
                ? (int) $purchaseLineLinks[(string) $purchaseLine->id]
                : null;
            $purchaseLine->save();

            if (! empty($purchaseLine->purchase_order_line_id)) {
                $this->productUtil->updatePurchaseOrderLine($purchaseLine->purchase_order_line_id, $purchaseLine->quantity, 0);
            }
        }

        $purchase->unsetRelation('purchase_lines');
        $purchase->load('purchase_lines');
        $purchase->forceFill([
            'total_before_tax' => round((float) $purchase->purchase_lines->sum(fn (PurchaseLine $line) => (float) $line->purchase_price * (float) $line->quantity), 4),
            'tax_amount' => round((float) $purchase->purchase_lines->sum(fn (PurchaseLine $line) => (float) $line->item_tax * (float) $line->quantity), 4),
            'final_total' => round((float) $purchase->purchase_lines->sum(fn (PurchaseLine $line) => (float) $line->purchase_price_inc_tax * (float) $line->quantity), 4),
        ])->save();
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
