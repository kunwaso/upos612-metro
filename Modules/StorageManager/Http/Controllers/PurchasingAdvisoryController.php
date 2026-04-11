<?php

namespace Modules\StorageManager\Http\Controllers;

use App\BusinessLocation;
use App\Http\Controllers\Controller;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Http\Requests\StoreGroupedPurchasingAdvisoryRequest;
use Modules\StorageManager\Http\Requests\StorePurchasingAdvisoryRequest;
use Modules\StorageManager\Services\PurchasingAdvisoryService;
use Modules\StorageManager\Utils\StorageManagerToolbarNavUtil;
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
            'storageToolbarTitle' => __('lang_v1.purchasing_advisories'),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.purchasing_advisories'), 'url' => null],
            ], $locationId > 0 ? $locationId : null),
            'storageToolbarLocationId' => $locationId > 0 ? $locationId : null,
        ]);
    }

    public function show(int $document)
    {
        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.manage') && ! auth()->user()->can('storage_manager.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) request()->session()->get('user.business_id');

        $document = StorageDocument::query()
            ->with([
                'area',
                'links' => fn ($query) => $query->orderByDesc('id'),
                'syncLogs' => fn ($query) => $query->with('createdByUser')->latest('id'),
            ])
            ->where('business_id', $businessId)
            ->where('document_type', 'purchase_requisition_advisory')
            ->findOrFail($document);

        $purchaseRequisitionLink = $document->links
            ->where('linked_system', 'app')
            ->where('linked_type', 'purchase_requisition')
            ->sortByDesc('id')
            ->first();
        $locationName = (string) (BusinessLocation::query()
            ->where('business_id', $businessId)
            ->where('id', $document->location_id)
            ->value('name') ?: ('#' . $document->location_id));

        return view('storagemanager::planning.show', [
            'document' => $document,
            'purchaseRequisitionLink' => $purchaseRequisitionLink,
            'locationName' => $locationName,
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
                        'ref' => data_get($result, 'purchase_requisition.ref_no', '-'),
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

    public function storeGrouped(StoreGroupedPurchasingAdvisoryRequest $request, int $location)
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
            $result = $this->purchasingAdvisoryService->createGroupedPurchaseRequisitionForLocation(
                $businessId,
                $location,
                $request->validated(),
                $userId
            );

            return redirect()
                ->route('storage-manager.planning.index', ['location_id' => $location])
                ->with('status', [
                    'success' => true,
                    'msg' => __('lang_v1.grouped_purchase_requisition_created_from_advisories', [
                        'ref' => data_get($result, 'purchase_requisition.ref_no', '-'),
                        'count' => (int) data_get($result, 'advisory_count', 0),
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

