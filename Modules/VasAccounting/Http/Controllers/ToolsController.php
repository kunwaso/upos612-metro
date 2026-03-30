<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasCostCenter;
use Modules\VasAccounting\Entities\VasDepartment;
use Modules\VasAccounting\Entities\VasProject;
use Modules\VasAccounting\Entities\VasTool;
use Modules\VasAccounting\Http\Requests\StoreToolRequest;
use Modules\VasAccounting\Services\VasToolAmortizationService;
use Modules\VasAccounting\Utils\OperationsAssetReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class ToolsController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected OperationsAssetReportUtil $operationsAssetReportUtil,
        protected VasToolAmortizationService $toolAmortizationService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.tools.manage');

        $businessId = $this->businessId($request);
        $selectedLocationId = $this->selectedLocationId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['tools'] ?? true) === false) {
            abort(404);
        }

        $toolRows = $this->operationsAssetReportUtil->toolRegisterRows($businessId);
        $scheduleRows = $this->operationsAssetReportUtil->toolScheduleRows($businessId);
        $amortizationHistory = $this->operationsAssetReportUtil->toolAmortizationHistory($businessId);

        if ($selectedLocationId) {
            $toolRows = collect($toolRows)
                ->filter(fn (array $row) => (int) data_get($row, 'tool.business_location_id') === $selectedLocationId)
                ->values();
            $scheduleRows = collect($scheduleRows)
                ->filter(fn (array $row) => (int) data_get($row, 'tool.business_location_id') === $selectedLocationId)
                ->values();
            $amortizationHistory = collect($amortizationHistory)
                ->filter(fn ($history) => (int) data_get($history, 'tool.business_location_id') === $selectedLocationId)
                ->values();
        }

        $summary = [
            'tool_count' => collect($toolRows)->count(),
            'active_tools' => collect($toolRows)->filter(fn (array $row) => in_array((string) data_get($row, 'tool.status'), ['active', 'issued'], true))->count(),
            'remaining_value' => round((float) collect($toolRows)->sum(fn (array $row) => (float) data_get($row, 'tool.remaining_value', 0)), 4),
            'due_this_month' => collect($scheduleRows)->filter(fn (array $row) => in_array((string) ($row['due_status'] ?? ''), ['due_now', 'overdue'], true))->count(),
        ];

        return view('vasaccounting::tools.index', [
            'summary' => $summary,
            'toolRows' => $toolRows,
            'scheduleRows' => $scheduleRows,
            'amortizationHistory' => $amortizationHistory,
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'selectedLocationId' => $selectedLocationId,
            'chartOptions' => $this->vasUtil->chartOptions($businessId),
            'departmentOptions' => Schema::hasTable('vas_departments')
                ? VasDepartment::query()->where('business_id', $businessId)->where('is_active', true)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'costCenterOptions' => Schema::hasTable('vas_cost_centers')
                ? VasCostCenter::query()->where('business_id', $businessId)->where('is_active', true)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'projectOptions' => Schema::hasTable('vas_projects')
                ? VasProject::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')
                : collect(),
        ]);
    }

    public function store(StoreToolRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $originalCost = round((float) $request->input('original_cost'), 4);
        $remainingValue = $request->filled('remaining_value') && (float) $request->input('remaining_value') > 0
            ? round((float) $request->input('remaining_value'), 4)
            : $originalCost;

        VasTool::create([
            'business_id' => $businessId,
            'tool_code' => strtoupper((string) $request->input('tool_code')),
            'name' => $request->input('name'),
            'business_location_id' => $request->input('business_location_id'),
            'expense_account_id' => $request->input('expense_account_id'),
            'asset_account_id' => $request->input('asset_account_id'),
            'department_id' => $request->input('department_id'),
            'cost_center_id' => $request->input('cost_center_id'),
            'project_id' => $request->input('project_id'),
            'original_cost' => $originalCost,
            'remaining_value' => min($remainingValue, $originalCost),
            'amortization_months' => $request->input('amortization_months'),
            'status' => $request->input('status', 'active'),
            'start_amortization_at' => $request->input('start_amortization_at'),
        ]);

        return redirect()
            ->route('vasaccounting.tools.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.tool_saved')]);
    }

    public function runAmortization(Request $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.tools.manage');

        $result = $this->toolAmortizationService->run($this->businessId($request), null, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.tools.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.tool_amortization_completed', ['count' => $result['amortizations_created']])]);
    }
}
