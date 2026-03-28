<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasWarehouse;
use Modules\VasAccounting\Http\Requests\StoreWarehouseRequest;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Utils\OperationsAssetReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class InventoryController extends VasBaseController
{
    public function __construct(
        protected VasInventoryValuationService $inventoryValuationService,
        protected VasAccountingUtil $vasUtil,
        protected OperationsAssetReportUtil $operationsAssetReportUtil
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.inventory.manage');

        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['inventory'] ?? true) === false) {
            abort(404);
        }

        $rows = $this->inventoryValuationService->summaries($businessId);
        $totals = $this->inventoryValuationService->totals($businessId);
        $warehouses = Schema::hasTable('vas_warehouses')
            ? VasWarehouse::query()->with('businessLocation')->where('business_id', $businessId)->orderBy('code')->get()
            : collect();

        return view('vasaccounting::inventory.index', [
            'rows' => $rows,
            'totals' => $totals,
            'warehouses' => $warehouses,
            'warehouseSummary' => $this->operationsAssetReportUtil->warehouseSummary($businessId),
            'movementRows' => $this->operationsAssetReportUtil->inventoryMovementRows($businessId),
            'reconciliationRows' => $this->operationsAssetReportUtil->warehouseReconciliationRows($businessId),
            'locationOptions' => BusinessLocation::forDropdown($businessId),
        ]);
    }

    public function storeWarehouse(StoreWarehouseRequest $request): RedirectResponse
    {
        VasWarehouse::create([
            'business_id' => $this->businessId($request),
            'code' => strtoupper((string) $request->input('code')),
            'name' => $request->input('name'),
            'business_location_id' => $request->input('business_location_id'),
            'status' => $request->input('status', 'active'),
        ]);

        return redirect()
            ->route('vasaccounting.inventory.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.warehouse_saved')]);
    }
}
