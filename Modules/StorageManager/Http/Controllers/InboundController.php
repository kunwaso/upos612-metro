<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Http\Requests\ConfirmReceiptRequest;
use Modules\StorageManager\Http\Requests\SyncInboundReceiptVasRequest;
use Modules\StorageManager\Http\Requests\UnlinkInboundReceiptVasRequest;
use Modules\StorageManager\Services\ReceivingService;
use Modules\StorageManager\Services\WarehouseSyncService;
use Modules\StorageManager\Utils\StorageManagerUtil;
use Modules\StorageManager\Utils\StorageVasReceiptSyncUtil;

class InboundController extends Controller
{
    public function __construct(
        protected ReceivingService $receivingService,
        protected StorageManagerUtil $storageManagerUtil,
        protected WarehouseSyncService $warehouseSyncService,
        protected StorageVasReceiptSyncUtil $vasReceiptSyncUtil
    ) {
    }

    public function index()
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $locations = $this->storageManagerUtil->getLocationsDropdown($businessId);
        $locationId = (int) request('location_id', 0);
        $board = $this->receivingService->expectedReceiptBoard($businessId, $locationId ?: null);

        return view('storagemanager::inbound.index', [
            'locations' => $locations,
            'locationId' => $locationId,
            'executionSummary' => $board['executionSummary'],
            'purchases' => $board['purchases'],
            'purchaseOrders' => $board['purchaseOrders'],
        ]);
    }

    public function show(string $sourceType, int $sourceId)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $context = $this->receivingService->getReceiptWorkbench($businessId, $sourceType, $sourceId, (int) request()->session()->get('user.id'));
        $context['sourceSummary']['can_confirm'] = ! empty($context['sourceSummary']['can_confirm']) && auth()->user()->can('storage_manager.operate');

        return view('storagemanager::inbound.show', $context);
    }

    public function startPurchaseOrderReceiving(Request $request, int $purchaseOrder)
    {
        if (! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        try {
            $generatedPurchase = $this->receivingService->startPurchaseOrderReceiving($businessId, $purchaseOrder, $userId);

            return redirect()
                ->route('storage-manager.inbound.show', [
                    'sourceType' => 'purchase',
                    'sourceId' => $generatedPurchase->id,
                ])
                ->with('status', [
                    'success' => true,
                    'msg' => 'Purchase order opened in inbound receiving.',
                ]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->with('status', [
                    'success' => false,
                    'msg' => $exception->getMessage(),
                ]);
        }
    }

    public function confirm(ConfirmReceiptRequest $request, int $document)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'receipt')
            ->findOrFail($document);

        $sourceDocument = $this->receivingService->loadSourceDocument($documentModel);
        $generatedFromPurchaseOrder = (bool) data_get((array) $documentModel->meta, 'storage_manager.generated_from_purchase_order', false);
        $allowSourceStatusUpdate = $generatedFromPurchaseOrder
            || $sourceDocument->status === 'received'
            || auth()->user()->can('purchase.update')
            || auth()->user()->can('purchase.update_status');

        try {
            $receiptDocument = $this->receivingService->confirmReceipt(
                $businessId,
                $documentModel,
                $request->validated(),
                $userId,
                $allowSourceStatusUpdate
            );

            return redirect()
                ->route('storage-manager.inbound.show', [
                    'sourceType' => $receiptDocument->source_type,
                    'sourceId' => $receiptDocument->source_id,
                ])
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.receipt_confirmed_successfully'),
                ]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', [
                    'success' => false,
                    'msg' => $exception->getMessage(),
                ]);
        }
    }

    public function showGrn(Request $request, int $document)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) $request->session()->get('user.business_id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'receipt')
            ->whereIn('status', ['completed', 'closed'])
            ->findOrFail($document);

        if ((string) $documentModel->source_type !== 'purchase') {
            abort(404);
        }

        return view('storagemanager::inbound.grn', $this->receivingService->goodsReceivedNoteContext($businessId, $documentModel));
    }

    public function reopen(Request $request, int $document)
    {
        if (! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'receipt')
            ->findOrFail($document);

        try {
            $receiptDocument = $this->receivingService->reopenReceipt(
                $businessId,
                $documentModel,
                $userId
            );

            return redirect()
                ->route('storage-manager.inbound.show', [
                    'sourceType' => $receiptDocument->source_type,
                    'sourceId' => $receiptDocument->source_id,
                ])
                ->with('status', [
                    'success' => true,
                    'msg' => 'Receipt reopened successfully.',
                ]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->with('status', [
                    'success' => false,
                    'msg' => $exception->getMessage(),
                ]);
        }
    }

    public function syncVas(SyncInboundReceiptVasRequest $request, int $document)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'receipt')
            ->whereIn('status', ['completed', 'closed'])
            ->findOrFail($document);

        try {
            $this->warehouseSyncService->syncDocument($documentModel, $userId);

            return redirect()
                ->route('storage-manager.inbound.show', [
                    'sourceType' => $documentModel->source_type,
                    'sourceId' => $documentModel->source_id,
                ])
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.vas_sync_completed'),
                ]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->with('status', [
                    'success' => false,
                    'msg' => $exception->getMessage(),
                ]);
        }
    }

    public function unlinkVas(UnlinkInboundReceiptVasRequest $request, int $document)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'receipt')
            ->whereIn('status', ['completed', 'closed'])
            ->findOrFail($document);

        try {
            $this->vasReceiptSyncUtil->unlinkReceiptVasSync($businessId, $documentModel, $userId);

            return redirect()
                ->route('storage-manager.inbound.show', [
                    'sourceType' => $documentModel->source_type,
                    'sourceId' => $documentModel->source_id,
                ])
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.vas_unlink_completed'),
                ]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->with('status', [
                    'success' => false,
                    'msg' => $exception->getMessage(),
                ]);
        }
    }
}
