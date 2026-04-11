<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\StorageManager\Http\Requests\CompleteReplenishmentRequest;
use Modules\StorageManager\Services\ReplenishmentService;
use Modules\StorageManager\Utils\StorageManagerToolbarNavUtil;
use Modules\StorageManager\Utils\StorageManagerUtil;

class ReplenishmentController extends Controller
{
    public function __construct(
        protected ReplenishmentService $replenishmentService,
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
        $queue = $this->replenishmentService->queueForLocation($businessId, $locationId ?: null);

        return view('storagemanager::replenishment.index', [
            'locations' => $locations,
            'locationId' => $locationId,
            'queueSummary' => $queue['queueSummary'],
            'rows' => $queue['rows'],
            'storageToolbarTitle' => __('lang_v1.replenishment_queue'),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.replenishment_queue'), 'url' => null],
            ], $locationId > 0 ? $locationId : null),
            'storageToolbarLocationId' => $locationId > 0 ? $locationId : null,
        ]);
    }

    public function show(int $rule)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.operate')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $context = $this->replenishmentService->getWorkbench($businessId, $rule, (int) request()->session()->get('user.id'));
        $locId = (int) $context['rule']->location_id;

        return view('storagemanager::replenishment.show', array_merge($context, [
            'storageToolbarTitle' => __('lang_v1.replenishment_document'),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.replenishment_queue'), 'url' => route('storage-manager.replenishment.index')],
                ['label' => __('lang_v1.replenishment_document'), 'url' => null],
            ], $locId > 0 ? $locId : null),
            'storageToolbarLocationId' => $locId > 0 ? $locId : null,
        ]));
    }

    public function complete(CompleteReplenishmentRequest $request, int $rule)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        try {
            $this->replenishmentService->completeReplenishment(
                $businessId,
                $rule,
                $request->validated(),
                $userId
            );

            return redirect()
                ->route('storage-manager.replenishment.show', $rule)
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.replenishment_completed_successfully'),
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
