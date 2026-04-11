<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Http\Requests\CompletePackRequest;
use Modules\StorageManager\Http\Requests\CompletePickRequest;
use Modules\StorageManager\Http\Requests\CompleteShipRequest;
use Modules\StorageManager\Services\OutboundExecutionService;
use Modules\StorageManager\Utils\StorageManagerToolbarNavUtil;
use Modules\StorageManager\Utils\StorageManagerUtil;

class OutboundExecutionController extends Controller
{
    public function __construct(
        protected OutboundExecutionService $outboundExecutionService,
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
        $board = $this->outboundExecutionService->boardForLocation($businessId, $locationId ?: null);

        return view('storagemanager::outbound.index', [
            'locations' => $locations,
            'locationId' => $locationId,
            'summary' => $board['summary'],
            'pickRows' => $board['pickRows'],
            'packRows' => $board['packRows'],
            'shipRows' => $board['shipRows'],
            'storageToolbarTitle' => __('lang_v1.outbound_execution'),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.outbound_execution'), 'url' => null],
            ], $locationId > 0 ? $locationId : null),
            'storageToolbarLocationId' => $locationId > 0 ? $locationId : null,
        ]);
    }

    public function showPick(int $salesOrder)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $context = $this->outboundExecutionService->getPickWorkbench($businessId, $salesOrder, (int) request()->session()->get('user.id'));
        $context['orderSummary']['can_confirm'] = ! empty($context['orderSummary']['can_confirm']) && auth()->user()->can('storage_manager.operate');

        $document = $context['document'];
        $locId = (int) $document->location_id;

        return view('storagemanager::outbound.pick', array_merge($context, [
            'storageToolbarTitle' => (string) ($document->document_no ?: __('lang_v1.pick_workbench')),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.outbound_execution'), 'url' => route('storage-manager.outbound.index')],
                ['label' => __('lang_v1.pick_workbench'), 'url' => null],
            ], $locId > 0 ? $locId : null),
            'storageToolbarLocationId' => $locId > 0 ? $locId : null,
        ]));
    }

    public function confirmPick(CompletePickRequest $request, int $document)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'pick')
            ->findOrFail($document);

        try {
            $pickDocument = $this->outboundExecutionService->confirmPick(
                $businessId,
                $documentModel,
                $request->validated(),
                $userId
            );

            return redirect()
                ->route('storage-manager.outbound.pick.show', $pickDocument->source_id)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.pick_confirmed_successfully'),
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

    public function showPack(int $salesOrder)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $context = $this->outboundExecutionService->getPackWorkbench($businessId, $salesOrder, (int) request()->session()->get('user.id'));
        $context['orderSummary']['can_confirm'] = ! empty($context['orderSummary']['can_confirm']) && auth()->user()->can('storage_manager.operate');

        $document = $context['document'];
        $locId = (int) $document->location_id;

        return view('storagemanager::outbound.pack', array_merge($context, [
            'storageToolbarTitle' => (string) ($document->document_no ?: __('lang_v1.pack_workbench')),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.outbound_execution'), 'url' => route('storage-manager.outbound.index')],
                ['label' => __('lang_v1.pack_workbench'), 'url' => null],
            ], $locId > 0 ? $locId : null),
            'storageToolbarLocationId' => $locId > 0 ? $locId : null,
        ]));
    }

    public function confirmPack(CompletePackRequest $request, int $document)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'pack')
            ->findOrFail($document);

        try {
            $packDocument = $this->outboundExecutionService->confirmPack(
                $businessId,
                $documentModel,
                $request->validated(),
                $userId,
                $this->canUpdateShippingStatus()
            );

            return redirect()
                ->route('storage-manager.outbound.pack.show', $packDocument->source_id)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.pack_confirmed_successfully'),
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

    public function showShip(int $salesOrder)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $context = $this->outboundExecutionService->getShipWorkbench($businessId, $salesOrder, (int) request()->session()->get('user.id'));
        $context['orderSummary']['can_confirm'] = ! empty($context['orderSummary']['can_confirm']) && auth()->user()->can('storage_manager.operate');

        $document = $context['document'];
        $locId = (int) $document->location_id;

        return view('storagemanager::outbound.ship', array_merge($context, [
            'storageToolbarTitle' => (string) ($document->document_no ?: __('lang_v1.ship_workbench')),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.outbound_execution'), 'url' => route('storage-manager.outbound.index')],
                ['label' => __('lang_v1.ship_workbench'), 'url' => null],
            ], $locId > 0 ? $locId : null),
            'storageToolbarLocationId' => $locId > 0 ? $locId : null,
        ]));
    }

    public function confirmShip(CompleteShipRequest $request, int $document)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $documentModel = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'ship')
            ->findOrFail($document);

        try {
            $shipDocument = $this->outboundExecutionService->confirmShip(
                $businessId,
                $documentModel,
                $request->validated(),
                $userId,
                $this->canUpdateShippingStatus()
            );

            return redirect()
                ->route('storage-manager.outbound.ship.show', $shipDocument->source_id)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.ship_confirmed_successfully'),
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

    protected function canUpdateShippingStatus(): bool
    {
        return auth()->user()->hasAnyPermission([
            'access_shipping',
            'access_own_shipping',
            'access_commission_agent_shipping',
        ]);
    }
}
