<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Http\Requests\CompletePackRequest;
use Modules\StorageManager\Http\Requests\CompletePickRequest;
use Modules\StorageManager\Http\Requests\CompleteShipRequest;
use Modules\StorageManager\Services\OutboundExecutionService;
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

        return view('storagemanager::outbound.pick', $context);
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

        return view('storagemanager::outbound.pack', $context);
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

        return view('storagemanager::outbound.ship', $context);
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
