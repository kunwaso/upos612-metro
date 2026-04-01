<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasIntegrationRun;
use Modules\VasAccounting\Entities\VasIntegrationWebhook;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Entities\VasReportSnapshot;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;
use Modules\VasAccounting\Utils\EnterprisePlanningReportUtil;
use Modules\VasAccounting\Utils\OperationsAssetReportUtil;

class EnterpriseReportingService
{
    public function __construct(
        protected VasInventoryValuationService $inventoryValuationService,
        protected EnterpriseFinanceReportUtil $enterpriseReportUtil,
        protected OperationsAssetReportUtil $operationsAssetReportUtil,
        protected EnterprisePlanningReportUtil $planningReportUtil,
        protected VasPeriodCloseService $periodCloseService
    ) {
    }

    public function reportDefinitions(): array
    {
        return [
            'trial_balance' => ['title' => 'Trial Balance', 'route' => 'vasaccounting.reports.trial_balance', 'description' => 'Account balances by period.', 'group' => 'Statutory'],
            'general_ledger' => ['title' => 'General Ledger', 'route' => 'vasaccounting.reports.general_ledger', 'description' => 'Detailed journal entries by account.', 'group' => 'Statutory'],
            'vat' => ['title' => 'VAT Books', 'route' => 'vasaccounting.reports.vat', 'description' => 'Input and output VAT summaries.', 'group' => 'Statutory'],
            'financial_statements' => ['title' => 'Financial Statements', 'route' => 'vasaccounting.reports.financial_statements', 'description' => 'Summarized balances by statement type.', 'group' => 'Statutory'],
            'cash_book' => ['title' => 'Cash Book', 'route' => 'vasaccounting.reports.cash_book', 'description' => 'Cash receipts and payments from posted vouchers.', 'group' => 'Treasury'],
            'bank_book' => ['title' => 'Bank Book', 'route' => 'vasaccounting.reports.bank_book', 'description' => 'Bank movements and settlements.', 'group' => 'Treasury'],
            'bank_reconciliation' => ['title' => 'Bank Reconciliation', 'route' => 'vasaccounting.reports.bank_reconciliation', 'description' => 'Statement matching and unreconciled items.', 'group' => 'Treasury'],
            'receivables' => ['title' => 'Receivables Aging', 'route' => 'vasaccounting.reports.receivables', 'description' => 'Outstanding customer invoices and aging.', 'group' => 'Subledger'],
            'payables' => ['title' => 'Payables Aging', 'route' => 'vasaccounting.reports.payables', 'description' => 'Outstanding vendor bills and aging.', 'group' => 'Subledger'],
            'invoice_register' => ['title' => 'Invoice Register', 'route' => 'vasaccounting.reports.invoice_register', 'description' => 'Sales and purchase invoice register with e-invoice status.', 'group' => 'Subledger'],
            'inventory' => ['title' => 'Inventory Valuation', 'route' => 'vasaccounting.reports.inventory', 'description' => 'Weighted-average inventory reporting.', 'group' => 'Operations'],
            'fixed_assets' => ['title' => 'Fixed Asset Register', 'route' => 'vasaccounting.reports.fixed_assets', 'description' => 'Capitalized assets and depreciation.', 'group' => 'Operations'],
            'payroll_bridge' => ['title' => 'Payroll Bridge', 'route' => 'vasaccounting.reports.payroll_bridge', 'description' => 'Essentials payroll groups and bridge status.', 'group' => 'Planning'],
            'contracts' => ['title' => 'Contracts', 'route' => 'vasaccounting.reports.contracts', 'description' => 'Contract register and recognized milestone value.', 'group' => 'Planning'],
            'loans' => ['title' => 'Loans', 'route' => 'vasaccounting.reports.loans', 'description' => 'Loan principal, repayments, and outstanding balances.', 'group' => 'Planning'],
            'costing' => ['title' => 'Project Costing', 'route' => 'vasaccounting.reports.costing', 'description' => 'Project-level actual activity against budget allocations.', 'group' => 'Planning'],
            'budget_variance' => ['title' => 'Budget Variance', 'route' => 'vasaccounting.reports.budget_variance', 'description' => 'Budget, committed, actual, and remaining balances by line.', 'group' => 'Planning'],
            'close_packet' => ['title' => 'Close Packet', 'route' => 'vasaccounting.reports.close_packet', 'description' => 'Period-close checklist and blocker pack.', 'group' => 'Operations'],
            'operational_health' => ['title' => 'Operational Health', 'route' => 'vasaccounting.reports.operational_health', 'description' => 'Posting failures, integrations, snapshots, and webhook backlog.', 'group' => 'Operations'],
        ];
    }

