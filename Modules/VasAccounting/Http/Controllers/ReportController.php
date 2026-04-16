<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Contracts\ProcurementDiscrepancyServiceInterface;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchException;
use Modules\VasAccounting\Entities\VasReportSnapshot;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Http\Requests\GenerateReportSnapshotRequest;
use Modules\VasAccounting\Http\Requests\ReportDatatableRequest;
use Modules\VasAccounting\Services\ComplianceProfileService;
use Modules\VasAccounting\Services\EnterpriseReportingService;
use Modules\VasAccounting\Services\ReportSnapshotService;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalEscalationDispatchService;

class ReportController extends VasBaseController
{
    public function __construct(
        protected EnterpriseReportingService $enterpriseReportingService,
        protected ReportSnapshotService $reportSnapshotService,
        protected ExpenseApprovalEscalationDispatchService $expenseApprovalEscalationDispatchService,
        protected ?ProcurementDiscrepancyServiceInterface $procurementDiscrepancyService = null,
        protected ?ComplianceProfileService $complianceProfileService = null
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.reports.view');

        $businessId = $this->businessId($request);

        return view('vasaccounting::reports.index', [
            'hubSummary' => $this->enterpriseReportingService->hubSummary($businessId),
            'reportDefinitions' => $this->enterpriseReportingService->reportDefinitions(),
            'recentSnapshots' => $this->reportSnapshotService->recentSnapshots($businessId, 15),
        ]);
    }

    public function trialBalance(Request $request)
    {
        return $this->renderReport($request, 'trial_balance');
    }

    public function generalLedger(Request $request)
    {
        return $this->renderReport($request, 'general_ledger');
    }

    public function vat(Request $request)
    {
        return $this->renderReport($request, 'vat');
    }

    public function cashBook(Request $request)
    {
        return $this->renderReport($request, 'cash_book');
    }

    public function bankBook(Request $request)
    {
        return $this->renderReport($request, 'bank_book');
    }

    public function bankReconciliation(Request $request)
    {
        return $this->renderReport($request, 'bank_reconciliation');
    }

    public function receivables(Request $request)
    {
        return $this->renderReport($request, 'receivables');
    }

    public function payables(Request $request)
    {
        return $this->renderReport($request, 'payables');
    }

    public function invoiceRegister(Request $request)
    {
        return $this->renderReport($request, 'invoice_register');
    }

    public function purchaseRegister(Request $request)
    {
        return $this->renderReport($request, 'purchase_register');
    }

    public function goodsReceiptRegister(Request $request)
    {
        return $this->renderReport($request, 'goods_receipt_register');
    }

    public function procurementDiscrepancies(Request $request)
    {
        return $this->renderReport($request, 'procurement_discrepancies');
    }

    public function procurementDiscrepancyOwnership(Request $request)
    {
        return $this->renderReport($request, 'procurement_discrepancy_ownership');
    }

    public function procurementAging(Request $request)
    {
        return $this->renderReport($request, 'procurement_aging');
    }

    public function expenseOutstanding(Request $request)
    {
        return $this->renderReport($request, 'expense_outstanding');
    }

    public function expenseRegister(Request $request)
    {
        return $this->renderReport($request, 'expense_register');
    }

    public function expenseEscalationAudit(Request $request)
    {
        return $this->renderReport($request, 'expense_escalation_audit');
    }

    public function retryFailedExpenseEscalationDispatches(Request $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.expenses.manage');

        $retried = $this->expenseApprovalEscalationDispatchService->retryFailedDispatchesForBusiness(
            $this->businessId($request),
            new ActionContext(
                (int) auth()->id(),
                $this->businessId($request),
                (string) ($request->input('reason') ?: 'Batch retry from expense escalation audit'),
                $request->input('request_id') ?: (string) Str::uuid(),
                $request->ip(),
                $request->userAgent(),
                array_merge((array) $request->input('meta', []), [
                    'source' => 'report_hub',
                    'report_key' => 'expense_escalation_audit',
                    'batch_retry' => true,
                ])
            )
        );

        return redirect()
            ->route('vasaccounting.reports.expense_escalation_audit')
            ->with('status', [
                'success' => true,
                'msg' => __('vasaccounting::lang.expense_escalation_dispatch_batch_requeued', ['count' => $retried]),
            ]);
    }

