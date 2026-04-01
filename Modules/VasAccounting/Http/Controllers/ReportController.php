<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\Request;
use Modules\VasAccounting\Entities\VasReportSnapshot;
use Modules\VasAccounting\Http\Requests\GenerateReportSnapshotRequest;
use Modules\VasAccounting\Services\EnterpriseReportingService;
use Modules\VasAccounting\Services\ReportSnapshotService;

class ReportController extends VasBaseController
{
    public function __construct(
        protected EnterpriseReportingService $enterpriseReportingService,
        protected ReportSnapshotService $reportSnapshotService
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

    public function expenseOutstanding(Request $request)
    {
        return $this->renderReport($request, 'expense_outstanding');
    }

    public function expenseRegister(Request $request)
    {
        return $this->renderReport($request, 'expense_register');
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

    protected function renderReport(Request $request, string $reportKey)
    {
        $this->authorizePermission('vas_accounting.reports.view');

        $dataset = $this->enterpriseReportingService->buildDataset($reportKey, $this->businessId($request), $request->all());

        return view('vasaccounting::reports.table', $dataset);
    }
}
