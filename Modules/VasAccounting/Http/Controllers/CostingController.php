<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasCostCenter;
use Modules\VasAccounting\Entities\VasDepartment;
use Modules\VasAccounting\Entities\VasProject;
use Modules\VasAccounting\Http\Requests\StoreCostCenterRequest;
use Modules\VasAccounting\Http\Requests\StoreDepartmentRequest;
use Modules\VasAccounting\Http\Requests\StoreProjectRequest;
use Modules\VasAccounting\Utils\EnterprisePlanningReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class CostingController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected EnterprisePlanningReportUtil $planningReportUtil
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.costing.manage');

        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['costing'] ?? true) === false) {
            abort(404);
        }

        return view('vasaccounting::costing.index', [
            'summary' => $this->planningReportUtil->costingSummary($businessId),
            'departmentRows' => $this->planningReportUtil->departmentRows($businessId),
            'costCenterRows' => $this->planningReportUtil->costCenterRows($businessId),
            'projectRows' => $this->planningReportUtil->projectRows($businessId),
            'contactOptions' => $this->planningReportUtil->contactOptions($businessId),
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'departmentOptions' => Schema::hasTable('vas_departments')
                ? VasDepartment::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'costCenterOptions' => Schema::hasTable('vas_cost_centers')
                ? VasCostCenter::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')
                : collect(),
        ]);
    }

    public function storeDepartment(StoreDepartmentRequest $request): RedirectResponse
    {
        VasDepartment::create([
            'business_id' => $this->businessId($request),
            'code' => strtoupper((string) $request->input('code')),
            'name' => $request->input('name'),
            'business_location_id' => $request->input('business_location_id'),
            'is_active' => (bool) $request->input('is_active', true),
        ]);

        return redirect()
            ->route('vasaccounting.costing.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.department_saved')]);
    }

    public function storeCostCenter(StoreCostCenterRequest $request): RedirectResponse
    {
        VasCostCenter::create([
            'business_id' => $this->businessId($request),
            'code' => strtoupper((string) $request->input('code')),
            'name' => $request->input('name'),
            'department_id' => $request->input('department_id'),
            'is_active' => (bool) $request->input('is_active', true),
        ]);

        return redirect()
            ->route('vasaccounting.costing.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.cost_center_saved')]);
    }

    public function storeProject(StoreProjectRequest $request): RedirectResponse
    {
        VasProject::create([
            'business_id' => $this->businessId($request),
            'project_code' => strtoupper((string) $request->input('project_code')),
            'name' => $request->input('name'),
            'contact_id' => $request->input('contact_id'),
            'cost_center_id' => $request->input('cost_center_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'status' => $request->input('status', 'draft'),
            'budget_amount' => $request->input('budget_amount', 0),
        ]);

        return redirect()
            ->route('vasaccounting.costing.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.project_saved')]);
    }
}