    public function assignUnassignedProcurementDiscrepanciesToMe(Request $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        $businessId = $this->businessId($request);
        $userId = (int) auth()->id();
        $context = new ActionContext(
            $userId,
            $businessId,
            (string) ($request->input('reason') ?: 'Batch owner assignment from procurement discrepancy ownership report'),
            $request->input('request_id') ?: (string) Str::uuid(),
            $request->ip(),
            $request->userAgent(),
            array_merge((array) $request->input('meta', []), [
                'source' => 'report_hub',
                'report_key' => 'procurement_discrepancy_ownership',
                'batch_assign_to_me' => true,
            ])
        );

        $assigned = 0;
        $exceptions = FinanceMatchException::query()
            ->where('business_id', $businessId)
            ->where('owner_id', 0)
            ->whereIn('status', FinanceMatchException::unresolvedStatuses())
            ->orderBy('id')
            ->get();

        foreach ($exceptions as $exception) {
            $this->procurementDiscrepancyService()->assignOwner($exception, $userId, $context);
            $assigned++;
        }

        return redirect()
            ->route('vasaccounting.reports.procurement_discrepancy_ownership')
            ->with('status', [
                'success' => true,
                'msg' => __('vasaccounting::lang.procurement_discrepancy_batch_assigned', ['count' => $assigned]),
            ]);
    }

    public function assignUnassignedProcurementDiscrepancies(Request $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.procurement.manage');

        $ownerId = (int) $request->input('owner_id');
        if ($ownerId <= 0) {
            return redirect()
                ->route('vasaccounting.reports.procurement_discrepancy_ownership')
                ->with('status', [
                    'success' => false,
                    'msg' => __('vasaccounting::lang.procurement_discrepancy_owner_required'),
                ]);
        }

        $businessId = $this->businessId($request);
        $context = new ActionContext(
            (int) auth()->id(),
            $businessId,
            (string) ($request->input('reason') ?: 'Batch owner reassignment from procurement discrepancy ownership report'),
            $request->input('request_id') ?: (string) Str::uuid(),
            $request->ip(),
            $request->userAgent(),
            array_merge((array) $request->input('meta', []), [
                'source' => 'report_hub',
                'report_key' => 'procurement_discrepancy_ownership',
                'batch_assign' => true,
                'assigned_owner_id' => $ownerId,
            ])
        );

        $assigned = 0;
        $exceptions = FinanceMatchException::query()
            ->where('business_id', $businessId)
            ->where('owner_id', 0)
            ->whereIn('status', FinanceMatchException::unresolvedStatuses())
            ->orderBy('id')
            ->get();

        foreach ($exceptions as $exception) {
            $this->procurementDiscrepancyService()->assignOwner($exception, $ownerId, $context);
            $assigned++;
        }

        return redirect()
            ->route('vasaccounting.reports.procurement_discrepancy_ownership')
            ->with('status', [
                'success' => true,
                'msg' => __('vasaccounting::lang.procurement_discrepancy_batch_reassigned', ['count' => $assigned]),
            ]);
    }

    public function inventory(Request $request)
    {
        return $this->renderReport($request, 'inventory');
    }

    public function fixedAssets(Request $request)
    {
        return $this->renderReport($request, 'fixed_assets');
    }

    public function payrollBridge(Request $request)
    {
        return $this->renderReport($request, 'payroll_bridge');
    }

    public function contracts(Request $request)
    {
        return $this->renderReport($request, 'contracts');
    }

    public function loans(Request $request)
    {
        return $this->renderReport($request, 'loans');
    }

    public function costing(Request $request)
    {
        return $this->renderReport($request, 'costing');
    }

    public function budgetVariance(Request $request)
    {
        return $this->renderReport($request, 'budget_variance');
    }

    public function financialStatements(Request $request)
    {
        return $this->renderReport($request, 'financial_statements');
    }

    public function closePacket(Request $request)
    {
        return $this->renderReport($request, 'close_packet');
    }

    public function operationalHealth(Request $request)
    {
        return $this->renderReport($request, 'operational_health');
    }