    public function supports(string $reportKey): bool
    {
        return array_key_exists($reportKey, $this->reportDefinitions());
    }

    public function definition(string $reportKey): array
    {
        return $this->reportDefinitions()[$reportKey] ?? [];
    }

    public function hubSummary(int $businessId): array
    {
        return [
            ['label' => 'Report types', 'value' => count($this->reportDefinitions())],
            ['label' => 'Ready snapshots', 'value' => VasReportSnapshot::query()->where('business_id', $businessId)->where('status', 'ready')->count()],
            ['label' => 'Queued integrations', 'value' => VasIntegrationRun::query()->where('business_id', $businessId)->whereIn('status', ['queued', 'processing'])->count()],
            ['label' => 'Open failures', 'value' => VasPostingFailure::query()->where('business_id', $businessId)->whereNull('resolved_at')->count()],
        ];
    }

    public function buildDataset(string $reportKey, int $businessId, array $filters = []): array
    {
        return match ($reportKey) {
            'trial_balance' => $this->trialBalance($businessId),
            'general_ledger' => $this->generalLedger($businessId),
            'vat' => $this->vatBooks($businessId),
            'cash_book' => $this->cashBook($businessId),
            'bank_book' => $this->bankBook($businessId),
            'bank_reconciliation' => $this->bankReconciliation($businessId),
            'receivables' => $this->receivables($businessId),
            'payables' => $this->payables($businessId),
            'invoice_register' => $this->invoiceRegister($businessId),
            'inventory' => $this->inventory($businessId),
            'fixed_assets' => $this->fixedAssets($businessId),
            'payroll_bridge' => $this->payrollBridge($businessId),
            'contracts' => $this->contracts($businessId),
            'loans' => $this->loans($businessId),
            'costing' => $this->costing($businessId),
            'budget_variance' => $this->budgetVariance($businessId),
            'financial_statements' => $this->financialStatements($businessId),
            'close_packet' => $this->closePacket($businessId, $filters),
            'operational_health' => $this->operationalHealth($businessId),
            default => throw new \InvalidArgumentException("Unsupported VAS report key [{$reportKey}]."),
        };
    }

    protected function trialBalance(int $businessId): array
    {
        $rows = DB::table('vas_ledger_balances as lb')
            ->join('vas_accounts as a', 'a.id', '=', 'lb.account_id')
            ->where('lb.business_id', $businessId)
            ->select('a.account_code', 'a.account_name', 'lb.opening_debit', 'lb.opening_credit', 'lb.period_debit', 'lb.period_credit', 'lb.closing_debit', 'lb.closing_credit')
            ->orderBy('a.account_code')
            ->get();

        return $this->dataset(
            'Trial Balance',
            ['Code', 'Account', 'Opening Debit', 'Opening Credit', 'Period Debit', 'Period Credit', 'Closing Debit', 'Closing Credit'],
            $rows->map(fn ($row) => [$row->account_code, $row->account_name, $this->money($row->opening_debit), $this->money($row->opening_credit), $this->money($row->period_debit), $this->money($row->period_credit), $this->money($row->closing_debit), $this->money($row->closing_credit)]),
            [['label' => 'Accounts', 'value' => $rows->count()]]
        );
    }

