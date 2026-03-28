<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasBudget;
use Modules\VasAccounting\Entities\VasBudgetLine;
use Modules\VasAccounting\Entities\VasCostCenter;
use Modules\VasAccounting\Entities\VasDepartment;
use Modules\VasAccounting\Entities\VasProject;
use Modules\VasAccounting\Http\Requests\StoreBudgetLineRequest;
use Modules\VasAccounting\Http\Requests\StoreBudgetRequest;
use Modules\VasAccounting\Services\BudgetControlService;
use Modules\VasAccounting\Utils\EnterprisePlanningReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class BudgetController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected EnterprisePlanningReportUtil $planningReportUtil,
        protected BudgetControlService $budgetControlService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.budgets.manage');

        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['budgets'] ?? true) === false) {
            abort(404);
        }

        return view('vasaccounting::budgets.index', [
            'summary' => $this->planningReportUtil->budgetSummary($businessId),
            'budgetRows' => $this->planningReportUtil->budgetRows($businessId),
            'varianceRows' => $this->planningReportUtil->budgetVarianceRows($businessId),
            'budgetOptions' => Schema::hasTable('vas_budgets')
                ? VasBudget::query()->where('business_id', $businessId)->orderBy('budget_code')->pluck('budget_code', 'id')
                : collect(),
            'chartOptions' => $this->vasUtil->chartOptions($businessId),
            'departmentOptions' => Schema::hasTable('vas_departments')
                ? VasDepartment::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'costCenterOptions' => Schema::hasTable('vas_cost_centers')
                ? VasCostCenter::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'projectOptions' => Schema::hasTable('vas_projects')
                ? VasProject::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')
                : collect(),
        ]);
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        VasBudget::create([
            'business_id' => $this->businessId($request),
            'budget_code' => strtoupper((string) $request->input('budget_code')),
            'name' => $request->input('name'),
            'department_id' => $request->input('department_id'),
            'cost_center_id' => $request->input('cost_center_id'),
            'project_id' => $request->input('project_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'status' => $request->input('status', 'draft'),
        ]);

        return redirect()
            ->route('vasaccounting.budgets.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.budget_saved')]);
    }

    public function storeLine(StoreBudgetLineRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $budget = VasBudget::query()->where('business_id', $businessId)->findOrFail((int) $request->input('budget_id'));

        VasBudgetLine::create([
            'business_id' => $businessId,
            'budget_id' => (int) $budget->id,
            'account_id' => $request->input('account_id'),
            'department_id' => $request->input('department_id') ?: $budget->department_id,
            'cost_center_id' => $request->input('cost_center_id') ?: $budget->cost_center_id,
            'project_id' => $request->input('project_id') ?: $budget->project_id,
            'budget_amount' => $request->input('budget_amount'),
            'committed_amount' => $request->input('committed_amount', 0),
            'actual_amount' => 0,
        ]);

        return redirect()
            ->route('vasaccounting.budgets.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.budget_line_saved')]);
    }

    public function syncActuals(Request $request, int $budget): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.budgets.manage');

        $budgetModel = VasBudget::query()
            ->where('business_id', $this->businessId($request))
            ->with('lines')
            ->findOrFail($budget);

        $result = $this->budgetControlService->syncBudgetActuals($budgetModel);

        return redirect()
            ->route('vasaccounting.budgets.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.budget_actuals_synced', ['count' => $result['updated_lines']])]);
    }
}