    public function storeSnapshot(GenerateReportSnapshotRequest $request)
    {
        $this->authorizePermission('vas_accounting.reports.view');

        $this->reportSnapshotService->queueSnapshot(
            $this->businessId($request),
            (string) $request->input('report_key'),
            $request->only(['period_id', 'snapshot_name']),
            (int) auth()->id()
        );

        return redirect()
            ->route('vasaccounting.reports.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.report_snapshot_queued')]);
    }

    public function showSnapshot(Request $request, int $snapshot)
    {
        $this->authorizePermission('vas_accounting.reports.view');

        $snapshotModel = VasReportSnapshot::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($snapshot);

        return view('vasaccounting::reports.snapshot', [
            'snapshot' => $snapshotModel,
            'payload' => (array) $snapshotModel->payload,
        ]);
    }

    public function datatable(ReportDatatableRequest $request, string $reportKey): JsonResponse
    {
        $this->authorizePermission('vas_accounting.reports.view');
        if (! $this->enterpriseReportingService->supports($reportKey)) {
            abort(404);
        }

        $dataset = $this->enterpriseReportingService->buildDataset($reportKey, $this->businessId($request), $request->validated());
        $columns = collect((array) ($dataset['columns'] ?? []))->values();
        $allRows = collect((array) ($dataset['rows'] ?? []))
            ->map(fn ($row) => is_array($row) ? array_values($row) : [(string) $row])
            ->values();

        $recordsTotal = $allRows->count();
        $searchValue = trim((string) data_get($request->input('search', []), 'value', ''));
        $filteredRows = $this->filterRows($allRows, $searchValue);
        $recordsFiltered = $filteredRows->count();
        $sortedRows = $this->sortRows(
            $filteredRows,
            (int) data_get($request->input('order', []), '0.column', 0),
            (string) data_get($request->input('order', []), '0.dir', 'asc')
        );

        $start = max(0, (int) $request->input('start', 0));
        $length = max(1, min(500, (int) $request->input('length', 25)));
        $pageRows = $sortedRows->slice($start, $length)->values();

        $data = $pageRows->map(function (array $row) use ($columns) {
            $mapped = [];
            foreach ($columns as $index => $columnName) {
                $mapped[] = $row[$index] ?? '';
            }

            return $mapped;
        })->all();

        $responsePayload = [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
        if ($reportKey === 'financial_statements') {
            $responsePayload['standard_profile'] = (string) ($dataset['standard_profile'] ?: $this->complianceProfileService()
                ->activeProfileForBusiness($this->businessId($request))['key']);
        }

        return response()->json($responsePayload);
    }

    protected function renderReport(Request $request, string $reportKey)
    {
        $this->authorizePermission('vas_accounting.reports.view');

        $filters = $request->all();
        if ($reportKey === 'financial_statements') {
            $filters = array_replace($filters, Validator::make(
                $request->all(),
                (new ReportDatatableRequest())->financialStatementRules()
            )->validate());
        }

        $dataset = $this->enterpriseReportingService->buildDataset($reportKey, $this->businessId($request), $filters);
        $dataset['reportKey'] = $reportKey;
        $dataset['reportManagement'] = $this->reportManagement($reportKey, $this->businessId($request));
        if ($reportKey === 'financial_statements') {
            $dataset['standard_profile'] = $dataset['standard_profile'] ?: $this->complianceProfileService()
                ->activeProfileForBusiness($this->businessId($request))['key'];
            $dataset['periodOptions'] = VasAccountingPeriod::query()
                ->where('business_id', $this->businessId($request))
                ->orderByDesc('end_date')
                ->get(['id', 'name', 'start_date', 'end_date']);

            return view('vasaccounting::reports.financial_statements', $dataset);
        }

        return view('vasaccounting::reports.table', $dataset);
    }

    protected function complianceProfileService(): ComplianceProfileService
    {
        return $this->complianceProfileService ?: app(ComplianceProfileService::class);
    }

    protected function procurementDiscrepancyService(): ProcurementDiscrepancyServiceInterface
    {
        return $this->procurementDiscrepancyService ?: app(ProcurementDiscrepancyServiceInterface::class);
    }

    protected function reportManagement(string $reportKey, int $businessId): array
    {
        if ($reportKey !== 'procurement_discrepancy_ownership') {
            return [];
        }

        return [
            'title' => __('vasaccounting::lang.views.report_table.procurement_ownership.title'),
            'subtitle' => __('vasaccounting::lang.views.report_table.procurement_ownership.subtitle'),
            'owner_label' => __('vasaccounting::lang.views.report_table.procurement_ownership.owner_label'),
            'owner_placeholder' => __('vasaccounting::lang.views.report_table.procurement_ownership.owner_placeholder'),
            'assign_label' => __('vasaccounting::lang.views.report_table.procurement_ownership.assign_label'),
            'route' => route('vasaccounting.reports.procurement_discrepancy_ownership.assign_unassigned'),
            'owner_options' => User::forDropdown($businessId, false, false, false, false),
        ];
    }

    protected function filterRows(Collection $rows, string $searchValue): Collection
    {
        if ($searchValue === '') {
            return $rows;
        }

        $needle = mb_strtolower($searchValue);

        return $rows->filter(function (array $row) use ($needle) {
            foreach ($row as $cell) {
                if (str_contains(mb_strtolower((string) $cell), $needle)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    protected function sortRows(Collection $rows, int $columnIndex, string $direction): Collection
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        if ($rows->isEmpty()) {
            return $rows;
        }

        $sorted = $rows->sort(function (array $left, array $right) use ($columnIndex, $direction) {
            $leftValue = (string) ($left[$columnIndex] ?? '');
            $rightValue = (string) ($right[$columnIndex] ?? '');

            $compare = strnatcasecmp($leftValue, $rightValue);
            return $direction === 'desc' ? ($compare * -1) : $compare;
        });

        return $sorted->values();
    }
}
