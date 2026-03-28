<?php

namespace Modules\VasAccounting\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Essentials\Entities\PayrollGroup;
use Modules\VasAccounting\Entities\VasBankAccount;
use Modules\VasAccounting\Entities\VasEInvoiceDocument;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Http\Requests\StoreIntegrationRunRequest;
use Modules\VasAccounting\Services\IntegrationHubService;
use Modules\VasAccounting\Services\ReportSnapshotService;

class IntegrationController extends VasBaseController
{
    public function __construct(
        protected IntegrationHubService $integrationHubService,
        protected ReportSnapshotService $reportSnapshotService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.integrations.manage');

        $businessId = $this->businessId($request);

        return view('vasaccounting::integrations.index', [
            'overview' => $this->integrationHubService->overview($businessId),
            'recentRuns' => $this->integrationHubService->recentRuns($businessId, 20),
            'recentWebhooks' => $this->integrationHubService->recentWebhooks($businessId, 20),
            'postingFailures' => VasPostingFailure::query()->where('business_id', $businessId)->whereNull('resolved_at')->latest()->take(20)->get(),
            'recentSnapshots' => $this->reportSnapshotService->recentSnapshots($businessId, 12),
            'bankAccounts' => VasBankAccount::query()->where('business_id', $businessId)->orderBy('account_code')->get(),
            'payrollGroups' => PayrollGroup::query()->where('business_id', $businessId)->orderBy('name')->get(),
            'einvoiceDocuments' => VasEInvoiceDocument::query()->where('business_id', $businessId)->latest()->take(30)->get(),
            'bankProviders' => array_keys((array) config('vasaccounting.bank_statement_import_adapters', [])),
            'taxProviders' => array_keys((array) config('vasaccounting.tax_export_adapters', [])),
            'payrollProviders' => array_keys((array) config('vasaccounting.payroll_bridge_adapters', [])),
            'einvoiceProviders' => array_keys((array) config('vasaccounting.einvoice_adapters', [])),
        ]);
    }

    public function storeRun(StoreIntegrationRunRequest $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.integrations.manage');

        $businessId = $this->businessId($request);
        $runType = (string) $request->input('run_type');
        $action = (string) $request->input('action');
        $payload = match ($runType) {
            'bank_statement_import' => [
                'bank_account_id' => $request->input('bank_account_id'),
                'reference_no' => $request->input('reference_no'),
                'lines' => $this->integrationHubService->parseStatementLinesText((string) $request->input('statement_lines'))->values()->all(),
            ],
            'tax_export' => [
                'business_id' => $businessId,
                'export_type' => (string) $request->input('export_type', 'vat_declaration'),
            ],
            'payroll_bridge' => [
                'payroll_group_id' => (int) $request->input('payroll_group_id'),
            ],
            'einvoice_sync' => [
                'einvoice_document_id' => (int) $request->input('einvoice_document_id'),
            ],
            default => [],
        };

        $this->integrationHubService->queueRun(
            $businessId,
            $runType,
            $request->filled('provider') ? (string) $request->input('provider') : null,
            $action,
            $payload,
            (int) auth()->id()
        );

        return redirect()
            ->route('vasaccounting.integrations.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.integration_run_queued')]);
    }

    public function retryFailure(Request $request, int $failure): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.integrations.manage');

        $businessId = $this->businessId($request);
        VasPostingFailure::query()
            ->where('business_id', $businessId)
            ->whereNull('resolved_at')
            ->findOrFail($failure);

        $this->integrationHubService->queueFailureReplay($businessId, $failure, (int) auth()->id());

        return redirect()
            ->route('vasaccounting.integrations.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.posting_failure_requeued')]);
    }
}