    protected function generalLedger(int $businessId): array
    {
        $rows = DB::table('vas_journal_entries as je')
            ->join('vas_accounts as a', 'a.id', '=', 'je.account_id')
            ->join('vas_vouchers as v', 'v.id', '=', 'je.voucher_id')
            ->where('je.business_id', $businessId)
            ->select('je.posting_date', 'v.voucher_no', 'a.account_code', 'a.account_name', 'je.description', 'je.debit', 'je.credit')
            ->orderByDesc('je.posting_date')
            ->orderByDesc('je.id')
            ->limit(500)
            ->get();

        return $this->dataset(
            'General Ledger',
            ['Posting Date', 'Voucher', 'Code', 'Account', 'Description', 'Debit', 'Credit'],
            $rows->map(fn ($row) => [$row->posting_date, $row->voucher_no, $row->account_code, $row->account_name, $row->description, $this->money($row->debit), $this->money($row->credit)]),
            [['label' => 'Entries shown', 'value' => $rows->count()]]
        );
    }

    protected function vatBooks(int $businessId): array
    {
        $salesVatBook = $this->enterpriseReportUtil->salesVatBook($businessId);
        $purchaseVatBook = $this->enterpriseReportUtil->purchaseVatBook($businessId);

        return $this->dataset(
            'VAT Books',
            ['Book', 'Voucher', 'Counterparty', 'Tax Code', 'Tax Amount', 'Gross Amount'],
            $salesVatBook->map(fn ($row) => ['Sales', $row->voucher_no, $row->contact_name, $row->tax_code, $this->money($row->tax_amount), $this->money($row->gross_amount)])
                ->concat($purchaseVatBook->map(fn ($row) => ['Purchase', $row->voucher_no, $row->contact_name, $row->tax_code, $this->money($row->tax_amount), $this->money($row->gross_amount)])),
            [
                ['label' => 'Sales VAT', 'value' => $this->money($salesVatBook->sum('tax_amount'))],
                ['label' => 'Purchase VAT', 'value' => $this->money($purchaseVatBook->sum('tax_amount'))],
            ]
        );
    }

    protected function cashBook(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->cashLedgerRows($businessId);

        return $this->dataset(
            'Cash Book',
            ['Posting Date', 'Voucher', 'Contact', 'Reference', 'Description', 'Debit', 'Credit'],
            $rows->map(fn ($row) => [$row->posting_date, $row->voucher_no, $row->contact_name, $row->reference, $row->description ?: $row->voucher_description, $this->money($row->debit), $this->money($row->credit)]),
            [['label' => 'Rows', 'value' => $rows->count()]]
        );
    }

    protected function bankBook(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->bankLedgerRows($businessId);

        return $this->dataset(
            'Bank Book',
            ['Posting Date', 'Voucher', 'Contact', 'Reference', 'Description', 'Debit', 'Credit'],
            $rows->map(fn ($row) => [$row->posting_date, $row->voucher_no, $row->contact_name, $row->reference, $row->description ?: $row->voucher_description, $this->money($row->debit), $this->money($row->credit)]),
            [['label' => 'Rows', 'value' => $rows->count()]]
        );
    }

    protected function bankReconciliation(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->reconciliationRows($businessId);

        return $this->dataset(
            'Bank Reconciliation',
            ['Transaction Date', 'Bank Account', 'Statement Ref', 'Description', 'Amount', 'Match Status', 'Exception', 'Matched Document'],
            $rows->map(fn ($row) => [$row->transaction_date, trim(($row->bank_account_code ?: '') . ' ' . ($row->bank_name ?: '')), $row->statement_reference, $row->description, $this->money($row->amount), ucfirst($row->match_status), $row->treasury_exception_status ? ucfirst($row->treasury_exception_status) . ($row->treasury_exception_code ? ' (' . $row->treasury_exception_code . ')' : '') : '-', $row->finance_document_no ?: ($row->voucher_no ?: 'Unmatched')]),
            [['label' => 'Open treasury exceptions', 'value' => $rows->whereIn('treasury_exception_status', ['open', 'suggested'])->count()]]
        );
    }

    protected function receivables(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->receivableOpenItems($businessId);

        return $this->dataset(
            'Receivables Aging',
            ['Invoice', 'Customer', 'Posting Date', 'Invoice Amount', 'Allocated', 'Outstanding', 'Age (Days)'],
            $rows->map(fn ($row) => [$row->voucher_no, $row->contact_name, $row->posting_date, $this->money($row->source_amount), $this->money($row->allocated_amount), $this->money($row->outstanding_amount), $row->age_days]),
            [['label' => 'Outstanding total', 'value' => $this->money($rows->sum('outstanding_amount'))]]
        );
    }

