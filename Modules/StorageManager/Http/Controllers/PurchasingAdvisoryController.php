<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\StorageManager\Http\Requests\StorePurchasingAdvisoryRequest;
use Modules\StorageManager\Services\PurchasingAdvisoryService;
use Modules\StorageManager\Utils\StorageManagerUtil;

class PurchasingAdvisoryController extends Controller
{
    public function __construct(
        protected PurchasingAdvisoryService $purchasingAdvisoryService,
        protected StorageManagerUtil $storageManagerUtil
    ) {
    }

    public function index()
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.manage') && ! auth()->user()->can('storage_manager.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = request()->session()->get('user.business_id');
        $locationId = (int) request('location_id', 0);
        $locations = $this->storageManagerUtil->getLocationsDropdown($businessId);
        $queue = $this->purchasingAdvisoryService->queueForLocation($businessId, $locationId ?: null);
        $purchaseRequisitionEnabled = ! empty(session()->get('business.common_settings', [])['enable_purchase_requisition']);

        return view('storagemanager::planning.index', [
            'locations' => $locations,
            'locationId' => $locationId,
            'summary' => $queue['summary'],
            'rows' => $queue['rows'],
            'purchaseRequisitionEnabled' => $purchaseRequisitionEnabled,
        ]);
    }

    public function store(StorePurchasingAdvisoryRequest $request, int $rule)
    {
        if (empty(session()->get('business.common_settings', [])['enable_purchase_requisition'])) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', [
                    'success' => false,
                    'msg' => __('lang_v1.purchase_requisition_feature_disabled'),
                ]);
        }

        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        try {
            $result = $this->purchasingAdvisoryService->createPurchaseRequisition(
                $businessId,
                $rule,
                $request->validated(),
                $userId
            );

            return redirect()
                ->route('storage-manager.planning.index', ['location_id' => (int) data_get($result, 'advisory.location_id', 0)])
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.purchase_requisition_created_from_advisory', [
                        'ref' => data_get($result, 'purchase_requisition.ref_no', '—'),
                    ]),
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
