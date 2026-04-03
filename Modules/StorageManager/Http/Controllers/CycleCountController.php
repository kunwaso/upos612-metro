<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\StorageManager\Entities\StorageArea;
use Modules\StorageManager\Entities\StorageCountSession;
use Modules\StorageManager\Http\Requests\ApproveCycleCountShortagesRequest;
use Modules\StorageManager\Http\Requests\CreateCycleCountSessionRequest;
use Modules\StorageManager\Http\Requests\SubmitCycleCountRequest;
use Modules\StorageManager\Services\CycleCountService;
use Modules\StorageManager\Utils\StorageManagerUtil;

class CycleCountController extends Controller
{
    public function __construct(
        protected CycleCountService $cycleCountService,
        protected StorageManagerUtil $storageManagerUtil
    ) {
    }

    public function index()
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.count')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $locations = $this->storageManagerUtil->getLocationsDropdown($businessId);
        $locationId = (int) request('location_id', 0);
        $board = $this->cycleCountService->boardForLocation($businessId, $locationId ?: null);
        $areaOptions = $locationId > 0
            ? StorageArea::query()->where('business_id', $businessId)->where('location_id', $locationId)->orderBy('name')->get()
            : collect();

        return view('storagemanager::counts.index', [
            'locations' => $locations,
            'locationId' => $locationId,
            'areaOptions' => $areaOptions,
            'boardSummary' => $board['boardSummary'],
            'sessionRows' => $board['sessionRows'],
            'approvalRows' => $board['approvalRows'],
        ]);
    }

    public function store(CreateCycleCountSessionRequest $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        try {
            $session = $this->cycleCountService->createSession($businessId, $request->validated(), $userId);

            return redirect()
                ->route('storage-manager.counts.show', $session->id)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.cycle_count_session_created_successfully'),
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

    public function show(int $session)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.count') && ! auth()->user()->can('storage_manager.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $context = $this->cycleCountService->getWorkbench($businessId, $session);

        return view('storagemanager::counts.show', $context);
    }

    public function submit(SubmitCycleCountRequest $request, int $session)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $sessionModel = StorageCountSession::query()
            ->where('business_id', $businessId)
            ->findOrFail($session);

        try {
            $sessionModel = $this->cycleCountService->submitCounts($businessId, $sessionModel, $request->validated(), $userId);

            return redirect()
                ->route('storage-manager.counts.show', $sessionModel->id)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.cycle_count_submitted_successfully'),
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

    public function approveShortages(ApproveCycleCountShortagesRequest $request, int $session)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $sessionModel = StorageCountSession::query()
            ->where('business_id', $businessId)
            ->findOrFail($session);

        try {
            $sessionModel = $this->cycleCountService->approveShortages($businessId, $sessionModel, $request->validated(), $userId);

            return redirect()
                ->route('storage-manager.counts.show', $sessionModel->id)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.cycle_count_shortages_approved_successfully'),
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