    protected function payables(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->payableOpenItems($businessId);

        return $this->dataset(
            'Payables Aging',
            ['Bill', 'Vendor', 'Posting Date', 'Bill Amount', 'Allocated', 'Outstanding', 'Age (Days)'],
            $rows->map(fn ($row) => [$row->voucher_no, $row->contact_name, $row->posting_date, $this->money($row->source_amount), $this->money($row->allocated_amount), $this->money($row->outstanding_amount), $row->age_days]),
            [['label' => 'Outstanding total', 'value' => $this->money($rows->sum('outstanding_amount'))]]
        );
    }

    protected function invoiceRegister(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->invoiceRegister($businessId);

        return $this->dataset(
            'Invoice Register',
            ['Voucher', 'Type', 'Counterparty', 'Posting Date', 'Amount', 'Status', 'E-Invoice'],
            $rows->map(fn ($row) => [$row->voucher_no, $row->voucher_type, $row->contact_name, $row->posting_date, $this->money($row->amount), ucfirst($row->status), $row->einvoice_document_no ?: '-']),
            [['label' => 'Documents', 'value' => $rows->count()]]
        );
    }

    protected function inventory(int $businessId): array
    {
        $rows = collect($this->inventoryValuationService->summaries($businessId));

        return $this->dataset(
            'Inventory Valuation',
            ['SKU', 'Product', 'Location', 'Qty Available', 'Average Cost', 'Inventory Value'],
            $rows->map(fn ($row) => [$row['sku'], $row['product_name'], $row['location_name'] ?: $row['location_id'], $row['qty_available'], $this->money($row['average_cost']), $this->money($row['inventory_value'])]),
            [['label' => 'Inventory value', 'value' => $this->money($rows->sum('inventory_value'))]]
        );
    }

    protected function fixedAssets(int $businessId): array
    {
        $rows = collect($this->operationsAssetReportUtil->fixedAssetRegisterRows($businessId));

        return $this->dataset(
            'Fixed Asset Register',
            ['Asset Code', 'Name', 'Location', 'Original Cost', 'Accumulated Depreciation', 'Net Book Value', 'Status'],
            $rows->map(fn ($row) => [$row['asset']->asset_code, $row['asset']->name, optional($row['asset']->businessLocation)->name ?: '-', $this->money($row['asset']->original_cost), $this->money($row['accumulated_depreciation']), $this->money($row['net_book_value']), $row['asset']->status]),
            [['label' => 'Net book value', 'value' => $this->money($rows->sum('net_book_value'))]]
        );
    }

    protected function payrollBridge(int $businessId): array
    {
        $rows = $this->planningReportUtil->payrollGroupRows($businessId, 200);

        return $this->dataset(
            'Payroll Bridge',
            ['Payroll Group', 'Month', 'Employees', 'Gross Total', 'Net Total', 'Paid Total', 'Batch Status', 'Accrual Voucher'],
            $rows->map(fn ($row) => [$row['group_name'], $row['payroll_month'] ?: '-', $row['employee_count'], $this->money($row['gross_total']), $this->money($row['net_total']), $this->money($row['paid_total']), ucfirst((string) ($row['batch_status'] ?: 'not bridged')), $row['accrual_voucher_id'] ?: '-']),
            [['label' => 'Payroll groups', 'value' => $rows->count()]]
        );
    }

    protected function contracts(int $businessId): array
    {
        $rows = $this->planningReportUtil->contractRows($businessId, 200);

        return $this->dataset(
            'Contract Register',
            ['Contract', 'Counterparty', 'Project', 'Contract Value', 'Recognized', 'Remaining', 'Status'],
            $rows->map(fn ($row) => [$row['contract']->contract_no, optional($row['contract']->contact)->name ?: '-', optional($row['contract']->project)->name ?: '-', $this->money($row['contract']->contract_value), $this->money($row['recognized_total']), $this->money($row['remaining_value']), ucfirst($row['contract']->status)]),
            [['label' => 'Recognized total', 'value' => $this->money($rows->sum('recognized_total'))]]
        );
    }

