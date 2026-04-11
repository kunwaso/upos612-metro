<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Http\Requests\ConfirmTransferReceiptRequest;
use Modules\StorageManager\Http\Requests\CompleteTransferDispatchRequest;
use Modules\StorageManager\Services\TransferExecutionService;
use Modules\StorageManager\Utils\StorageManagerToolbarNavUtil;
use Modules\StorageManager\Utils\StorageManagerUtil;

class TransferExecutionController extends Controller
{
    public function __construct(
        protected TransferExecutionService $transferExecutionService,
        protected StorageManagerUtil $storageManagerUtil
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
        $board = $this->transferExecutionService->boardForLocation($businessId, $locationId ?: null);

        return view('storagemanager::transfers.index', [
            'locations' => $locations,
            'locationId' => $locationId,
            'dispatchSummary' => $board['dispatchSummary'],
            'dispatchRows' => $board['dispatchRows'],
            'receiptRows' => $board['receiptRows'],
            'storageToolbarTitle' => __('lang_v1.transfer_execution'),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.transfer_execution'), 'url' => null],
            ], $locationId > 0 ? $locationId : null),
            'storageToolbarLocationId' => $locationId > 0 ? $locationId : null,
        ]);
    }

    public function showDispatch(int $transfer)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $context = $this->transferExecutionService->getDispatchWorkbench($businessId, $transfer, (int) request()->session()->get('user.id'));
        $context['sourceSummary']['can_confirm'] = ! empty($context['sourceSummary']['can_confirm']) && auth()->user()->can('storage_manager.operate');

        $document = $context['document'];
        $locId = (int) $document->location_id;

        return view('storagemanager::transfers.dispatch', array_merge($context, [
            'storageToolbarTitle' => (string) ($document->document_no ?: __('lang_v1.transfer_dispatch')),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.transfer_execution'), 'url' => route('storage-manager.transfers.index')],
                ['label' => __('lang_v1.dispatch_workbench'), 'url' => null],
            ], $locId > 0 ? $locId : null),
            'storageToolbarLocationId' => $locId > 0 ? $locId : null,
        ]));
    }

    public function confirmDispatch(CompleteTransferDispatchRequest $request, int $document)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'transfer_dispatch')
            ->findOrFail($document);

        $allowSourceStatusUpdate = auth()->user()->can('stock_transfer.update');

        try {
            $dispatchDocument = $this->transferExecutionService->confirmDispatch(
                $businessId,
                $documentModel,
                $request->validated(),
                $userId,
                $allowSourceStatusUpdate
            );

            return redirect()
                ->route('storage-manager.transfers.dispatch.show', $dispatchDocument->source_id)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.transfer_dispatch_confirmed_successfully'),
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

    public function showReceipt(int $transfer)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $context = $this->transferExecutionService->getReceiptWorkbench($businessId, $transfer, (int) request()->session()->get('user.id'));
        $context['sourceSummary']['can_confirm'] = ! empty($context['sourceSummary']['can_confirm']) && auth()->user()->can('storage_manager.operate');

        $document = $context['document'];
        $locId = (int) $document->location_id;

        return view('storagemanager::transfers.receipt', array_merge($context, [
            'storageToolbarTitle' => (string) ($document->document_no ?: __('lang_v1.transfer_receipt')),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.transfer_execution'), 'url' => route('storage-manager.transfers.index')],
                ['label' => __('lang_v1.receipt_workbench'), 'url' => null],
            ], $locId > 0 ? $locId : null),
            'storageToolbarLocationId' => $locId > 0 ? $locId : null,
        ]));
    }

    public function confirmReceipt(ConfirmTransferReceiptRequest $request, int $document)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'transfer_receipt')
            ->findOrFail($document);

        $allowSourceStatusUpdate = auth()->user()->can('stock_transfer.update');

        try {
            $receiptDocument = $this->transferExecutionService->confirmReceipt(
                $businessId,
                $documentModel,
                $request->validated(),
                $userId,
                $allowSourceStatusUpdate
            );

            return redirect()
                ->route('storage-manager.transfers.receipts.show', $receiptDocument->source_id)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.transfer_receipt_confirmed_successfully'),
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
}
