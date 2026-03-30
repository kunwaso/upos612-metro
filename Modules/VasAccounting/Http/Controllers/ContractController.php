<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasContract;
use Modules\VasAccounting\Entities\VasContractMilestone;
use Modules\VasAccounting\Entities\VasCostCenter;
use Modules\VasAccounting\Entities\VasProject;
use Modules\VasAccounting\Http\Requests\PostContractMilestoneRequest;
use Modules\VasAccounting\Http\Requests\StoreContractMilestoneRequest;
use Modules\VasAccounting\Http\Requests\StoreContractRequest;
use Modules\VasAccounting\Services\ContractAccountingService;
use Modules\VasAccounting\Utils\EnterprisePlanningReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class ContractController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected EnterprisePlanningReportUtil $planningReportUtil,
        protected ContractAccountingService $contractAccountingService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.contracts.manage');

        $businessId = $this->businessId($request);
        $selectedLocationId = $this->selectedLocationId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['contracts'] ?? true) === false) {
            abort(404);
        }

        $contractRows = $this->planningReportUtil->contractRows($businessId);
        $milestoneRows = $this->planningReportUtil->contractMilestoneRows($businessId);
        $summary = $this->planningReportUtil->contractSummary($businessId);

        if ($selectedLocationId) {
            $contractRows = collect($contractRows)
                ->filter(fn (array $row) => (int) data_get($row, 'contract.business_location_id') === $selectedLocationId)
                ->values();

            $milestoneRows = collect($milestoneRows)
                ->filter(fn ($milestone) => (int) data_get($milestone, 'contract.business_location_id') === $selectedLocationId)
                ->values();

            $summary = [
                'contract_count' => $contractRows->count(),
                'active_contracts' => $contractRows->filter(fn (array $row) => (string) data_get($row, 'contract.status') === 'active')->count(),
                'due_milestones' => $milestoneRows->filter(function ($milestone) {
                    if (! in_array((string) data_get($milestone, 'status'), ['planned', 'ready_to_bill'], true)) {
                        return false;
                    }

                    $date = data_get($milestone, 'milestone_date');
                    if (empty($date)) {
                        return false;
                    }

                    return now()->startOfDay()->gte(\Illuminate\Support\Carbon::parse((string) $date)->startOfDay());
                })->count(),
                'recognized_revenue' => round((float) $contractRows->sum(fn (array $row) => (float) ($row['recognized_total'] ?? 0)), 4),
            ];
        }

        return view('vasaccounting::contracts.index', [
            'summary' => $summary,
            'contractRows' => $contractRows,
            'milestoneRows' => $milestoneRows,
            'contactOptions' => $this->planningReportUtil->contactOptions($businessId),
            'projectOptions' => Schema::hasTable('vas_projects')
                ? VasProject::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'costCenterOptions' => Schema::hasTable('vas_cost_centers')
                ? VasCostCenter::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'selectedLocationId' => $selectedLocationId,
            'contractOptions' => Schema::hasTable('vas_contracts')
                ? VasContract::query()->where('business_id', $businessId)->orderBy('contract_no')->pluck('contract_no', 'id')
                : collect(),
        ]);
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        VasContract::create([
            'business_id' => $this->businessId($request),
            'contract_no' => strtoupper((string) $request->input('contract_no')),
            'name' => $request->input('name'),
            'contact_id' => $request->input('contact_id'),
            'project_id' => $request->input('project_id'),
            'cost_center_id' => $request->input('cost_center_id'),
            'business_location_id' => $request->input('business_location_id'),
            'signed_at' => $request->input('signed_at'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'contract_value' => $request->input('contract_value'),
            'advance_amount' => $request->input('advance_amount', 0),
            'retention_amount' => $request->input('retention_amount', 0),
            'status' => $request->input('status', 'draft'),
        ]);

        return redirect()
            ->route('vasaccounting.contracts.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.contract_saved')]);
    }

    public function storeMilestone(StoreContractMilestoneRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $contract = VasContract::query()->where('business_id', $businessId)->findOrFail((int) $request->input('contract_id'));

        VasContractMilestone::create([
            'business_id' => $businessId,
            'contract_id' => (int) $contract->id,
            'milestone_no' => strtoupper((string) $request->input('milestone_no')),
            'name' => $request->input('name'),
            'milestone_date' => $request->input('milestone_date'),
            'billing_date' => $request->input('billing_date'),
            'revenue_amount' => $request->input('revenue_amount'),
            'advance_amount' => $request->input('advance_amount', 0),
            'retention_amount' => $request->input('retention_amount', 0),
            'status' => $request->input('status', 'planned'),
        ]);

        return redirect()
            ->route('vasaccounting.contracts.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.contract_milestone_saved')]);
    }

    public function postMilestone(PostContractMilestoneRequest $request, int $milestone): RedirectResponse
    {
        $milestoneModel = VasContractMilestone::query()
            ->where('business_id', $this->businessId($request))
            ->with('contract')
            ->findOrFail($milestone);

        $this->contractAccountingService->postMilestone($milestoneModel, (int) auth()->id(), $request->input('posted_at'));

        return redirect()
            ->route('vasaccounting.contracts.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.contract_milestone_posted')]);
    }
}