    protected function loans(int $businessId): array
    {
        $rows = $this->planningReportUtil->loanRows($businessId, 200);

        return $this->dataset(
            'Loan Register',
            ['Loan', 'Lender', 'Principal', 'Principal Paid', 'Outstanding Principal', 'Next Due Date', 'Status'],
            $rows->map(fn ($row) => [$row['loan']->loan_no, $row['loan']->lender_name, $this->money($row['loan']->principal_amount), $this->money($row['principal_paid']), $this->money($row['outstanding_principal']), $row['next_due_date'] ?: '-', ucfirst($row['loan']->status)]),
            [['label' => 'Outstanding principal', 'value' => $this->money($rows->sum('outstanding_principal'))]]
        );
    }

    protected function costing(int $businessId): array
    {
        $rows = $this->planningReportUtil->projectRows($businessId);

        return $this->dataset(
            'Project Costing',
            ['Project', 'Customer', 'Cost Center', 'Budget Amount', 'Actual Activity', 'Status'],
            $rows->map(fn ($row) => [$row['project']->project_code . ' - ' . $row['project']->name, optional($row['project']->contact)->name ?: '-', optional($row['project']->costCenter)->name ?: '-', $this->money($row['project']->budget_amount), $this->money($row['actual_total']), ucfirst($row['project']->status)]),
            [['label' => 'Projects', 'value' => $rows->count()]]
        );
    }

    protected function budgetVariance(int $businessId): array
    {
        $rows = collect($this->planningReportUtil->budgetVarianceRows($businessId));

        return $this->dataset(
            'Budget Variance',
            ['Budget', 'Account', 'Department', 'Cost Center', 'Project', 'Budget', 'Committed', 'Actual', 'Remaining', 'Status'],
            $rows->map(fn ($row) => [$row['budget_code'] . ' - ' . $row['budget_name'], trim(($row['account_code'] ?: '') . ' ' . ($row['account_name'] ?: '')), $row['department_name'] ?: '-', $row['cost_center_name'] ?: '-', $row['project_name'] ?: '-', $this->money($row['budget_amount']), $this->money($row['committed_amount']), $this->money($row['actual_amount']), $this->money($row['remaining_amount']), $row['is_over_budget'] ? 'Over budget' : 'Within budget']),
            [['label' => 'Over-budget lines', 'value' => $rows->where('is_over_budget', true)->count()]]
        );
    }

    protected function financialStatements(int $businessId): array
    {
        $rows = DB::table('vas_accounts as a')
            ->leftJoin('vas_ledger_balances as lb', 'lb.account_id', '=', 'a.id')
            ->where('a.business_id', $businessId)
            ->selectRaw('a.account_type, SUM(lb.closing_debit) as debit_total, SUM(lb.closing_credit) as credit_total')
            ->groupBy('a.account_type')
            ->get();

        return $this->dataset(
            'Financial Statement Summary',
            ['Account Type', 'Debit Total', 'Credit Total'],
            $rows->map(fn ($row) => [$row->account_type, $this->money($row->debit_total), $this->money($row->credit_total)]),
            [['label' => 'Statement groups', 'value' => $rows->count()]]
        );
    }

