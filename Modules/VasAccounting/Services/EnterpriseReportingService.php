<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchException;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasIntegrationRun;
use Modules\VasAccounting\Entities\VasIntegrationWebhook;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Entities\VasReportSnapshot;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalMonitorService;
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
        protected VasPeriodCloseService $periodCloseService,
        protected ExpenseApprovalMonitorService $expenseApprovalMonitorService
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
            'purchase_register' => ['title' => 'Purchase Register', 'route' => 'vasaccounting.reports.purchase_register', 'description' => 'Canonical purchase requisitions, orders, and supplier invoices.', 'group' => 'Operations'],
            'goods_receipt_register' => ['title' => 'Goods Receipt Register', 'route' => 'vasaccounting.reports.goods_receipt_register', 'description' => 'Canonical goods receipts with linked PO and invoice visibility.', 'group' => 'Operations'],
            'procurement_discrepancies' => ['title' => 'Procurement Discrepancies', 'route' => 'vasaccounting.reports.procurement_discrepancies', 'description' => 'Latest supplier invoice matching exceptions and mismatch backlog.', 'group' => 'Operations'],
            'procurement_discrepancy_ownership' => ['title' => 'Procurement Discrepancy Ownership', 'route' => 'vasaccounting.reports.procurement_discrepancy_ownership', 'description' => 'Ownership, queue aging, and assignee backlog for procurement discrepancies.', 'group' => 'Operations'],
            'procurement_aging' => ['title' => 'Procurement Aging', 'route' => 'vasaccounting.reports.procurement_aging', 'description' => 'Open procurement workflow aging across requisitions, orders, and supplier invoices.', 'group' => 'Operations'],
            'expense_outstanding' => ['title' => 'Expense Outstanding', 'route' => 'vasaccounting.reports.expense_outstanding', 'description' => 'Open employee advances and unresolved expense claims.', 'group' => 'Subledger'],
            'expense_register' => ['title' => 'Expense Register', 'route' => 'vasaccounting.reports.expense_register', 'description' => 'Native expense claims, advances, settlements, and reimbursements.', 'group' => 'Operations'],
            'expense_escalation_audit' => ['title' => 'Expense Escalation Audit', 'route' => 'vasaccounting.reports.expense_escalation_audit', 'description' => 'Overdue, escalated, failed, and retried expense approval dispatch controls.', 'group' => 'Operations'],
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
            'purchase_register' => $this->purchaseRegister($businessId),
            'goods_receipt_register' => $this->goodsReceiptRegister($businessId),
            'procurement_discrepancies' => $this->procurementDiscrepancies($businessId),
            'procurement_discrepancy_ownership' => $this->procurementDiscrepancyOwnership($businessId),
            'procurement_aging' => $this->procurementAging($businessId),
            'expense_outstanding' => $this->expenseOutstanding($businessId),
            'expense_register' => $this->expenseRegister($businessId),
            'expense_escalation_audit' => $this->expenseEscalationAudit($businessId),
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

    protected function purchaseRegister(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->purchaseRegisterRows($businessId);
        $supplierLabels = $this->contactLabels($rows->pluck('counterparty_id')->filter()->all());

        return $this->dataset(
            'Purchase Register',
            ['Document', 'Type', 'Supplier', 'Parent', 'Document Date', 'Quantity', 'Gross Amount', 'Workflow', 'Match'],
            $rows->map(function ($document) use ($supplierLabels) {
                $parentDocument = optional($document->parentLinks->first())->parentDocument;

                return [
                    $document->document_no ?: ('#' . $document->id),
                    $document->document_type,
                    $supplierLabels[(int) $document->counterparty_id] ?? ('Supplier #' . (int) $document->counterparty_id),
                    $parentDocument?->document_no ?: '-',
                    optional($document->document_date)->format('Y-m-d') ?: '-',
                    $document->lines->sum(fn ($line) => (float) $line->quantity),
                    $this->money($document->gross_amount) . ' ' . $document->currency_code,
                    ucfirst((string) $document->workflow_status),
                    data_get($document->meta, 'matching.latest_status') ?: '-',
                ];
            }),
            [
                ['label' => 'Documents', 'value' => $rows->count()],
                ['label' => 'Supplier invoices', 'value' => $rows->where('document_type', 'supplier_invoice')->count()],
                ['label' => 'Posted docs', 'value' => $rows->where('workflow_status', 'posted')->count()],
                ['label' => 'Gross total', 'value' => $this->money($rows->sum('gross_amount'))],
            ],
            [
                [
                    'title' => 'Purchase Document Mix',
                    'subtitle' => 'See how requisitions, orders, and supplier invoices are moving through the canonical procurement workspace.',
                    'columns' => ['Type', 'Documents', 'Gross Total', 'Quantity'],
                    'rows' => $rows
                        ->groupBy('document_type')
                        ->map(function ($documents, $documentType) {
                            return [
                                $documentType,
                                $documents->count(),
                                $this->money($documents->sum('gross_amount')),
                                $documents->sum(fn ($document) => $document->lines->sum(fn ($line) => (float) $line->quantity)),
                            ];
                        })
                        ->values()
                        ->all(),
                    'empty' => 'No canonical procurement documents have been recorded yet.',
                ],
            ],
            [
                [
                    'label' => 'Open procurement workspace',
                    'url' => route('vasaccounting.procurement.index'),
                    'style' => 'light-primary',
                    'method' => 'GET',
                ],
                [
                    'label' => 'Review matching queue',
                    'url' => route('vasaccounting.procurement.index', ['focus' => 'pending_matching']),
                    'style' => 'light-warning',
                    'method' => 'GET',
                ],
            ]
        );
    }

    protected function goodsReceiptRegister(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->goodsReceiptRegisterRows($businessId);
        $supplierLabels = $this->contactLabels($rows->pluck('counterparty_id')->filter()->all());

        return $this->dataset(
            'Goods Receipt Register',
            ['Receipt', 'Supplier', 'Parent PO', 'Posting Date', 'Quantity', 'Gross Amount', 'Workflow', 'Child Invoices'],
            $rows->map(function ($document) use ($supplierLabels) {
                $parentDocument = optional($document->parentLinks->first())->parentDocument;
                $childInvoiceCount = $document->childLinks
                    ->pluck('childDocument')
                    ->filter(fn ($child) => $child && $child->document_type === 'supplier_invoice')
                    ->count();

                return [
                    $document->document_no ?: ('#' . $document->id),
                    $supplierLabels[(int) $document->counterparty_id] ?? ('Supplier #' . (int) $document->counterparty_id),
                    $parentDocument?->document_no ?: '-',
                    optional($document->posting_date)->format('Y-m-d') ?: optional($document->document_date)->format('Y-m-d') ?: '-',
                    $document->lines->sum(fn ($line) => (float) $line->quantity),
                    $this->money($document->gross_amount) . ' ' . $document->currency_code,
                    ucfirst((string) $document->workflow_status),
                    $childInvoiceCount,
                ];
            }),
            [
                ['label' => 'Receipts', 'value' => $rows->count()],
                ['label' => 'Posted receipts', 'value' => $rows->where('workflow_status', 'posted')->count()],
                ['label' => 'Total quantity', 'value' => $rows->sum(fn ($document) => $document->lines->sum(fn ($line) => (float) $line->quantity))],
                ['label' => 'Gross total', 'value' => $this->money($rows->sum('gross_amount'))],
            ],
            [],
            [
                [
                    'label' => 'Open procurement workspace',
                    'url' => route('vasaccounting.procurement.index'),
                    'style' => 'light-primary',
                    'method' => 'GET',
                ],
                [
                    'label' => 'Review receiving queue',
                    'url' => route('vasaccounting.procurement.index', ['focus' => 'receiving_queue']),
                    'style' => 'light-warning',
                    'method' => 'GET',
                ],
            ]
        );
    }

    protected function procurementDiscrepancies(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->procurementDiscrepancyRows($businessId);
        $supplierLabels = $this->contactLabels($rows->pluck('counterparty_id')->filter()->all());
        $ownerLabels = $this->userLabels($rows->pluck('owner_id')->filter()->all());

        return $this->dataset(
            'Procurement Discrepancies',
            ['Invoice', 'Supplier', 'Severity', 'Queue Status', 'Owner', 'Code', 'Message', 'Line', 'Match Summary', 'Amount'],
            $rows->map(function ($row) use ($supplierLabels, $ownerLabels) {
                return [
                    $row->document_no,
                    $supplierLabels[(int) $row->counterparty_id] ?? ('Supplier #' . (int) $row->counterparty_id),
                    strtoupper((string) $row->severity),
                    str($row->status)->replace('_', ' ')->title(),
                    $ownerLabels[(int) $row->owner_id] ?? ($row->owner_id > 0 ? ('User #' . (int) $row->owner_id) : 'Unassigned'),
                    $row->code,
                    $row->message,
                    $row->line_no > 0 ? ('Line #' . $row->line_no) : 'Header',
                    str($row->match_status)->replace('_', ' ')->title() . ' | B' . $row->blocking_exception_count . ' / W' . $row->warning_count,
                    $this->money($row->gross_amount) . ' ' . $row->currency_code,
                ];
            }),
            [
                ['label' => 'Open discrepancies', 'value' => $rows->count()],
                ['label' => 'Blocking', 'value' => $rows->where('severity', 'blocking')->count()],
                ['label' => 'Warnings', 'value' => $rows->where('severity', 'warning')->count()],
                ['label' => 'In review', 'value' => $rows->where('status', FinanceMatchException::STATUS_IN_REVIEW)->count()],
                ['label' => 'Affected invoices', 'value' => $rows->pluck('document_id')->unique()->count()],
            ],
            [
                [
                    'title' => 'Discrepancy Code Mix',
                    'subtitle' => 'See which procurement mismatch types are driving the current supplier-invoice exception queue.',
                    'columns' => ['Code', 'Severity', 'Rows'],
                    'rows' => $rows
                        ->groupBy(fn ($row) => $row->code . '|' . $row->severity)
                        ->map(function ($group, $key) {
                            [$code, $severity] = explode('|', (string) $key, 2);

                            return [$code, strtoupper($severity), $group->count()];
                        })
                        ->values()
                        ->all(),
                    'empty' => 'No open procurement discrepancies are currently queued.',
                ],
                [
                    'title' => 'Ownership Mix',
                    'subtitle' => 'See whether discrepancies are still unassigned or already owned by a procurement reviewer.',
                    'columns' => ['Queue Status', 'Owner', 'Rows'],
                    'rows' => $rows
                        ->groupBy(fn ($row) => $row->status . '|' . ($row->owner_id > 0 ? $row->owner_id : 0))
                        ->map(function ($group, $key) use ($ownerLabels) {
                            [$status, $ownerId] = explode('|', (string) $key, 2);

                            return [
                                str($status)->replace('_', ' ')->title(),
                                (int) $ownerId > 0 ? ($ownerLabels[(int) $ownerId] ?? ('User #' . (int) $ownerId)) : 'Unassigned',
                                $group->count(),
                            ];
                        })
                        ->values()
                        ->all(),
                    'empty' => 'No procurement ownership actions have been recorded yet.',
                ],
            ],
            [
                [
                    'label' => 'Open discrepancy queue',
                    'url' => route('vasaccounting.procurement.index', ['focus' => 'discrepancy_queue']),
                    'style' => 'light-danger',
                    'method' => 'GET',
                ],
                [
                    'label' => 'Open matching queue',
                    'url' => route('vasaccounting.procurement.index', ['focus' => 'pending_matching']),
                    'style' => 'light-warning',
                    'method' => 'GET',
                ],
            ]
        );
    }

    protected function procurementAging(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->purchaseRegisterRows($businessId)
            ->filter(fn ($document) => ! in_array((string) $document->workflow_status, ['closed', 'cancelled', 'posted'], true))
            ->values();
        $supplierLabels = $this->contactLabels($rows->pluck('counterparty_id')->filter()->all());

        $agingRows = $rows->map(function ($document) use ($supplierLabels) {
            $ageDate = $document->posting_date ?: $document->document_date;
            $ageDays = $ageDate ? $ageDate->diffInDays(now()) : 0;

            return (object) [
                'document_no' => $document->document_no ?: ('#' . $document->id),
                'document_type' => (string) $document->document_type,
                'supplier' => $supplierLabels[(int) $document->counterparty_id] ?? ('Supplier #' . (int) $document->counterparty_id),
                'workflow_status' => (string) $document->workflow_status,
                'age_days' => $ageDays,
                'age_bucket' => $this->agingBucketLabel($ageDays),
                'gross_amount' => (float) $document->gross_amount,
                'currency_code' => (string) $document->currency_code,
                'matching_status' => (string) (data_get($document->meta, 'matching.latest_status') ?: '-'),
            ];
        })->values();

        return $this->dataset(
            'Procurement Aging',
            ['Document', 'Type', 'Supplier', 'Workflow', 'Age (Days)', 'Aging Bucket', 'Match', 'Gross Amount'],
            $agingRows->map(fn ($row) => [
                $row->document_no,
                $row->document_type,
                $row->supplier,
                ucfirst($row->workflow_status),
                $row->age_days,
                $row->age_bucket,
                $row->matching_status !== '-' ? str($row->matching_status)->replace('_', ' ')->title() : '-',
                $this->money($row->gross_amount) . ' ' . $row->currency_code,
            ]),
            [
                ['label' => 'Open workflow docs', 'value' => $agingRows->count()],
                ['label' => 'Older than 7 days', 'value' => $agingRows->where('age_days', '>', 7)->count()],
                ['label' => 'Supplier invoices', 'value' => $agingRows->where('document_type', 'supplier_invoice')->count()],
                ['label' => 'Gross total', 'value' => $this->money($agingRows->sum('gross_amount'))],
            ],
            [
                [
                    'title' => 'Aging Bucket Mix',
                    'subtitle' => 'See how much of the procurement workflow is current versus drifting into stale operational backlog.',
                    'columns' => ['Aging Bucket', 'Documents'],
                    'rows' => $agingRows
                        ->groupBy('age_bucket')
                        ->map(fn ($group, $bucket) => [$bucket, $group->count()])
                        ->values()
                        ->all(),
                    'empty' => 'No open procurement workflow items are aging right now.',
                ],
            ],
            [
                [
                    'label' => 'Review procurement queue',
                    'url' => route('vasaccounting.procurement.index', ['focus' => 'pending_documents']),
                    'style' => 'light-primary',
                    'method' => 'GET',
                ],
                [
                    'label' => 'Review discrepancies',
                    'url' => route('vasaccounting.procurement.index', ['focus' => 'discrepancy_queue']),
                    'style' => 'light-warning',
                    'method' => 'GET',
                ],
            ]
        );
    }

    protected function procurementDiscrepancyOwnership(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->procurementDiscrepancyRows($businessId);
        $supplierLabels = $this->contactLabels($rows->pluck('counterparty_id')->filter()->all());
        $ownerLabels = $this->userLabels($rows->pluck('owner_id')->filter()->all());
        $ownershipActivity = $this->procurementOwnershipActivity($businessId);
        $activityUserLabels = $this->userLabels($ownershipActivity->pluck('actor_id')->filter()->all());

        return $this->dataset(
            'Procurement Discrepancy Ownership',
            ['Invoice', 'Supplier', 'Queue Status', 'Owner', 'Owner Age', 'Severity', 'Code', 'Match Summary'],
            $rows->map(function ($row) use ($supplierLabels, $ownerLabels) {
                $ownerLabel = $ownerLabels[(int) $row->owner_id] ?? ($row->owner_id > 0 ? ('User #' . (int) $row->owner_id) : 'Unassigned');
                $ownerAge = is_null($row->owner_age_days)
                    ? 'Unassigned'
                    : ($row->owner_age_days . ' day' . ($row->owner_age_days === 1 ? '' : 's'));

                return [
                    $row->document_no,
                    $supplierLabels[(int) $row->counterparty_id] ?? ('Supplier #' . (int) $row->counterparty_id),
                    str($row->status)->replace('_', ' ')->title(),
                    $ownerLabel,
                    $ownerAge,
                    strtoupper((string) $row->severity),
                    $row->code,
                    str($row->match_status)->replace('_', ' ')->title() . ' | B' . $row->blocking_exception_count . ' / W' . $row->warning_count,
                ];
            }),
            [
                ['label' => 'Unassigned', 'value' => $rows->where('owner_id', 0)->count()],
                ['label' => 'In review', 'value' => $rows->where('status', FinanceMatchException::STATUS_IN_REVIEW)->count()],
                ['label' => 'Aged > 2 days', 'value' => $rows->filter(fn ($row) => ! is_null($row->owner_age_days) && $row->owner_age_days > 2)->count()],
                ['label' => 'Aged > 7 days', 'value' => $rows->filter(fn ($row) => ! is_null($row->owner_age_days) && $row->owner_age_days > 7)->count()],
                ['label' => 'Older than 14 days', 'value' => $rows->filter(fn ($row) => ! is_null($row->owner_age_days) && $row->owner_age_days > 14)->count()],
                ['label' => 'Ownership actions (14 days)', 'value' => $ownershipActivity->count()],
            ],
            [
                [
                    'title' => 'Assignment Aging Trend',
                    'subtitle' => 'Weekly backlog aging trend based on current unresolved discrepancies and when ownership was last assigned.',
                    'columns' => ['Assignment Week', 'Open Rows', 'In Review', 'Aged > 7 Days', 'Aged > 14 Days'],
                    'rows' => collect(range(5, 0))
                        ->map(function ($weeksAgo) use ($rows) {
                            $weekStart = now()->subWeeks($weeksAgo)->startOfWeek();
                            $weekEnd = (clone $weekStart)->endOfWeek();
                            $weekRows = $rows->filter(function ($row) use ($weekStart, $weekEnd) {
                                if ((int) $row->owner_id === 0 || is_null($row->owner_age_days)) {
                                    return false;
                                }

                                $assignedAt = now()->subDays((int) $row->owner_age_days);

                                return $assignedAt->betweenIncluded($weekStart, $weekEnd);
                            });

                            return [
                                $weekStart->format('Y-m-d'),
                                $weekRows->count(),
                                $weekRows->where('status', FinanceMatchException::STATUS_IN_REVIEW)->count(),
                                $weekRows->filter(fn ($row) => ! is_null($row->owner_age_days) && $row->owner_age_days > 7)->count(),
                                $weekRows->filter(fn ($row) => ! is_null($row->owner_age_days) && $row->owner_age_days > 14)->count(),
                            ];
                        })
                        ->all(),
                    'empty' => 'No ownership assignments are available to trend across the last six weeks.',
                ],
                [
                    'title' => 'Owner Aging Mix',
                    'subtitle' => 'See where procurement mismatch follow-up is drifting by owner and queue age.',
                    'columns' => ['Owner', 'Aging Bucket', 'Rows'],
                    'rows' => $rows
                        ->groupBy(function ($row) {
                            $ownerId = $row->owner_id > 0 ? $row->owner_id : 0;
                            $bucket = match (true) {
                                is_null($row->owner_age_days) => 'Unassigned',
                                $row->owner_age_days <= 1 => '0-1 days',
                                $row->owner_age_days <= 3 => '2-3 days',
                                $row->owner_age_days <= 7 => '4-7 days',
                                default => '8+ days',
                            };

                            return $ownerId . '|' . $bucket;
                        })
                        ->map(function ($group, $key) use ($ownerLabels) {
                            [$ownerId, $bucket] = explode('|', (string) $key, 2);

                            return [
                                (int) $ownerId > 0 ? ($ownerLabels[(int) $ownerId] ?? ('User #' . (int) $ownerId)) : 'Unassigned',
                                $bucket,
                                $group->count(),
                            ];
                        })
                        ->values()
                        ->all(),
                    'empty' => 'No procurement discrepancy ownership backlog is currently open.',
                ],
                [
                    'title' => 'Stale Owner Backlog',
                    'subtitle' => 'Owners currently holding discrepancies older than two days, ranked by stale queue size.',
                    'columns' => ['Owner', 'Open Discrepancies', 'Aged > 2 Days', 'Aged > 7 Days'],
                    'rows' => $rows
                        ->groupBy(fn ($row) => (int) ($row->owner_id ?: 0))
                        ->map(function ($group, $ownerId) use ($ownerLabels) {
                            if ((int) $ownerId === 0) {
                                return null;
                            }

                            $agedOver2 = $group->filter(fn ($row) => ! is_null($row->owner_age_days) && $row->owner_age_days > 2)->count();
                            $agedOver7 = $group->filter(fn ($row) => ! is_null($row->owner_age_days) && $row->owner_age_days > 7)->count();

                            if ($agedOver2 === 0 && $agedOver7 === 0) {
                                return null;
                            }

                            return [
                                $ownerLabels[(int) $ownerId] ?? ('User #' . (int) $ownerId),
                                $group->count(),
                                $agedOver2,
                                $agedOver7,
                            ];
                        })
                        ->filter()
                        ->sortByDesc(fn ($row) => [$row[3], $row[2], $row[1]])
                        ->values()
                        ->all(),
                    'empty' => 'No owner currently has procurement discrepancies older than two days.',
                ],
                [
                    'title' => 'Unassigned Discrepancies',
                    'subtitle' => 'Exceptions still waiting for an explicit owner assignment.',
                    'columns' => ['Invoice', 'Supplier', 'Severity', 'Code', 'Match Summary'],
                    'rows' => $rows
                        ->filter(fn ($row) => (int) $row->owner_id === 0)
                        ->map(function ($row) use ($supplierLabels) {
                            return [
                                $row->document_no,
                                $supplierLabels[(int) $row->counterparty_id] ?? ('Supplier #' . (int) $row->counterparty_id),
                                strtoupper((string) $row->severity),
                                str($row->code)->replace('_', ' ')->title()->value(),
                                str($row->match_status)->replace('_', ' ')->title() . ' | B' . $row->blocking_exception_count . ' / W' . $row->warning_count,
                            ];
                        })
                        ->values()
                        ->all(),
                    'empty' => 'All open procurement discrepancies currently have an assigned owner.',
                ],
                [
                    'title' => 'Ownership Activity Trend',
                    'subtitle' => 'Daily ownership, reassignment, and resolution flow over the last fourteen days from the canonical audit stream.',
                    'columns' => ['Date', 'Claimed', 'Reassigned', 'Resolved'],
                    'rows' => collect(range(13, 0))
                        ->map(function ($daysAgo) use ($ownershipActivity) {
                            $date = now()->subDays($daysAgo)->toDateString();
                            $events = $ownershipActivity->where('activity_date', $date);

                            return [
                                $date,
                                $events->where('event_type', 'procurement.discrepancy_owned')->count(),
                                $events->where('event_type', 'procurement.discrepancy_reassigned')->count(),
                                $events->where('event_type', 'procurement.discrepancy_resolved')->count(),
                            ];
                        })
                        ->all(),
                    'empty' => 'No procurement ownership activity has been recorded in the last fourteen days.',
                ],
                [
                    'title' => 'Reviewer Throughput',
                    'subtitle' => 'See which reviewers are actively taking ownership, reassigning, or resolving procurement discrepancies.',
                    'columns' => ['Reviewer', 'Claimed', 'Reassigned', 'Resolved', 'Total Actions'],
                    'rows' => $ownershipActivity
                        ->groupBy(fn ($row) => (int) $row->actor_id)
                        ->map(function ($events, $actorId) use ($activityUserLabels) {
                            return [
                                $activityUserLabels[(int) $actorId] ?? ('User #' . (int) $actorId),
                                $events->where('event_type', 'procurement.discrepancy_owned')->count(),
                                $events->where('event_type', 'procurement.discrepancy_reassigned')->count(),
                                $events->where('event_type', 'procurement.discrepancy_resolved')->count(),
                                $events->count(),
                            ];
                        })
                        ->sortByDesc(fn ($row) => [$row[4], $row[3], $row[2], $row[1]])
                        ->values()
                        ->all(),
                    'empty' => 'No reviewer throughput has been recorded in the last fourteen days.',
                ],
            ],
            [
                [
                    'label' => 'Open discrepancy queue',
                    'url' => route('vasaccounting.procurement.index', ['focus' => 'discrepancy_queue']),
                    'style' => 'light-danger',
                    'method' => 'GET',
                ],
                [
                    'label' => 'Assign unassigned to me',
                    'url' => route('vasaccounting.reports.procurement_discrepancy_ownership.assign_unassigned_to_me'),
                    'style' => 'light-primary',
                    'method' => 'POST',
                    'confirm' => 'Assign every unassigned procurement discrepancy in this report to you?',
                ],
                [
                    'label' => 'Open procurement aging',
                    'url' => route('vasaccounting.reports.procurement_aging'),
                    'style' => 'light-warning',
                    'method' => 'GET',
                ],
            ]
        );
    }

    protected function procurementOwnershipActivity(int $businessId): Collection
    {
        return $this->procurementOwnershipActivityForRange(
            $businessId,
            now()->subDays(14)->toDateString(),
            now()->toDateString()
        );
    }

    protected function procurementOwnershipActivityForRange(int $businessId, ?string $startDate, ?string $endDate): Collection
    {
        if (! Schema::hasTable('vas_fin_audit_events')) {
            return collect();
        }

        return FinanceAuditEvent::query()
            ->with(['document:id,document_no'])
            ->where('business_id', $businessId)
            ->whereIn('event_type', [
                'procurement.discrepancy_owned',
                'procurement.discrepancy_reassigned',
                'procurement.discrepancy_resolved',
            ])
            ->when($startDate, fn ($query) => $query->whereDate('acted_at', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('acted_at', '<=', $endDate))
            ->orderBy('acted_at')
            ->get([
                'id',
                'document_id',
                'actor_id',
                'event_type',
                'reason',
                'before_state',
                'after_state',
                'acted_at',
            ])
            ->map(function (FinanceAuditEvent $event) {
                return (object) [
                    'document_id' => (int) $event->document_id,
                    'document_no' => $event->document?->document_no,
                    'actor_id' => (int) $event->actor_id,
                    'event_type' => (string) $event->event_type,
                    'reason' => $event->reason,
                    'activity_date' => optional($event->acted_at)->toDateString(),
                    'acted_at_label' => optional($event->acted_at)->format('Y-m-d H:i'),
                    'before_owner_id' => (int) data_get($event->before_state, 'owner_id', 0),
                    'after_owner_id' => (int) data_get($event->after_state, 'owner_id', 0),
                ];
            });
    }

    protected function expenseOutstanding(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->expenseOutstandingRows($businessId);

        return $this->dataset(
            'Expense Outstanding',
            ['Document', 'Type', 'Claimant', 'Posting Date', 'Outstanding', 'Settlement Status', 'Linked Context'],
            $rows->map(function ($document) {
                $expense = (array) data_get($document->meta, 'expense', []);
                $expenseChain = (array) data_get($document->meta, 'expense_chain', []);

                return [
                    $document->document_no ?: ('#' . $document->id),
                    $document->document_type,
                    $expense['claimant_name'] ?? 'Unassigned',
                    optional($document->posting_date)->format('Y-m-d') ?: optional($document->document_date)->format('Y-m-d') ?: '-',
                    $this->money($document->open_amount) . ' ' . $document->currency_code,
                    strtoupper((string) ($expenseChain['settlement_status'] ?? 'open')),
                    $expenseChain['linked_advance_document_no']
                        ?? (($expenseChain['linked_claim_count'] ?? null) !== null
                            ? 'Linked claims: ' . $expenseChain['linked_claim_count']
                            : '-'),
                ];
            }),
            [
                ['label' => 'Outstanding total', 'value' => $this->money($rows->sum('open_amount'))],
                ['label' => 'Open advances', 'value' => $rows->where('document_type', 'advance_request')->count()],
                ['label' => 'Open claims', 'value' => $rows->where('document_type', 'expense_claim')->count()],
            ]
        );
    }

    protected function expenseRegister(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->expenseRegisterRows($businessId);

        return $this->dataset(
            'Expense Register',
            ['Document', 'Type', 'Claimant', 'Document Date', 'Posting Date', 'Gross Amount', 'Open Amount', 'Workflow', 'Accounting'],
            $rows->map(function ($document) {
                $expense = (array) data_get($document->meta, 'expense', []);

                return [
                    $document->document_no ?: ('#' . $document->id),
                    $document->document_type,
                    $expense['claimant_name'] ?? 'Unassigned',
                    optional($document->document_date)->format('Y-m-d') ?: '-',
                    optional($document->posting_date)->format('Y-m-d') ?: '-',
                    $this->money($document->gross_amount) . ' ' . $document->currency_code,
                    $this->money($document->open_amount) . ' ' . $document->currency_code,
                    ucfirst((string) $document->workflow_status),
                    ucfirst((string) $document->accounting_status),
                ];
            }),
            [
                ['label' => 'Documents', 'value' => $rows->count()],
                ['label' => 'Posted docs', 'value' => $rows->where('workflow_status', 'posted')->count()],
                ['label' => 'Gross total', 'value' => $this->money($rows->sum('gross_amount'))],
            ],
            [
                [
                    'title' => 'Expense Document Mix',
                    'subtitle' => 'Track how claims, advances, settlements, and reimbursements are flowing through the native expense workspace.',
                    'columns' => ['Type', 'Documents', 'Gross Total', 'Open Total'],
                    'rows' => $rows
                        ->groupBy('document_type')
                        ->map(function ($documents, $documentType) {
                            return [
                                $documentType,
                                $documents->count(),
                                $this->money($documents->sum('gross_amount')),
                                $this->money($documents->sum('open_amount')),
                            ];
                        })
                        ->values()
                        ->all(),
                    'empty' => 'No expense documents have been posted into the native register yet.',
                ],
            ]
        );
    }

    protected function expenseEscalationAudit(int $businessId): array
    {
        $rows = $this->enterpriseReportUtil->expenseEscalationAuditRows($businessId);
        $insights = $this->expenseApprovalMonitorService->buildInsights($rows);
        $filteredRows = $rows->filter(function ($document) use ($insights) {
            $insight = $insights[$document->id] ?? [];

            return ($insight['sla_state'] ?? null) === 'overdue'
                || (int) ($insight['escalation_count'] ?? 0) > 0
                || filled($insight['dispatch_status'] ?? null);
        })->values();

        return $this->dataset(
            'Expense Escalation Audit',
            ['Document', 'Type', 'Claimant', 'Current Approver', 'SLA', 'Escalations', 'Dispatch', 'Dispatch Error'],
            $filteredRows->map(function ($document) use ($insights) {
                $insight = $insights[$document->id] ?? [];
                $expense = (array) data_get($document->meta, 'expense', []);

                return [
                    $document->document_no ?: ('#' . $document->id),
                    $document->document_type,
                    $expense['claimant_name'] ?? 'Unassigned',
                    $insight['current_step_role_label'] ?? $insight['current_step_label'] ?? 'Pending review',
                    $insight['sla_label'] ?? 'No SLA',
                    (int) ($insight['escalation_count'] ?? 0),
                    $insight['dispatch_status_label'] ?? 'No dispatch',
                    $insight['dispatch_error'] ?? '-',
                ];
            }),
            [
                ['label' => 'Documents in audit', 'value' => $filteredRows->count()],
                ['label' => 'Overdue approvals', 'value' => collect($insights)->where('sla_state', 'overdue')->count()],
                ['label' => 'Failed dispatches', 'value' => collect($insights)->where('dispatch_status', 'failed')->count()],
                ['label' => 'Queued dispatches', 'value' => collect($insights)->where('dispatch_status', 'queued')->count()],
            ],
            [
                [
                    'title' => 'Dispatch Status Mix',
                    'subtitle' => 'Monitor whether escalation notifications are queued, delivered, or failing by active approval document.',
                    'columns' => ['Dispatch Status', 'Documents'],
                    'rows' => collect($insights)
                        ->groupBy(fn ($insight) => $insight['dispatch_status_label'] ?? 'No dispatch')
                        ->map(fn ($group, $label) => [$label, count($group)])
                        ->values()
                        ->all(),
                    'empty' => 'No escalation dispatch activity has been recorded yet.',
                ],
            ],
            [
                [
                    'label' => 'Open escalated approvals',
                    'url' => route('vasaccounting.expenses.index', ['focus' => 'escalated_approvals']),
                    'style' => 'light-primary',
                    'method' => 'GET',
                ],
                [
                    'label' => 'Retry failed dispatches',
                    'url' => route('vasaccounting.reports.expense_escalation_audit.retry_failed_dispatches'),
                    'style' => 'light-warning',
                    'method' => 'POST',
                    'confirm' => 'Retry all failed escalation dispatches from this audit report?',
                ],
            ]
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
        $procurementInsights = $this->periodCloseService->procurementCloseInsights($businessId, $period);
        $expenseInsights = $this->periodCloseService->expenseCloseInsights($businessId, $period);
        $procurementOwnershipActivity = $this->procurementOwnershipActivityForRange(
            $businessId,
            optional($period->start_date)->toDateString(),
            optional($period->end_date)->toDateString()
        );
        $procurementOwnershipLabels = $this->userLabels(
            $procurementOwnershipActivity
                ->pluck('actor_id')
                ->merge($procurementOwnershipActivity->pluck('before_owner_id'))
                ->merge($procurementOwnershipActivity->pluck('after_owner_id'))
                ->filter()
                ->unique()
                ->values()
                ->all()
        );
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
                ['label' => 'Pending procurement docs', 'value' => $blockers['pending_procurement_documents']],
                ['label' => 'Receiving backlog', 'value' => $blockers['receiving_procurement_documents']],
                ['label' => 'Matching backlog', 'value' => $blockers['matching_procurement_documents']],
                ['label' => 'Unassigned procurement discrepancies', 'value' => $procurementInsights['owner_summary']->where('owner_id', 0)->sum('open_count')],
                ['label' => 'Procurement discrepancies aged > 7 days', 'value' => $procurementInsights['owner_summary']->sum('aged_over_7_days')],
                ['label' => 'Procurement ownership actions', 'value' => $procurementOwnershipActivity->count()],
                ['label' => 'Procurement reassignments', 'value' => $procurementOwnershipActivity->where('event_type', 'procurement.discrepancy_reassigned')->count()],
                ['label' => 'Pending expense docs', 'value' => $blockers['pending_expense_documents']],
                ['label' => 'Outstanding expense balances', 'value' => $blockers['outstanding_expense_documents']],
                ['label' => 'Escalated expense approvals', 'value' => $blockers['escalated_expense_approvals']],
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
                [
                    'title' => 'Pending Procurement Documents',
                    'subtitle' => 'Procurement workflow items that still need draft, approval, or posting readiness work before close.',
                    'columns' => ['Document', 'Type', 'Workflow', 'Accounting', 'Amount'],
                    'rows' => $procurementInsights['pending_documents']->map(function ($document) {
                        return [
                            $document->document_no ?: ('#' . $document->id),
                            $document->document_type,
                            ucfirst((string) $document->workflow_status),
                            ucfirst((string) $document->accounting_status),
                            $this->money($document->gross_amount) . ' ' . $document->currency_code,
                        ];
                    })->values()->all(),
                    'empty' => 'No procurement workflow documents are blocking close for this period.',
                ],
                [
                    'title' => 'Receiving Backlog',
                    'subtitle' => 'Approved or partially received purchase orders that still require operational receiving progression.',
                    'columns' => ['Purchase Order', 'Workflow', 'Document Date', 'Receipts Recorded'],
                    'rows' => $procurementInsights['receiving_documents']->map(function ($document) {
                        $childReceiptCount = $document->childLinks
                            ->pluck('childDocument')
                            ->filter(fn ($child) => $child && $child->document_type === 'goods_receipt')
                            ->count();

                        return [
                            $document->document_no ?: ('#' . $document->id),
                            ucfirst((string) $document->workflow_status),
                            optional($document->document_date)->format('Y-m-d') ?: '-',
                            $childReceiptCount,
                        ];
                    })->values()->all(),
                    'empty' => 'No purchase orders remain in the receiving backlog for this period.',
                ],
                [
                    'title' => 'Supplier Invoice Matching Backlog',
                    'subtitle' => 'Supplier invoices that still need clean 2-way or 3-way matching before close readiness is achieved.',
                    'columns' => ['Invoice', 'Workflow', 'Match Status', 'Blocking Exceptions', 'Warnings'],
                    'rows' => $procurementInsights['matching_documents']->map(function ($document) {
                        return [
                            $document->document_no ?: ('#' . $document->id),
                            ucfirst((string) $document->workflow_status),
                            data_get($document->meta, 'matching.latest_status') ?: 'awaiting_match',
                            (int) data_get($document->meta, 'matching.blocking_exception_count', 0),
                            (int) data_get($document->meta, 'matching.warning_count', 0),
                        ];
                    })->values()->all(),
                    'empty' => 'No supplier invoices are blocking close with matching backlog for this period.',
                ],
                [
                    'title' => 'Procurement Discrepancy Ownership',
                    'subtitle' => 'Owner-level aging for unresolved procurement discrepancies through the period end.',
                    'columns' => ['Owner', 'Open Discrepancies', 'Aged > 2 Days', 'Aged > 7 Days'],
                    'rows' => $procurementInsights['owner_summary']->map(function (array $row) {
                        return [
                            $row['owner_id'] > 0 ? ($row['owner_name'] ?: ('User #' . $row['owner_id'])) : 'Unassigned',
                            (int) $row['open_count'],
                            (int) $row['aged_over_2_days'],
                            (int) $row['aged_over_7_days'],
                        ];
                    })->values()->all(),
                    'empty' => 'No procurement discrepancy ownership backlog exists for this period.',
                ],
                [
                    'title' => 'Procurement Ownership Activity',
                    'subtitle' => 'Canonical audit history for procurement discrepancy ownership actions recorded during the close period.',
                    'columns' => ['Date/Time', 'Reviewer', 'Action', 'Document', 'Reason'],
                    'rows' => $procurementOwnershipActivity->map(function ($activity) use ($procurementOwnershipLabels) {
                        return [
                            $activity->acted_at_label ?: '-',
                            $procurementOwnershipLabels[$activity->actor_id] ?? ('User #' . $activity->actor_id),
                            str((string) $activity->event_type)->afterLast('.')->replace('_', ' ')->title()->value(),
                            $activity->document_no ?: ('#' . $activity->document_id),
                            $activity->reason ?: '-',
                        ];
                    })->values()->all(),
                    'empty' => 'No procurement ownership actions were recorded in the close period.',
                ],
                [
                    'title' => 'Procurement Reassignment History',
                    'subtitle' => 'Track procurement discrepancy owner handoffs during the close period, including reviewer and rationale.',
                    'columns' => ['Date/Time', 'Document', 'Previous Owner', 'New Owner', 'Reviewer', 'Reason'],
                    'rows' => $procurementOwnershipActivity
                        ->where('event_type', 'procurement.discrepancy_reassigned')
                        ->map(function ($activity) use ($procurementOwnershipLabels) {
                            $previousOwner = $activity->before_owner_id > 0
                                ? ($procurementOwnershipLabels[$activity->before_owner_id] ?? ('User #' . $activity->before_owner_id))
                                : 'Unassigned';
                            $newOwner = $activity->after_owner_id > 0
                                ? ($procurementOwnershipLabels[$activity->after_owner_id] ?? ('User #' . $activity->after_owner_id))
                                : 'Unassigned';

                            return [
                                $activity->acted_at_label ?: '-',
                                $activity->document_no ?: ('#' . $activity->document_id),
                                $previousOwner,
                                $newOwner,
                                $procurementOwnershipLabels[$activity->actor_id] ?? ('User #' . $activity->actor_id),
                                $activity->reason ?: '-',
                            ];
                        })
                        ->values()
                        ->all(),
                    'empty' => 'No procurement discrepancy reassignments were recorded in the close period.',
                ],
                [
                    'title' => 'Aged Procurement Discrepancies',
                    'subtitle' => 'Most stale unresolved procurement discrepancies, including unassigned items and aged owner queues.',
                    'columns' => ['Invoice', 'Owner', 'Queue Status', 'Owner Age', 'Code'],
                    'rows' => $procurementInsights['discrepancy_exceptions']->map(function ($exception) {
                        $ownerAge = $exception->owner_assigned_at
                            ? $this->agingBucketLabel($exception->owner_assigned_at->diffInDays(now()))
                            : 'Unassigned';

                        return [
                            $exception->document?->document_no ?: ('#' . $exception->document_id),
                            $exception->owner_id > 0
                                ? trim((string) ($exception->owner?->surname . ' ' . $exception->owner?->first_name . ' ' . $exception->owner?->last_name)) ?: ('User #' . $exception->owner_id)
                                : 'Unassigned',
                            strtoupper((string) $exception->status),
                            $ownerAge,
                            str($exception->code)->replace('_', ' ')->title()->value(),
                        ];
                    })->values()->all(),
                    'empty' => 'No aged procurement discrepancies are blocking close for this period.',
                ],
                [
                    'title' => 'Pending Expense Documents',
                    'subtitle' => 'Native expense documents in the close period that still need workflow or posting action.',
                    'columns' => ['Document', 'Type', 'Workflow', 'Accounting', 'Amount'],
                    'rows' => $expenseInsights['pending_documents']->map(function ($document) {
                        return [
                            $document->document_no ?: ('#' . $document->id),
                            $document->document_type,
                            ucfirst((string) $document->workflow_status),
                            ucfirst((string) $document->accounting_status),
                            $this->money($document->gross_amount) . ' ' . $document->currency_code,
                        ];
                    })->values()->all(),
                    'empty' => 'No expense documents are blocking close for this period.',
                ],
                [
                    'title' => 'Outstanding Expense Balances',
                    'subtitle' => 'Posted advances and claims that still carry unresolved open amounts through the period end.',
                    'columns' => ['Document', 'Type', 'Claimant', 'Outstanding', 'Status'],
                    'rows' => $expenseInsights['outstanding_documents']->map(function ($document) {
                        return [
                            $document->document_no ?: ('#' . $document->id),
                            $document->document_type,
                            data_get($document->meta, 'expense.claimant_name') ?: 'Unassigned',
                            $this->money($document->open_amount) . ' ' . $document->currency_code,
                            strtoupper((string) data_get($document->meta, 'expense_chain.settlement_status', 'open')),
                        ];
                    })->values()->all(),
                    'empty' => 'No expense advances or claims remain outstanding through the period end.',
                ],
                [
                    'title' => 'Escalated Expense Approvals',
                    'subtitle' => 'Expense documents whose active approval step has already breached its configured SLA and needs escalation attention.',
                    'columns' => ['Document', 'Type', 'Current Approver', 'SLA', 'Escalation'],
                    'rows' => $expenseInsights['escalated_approvals']->map(function ($document) {
                        return [
                            $document->document_no ?: ('#' . $document->id),
                            $document->document_type,
                            data_get($document, 'approval_close_insight.current_step_role_label')
                                ?: data_get($document, 'approval_close_insight.current_step_label')
                                ?: 'Pending review',
                            data_get($document, 'approval_close_insight.sla_label', 'No SLA'),
                            data_get($document, 'approval_close_insight.escalation_message', 'Escalation path not configured'),
                        ];
                    })->values()->all(),
                    'empty' => 'No escalated expense approvals are blocking close for this period.',
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

    protected function agingBucketLabel(int $ageDays): string
    {
        return match (true) {
            $ageDays <= 3 => '0-3 days',
            $ageDays <= 7 => '4-7 days',
            $ageDays <= 14 => '8-14 days',
            default => '15+ days',
        };
    }

    protected function dataset(string $title, array $columns, Collection $rows, array $summary = [], array $sections = [], array $actions = []): array
    {
        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows->values()->all(),
            'summary' => $summary,
            'sections' => $sections,
            'actions' => $actions,
        ];
    }

    protected function contactLabels(array $contactIds): array
    {
        $ids = collect($contactIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return DB::table('contacts')
            ->whereIn('id', $ids->all())
            ->select('id', 'name', 'supplier_business_name')
            ->get()
            ->mapWithKeys(function ($contact) {
                $label = trim((string) ($contact->name ?? ''));
                $supplierBusinessName = trim((string) ($contact->supplier_business_name ?? ''));
                if ($supplierBusinessName !== '') {
                    $label = trim($label . ' (' . $supplierBusinessName . ')');
                }

                return [(int) $contact->id => $label !== '' ? $label : ('Contact #' . (int) $contact->id)];
            })
            ->all();
    }

    protected function userLabels(array $userIds): array
    {
        $ids = collect($userIds)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return DB::table('users')
            ->whereIn('id', $ids->all())
            ->select('id', 'surname', 'first_name', 'last_name', 'username')
            ->get()
            ->mapWithKeys(function ($user) {
                $label = trim(implode(' ', array_filter([
                    $user->surname ?? null,
                    $user->first_name ?? null,
                    $user->last_name ?? null,
                ])));
                $label = $label !== '' ? $label : trim((string) ($user->username ?? ''));

                return [(int) $user->id => $label !== '' ? $label : ('User #' . (int) $user->id)];
            })
            ->all();
    }

    protected function money($value): string
    {
        return number_format((float) $value, 2);
    }
}