    protected function closePacket(int $businessId, array $filters): array
    {
        $period = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->when(! empty($filters['period_id']), fn ($query) => $query->where('id', (int) $filters['period_id']))
            ->orderByDesc('end_date')
            ->firstOrFail();

        $blockers = $this->periodCloseService->blockers($businessId, $period);
        $checklists = $this->periodCloseService->checklistForPeriod($businessId, $period);
        $treasuryInsights = $this->periodCloseService->treasuryCloseInsights($businessId, $period);
        $blockedCount = collect($blockers)->reduce(function ($carry, $value, $key) {
            if ($key === 'posting_map_incomplete') {
                return $carry + ($value ? 1 : 0);
            }

            return $carry + (((int) $value) > 0 ? 1 : 0);
        }, 0);

        return $this->dataset(
            'Close Packet - ' . $period->name,
            ['Checklist', 'Status', 'Notes'],
            $checklists->map(fn ($item) => [$item->title, ucfirst($item->status), $item->notes ?: '-']),
            [
                ['label' => 'Period', 'value' => $period->name],
                ['label' => 'Status', 'value' => ucfirst($period->status)],
                ['label' => 'Blocked items', 'value' => $blockedCount],
                ['label' => 'Pending treasury docs', 'value' => $blockers['pending_treasury_documents']],
                ['label' => 'Treasury exceptions', 'value' => $blockers['unreconciled_bank_lines']],
            ],
            [
                [
                    'title' => 'Pending Treasury Documents',
                    'subtitle' => 'Native treasury documents in the close period that still need posting workflow action.',
                    'columns' => ['Document', 'Type', 'Workflow', 'Accounting', 'Amount'],
                    'rows' => $treasuryInsights['pending_documents']->map(function ($document) {
                        return [
                            $document->document_no ?: ('#' . $document->id),
                            $document->document_type,
                            ucfirst((string) $document->workflow_status),
                            ucfirst((string) $document->accounting_status),
                            $this->money($document->gross_amount) . ' ' . $document->currency_code,
                        ];
                    })->values()->all(),
                    'empty' => 'No native treasury documents are blocking close for this period.',
                ],
                [
                    'title' => 'Treasury Reconciliation Exceptions',
                    'subtitle' => 'Open or suggested bank statement exceptions that still block treasury close readiness.',
                    'columns' => ['Transaction Date', 'Statement Line', 'Status', 'Recommended Document'],
                    'rows' => $treasuryInsights['exceptions']->map(function ($exception) {
                        return [
                            optional(optional($exception->statementLine)->transaction_date)->format('Y-m-d') ?: '-',
                            optional($exception->statementLine)->description ?: 'Statement line',
                            strtoupper((string) $exception->status),
                            $exception->recommendedDocument?->document_no ?: 'No recommendation yet',
                        ];
                    })->values()->all(),
                    'empty' => 'No treasury reconciliation exceptions are blocking close for this period.',
                ],
            ]
        );
    }

    protected function operationalHealth(int $businessId): array
    {
        $unresolvedFailures = VasPostingFailure::query()->where('business_id', $businessId)->whereNull('resolved_at')->count();
        $queuedRuns = VasIntegrationRun::query()->where('business_id', $businessId)->whereIn('status', ['queued', 'processing'])->count();
        $failedRuns = VasIntegrationRun::query()->where('business_id', $businessId)->where('status', 'failed')->count();
        $pendingWebhooks = VasIntegrationWebhook::query()->where(function ($query) use ($businessId) {
            $query->where('business_id', $businessId)->orWhereNull('business_id');
        })->whereIn('status', ['received', 'queued'])->count();
        $unmatchedStatements = (int) DB::table('vas_bank_statement_lines')->where('business_id', $businessId)->where('match_status', 'unmatched')->count();
        $queuedSnapshots = VasReportSnapshot::query()->where('business_id', $businessId)->whereIn('status', ['queued', 'processing'])->count();

        $rows = collect([
            ['Posting failures', $unresolvedFailures, $unresolvedFailures > 0 ? 'Action required' : 'Healthy'],
            ['Queued integrations', $queuedRuns, $queuedRuns > 0 ? 'In progress' : 'Clear'],
            ['Failed integrations', $failedRuns, $failedRuns > 0 ? 'Investigate' : 'Clear'],
            ['Pending webhooks', $pendingWebhooks, $pendingWebhooks > 0 ? 'Review' : 'Clear'],
            ['Unmatched bank lines', $unmatchedStatements, $unmatchedStatements > 0 ? 'Reconcile' : 'Clear'],
            ['Queued report snapshots', $queuedSnapshots, $queuedSnapshots > 0 ? 'Generating' : 'Ready'],
        ]);

        $healthyCount = $rows->filter(fn ($row) => in_array($row[2], ['Healthy', 'Clear', 'Ready'], true))->count();

        return $this->dataset(
            'Operational Health',
            ['Area', 'Count', 'Status'],
            $rows,
            [['label' => 'Healthy checks', 'value' => $healthyCount]]
        );
    }

    protected function dataset(string $title, array $columns, Collection $rows, array $summary = [], array $sections = []): array
    {
        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows->values()->all(),
            'summary' => $summary,
            'sections' => $sections,
        ];
    }

    protected function money($value): string
    {
        return number_format((float) $value, 2);
    }
}
