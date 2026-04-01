<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Application\DTOs\DocumentCreateData;
use Modules\VasAccounting\Application\DTOs\PostingContext;
use Modules\VasAccounting\Application\DTOs\ReversalContext;
use Modules\VasAccounting\Contracts\FinanceDocumentServiceInterface;
use Modules\VasAccounting\Contracts\PostingRuleEngineInterface;
use Modules\VasAccounting\Contracts\TreasuryReconciliationServiceInterface;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTreasuryException;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTreasuryReconciliation;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasBankAccount;
use Modules\VasAccounting\Entities\VasBankStatementImport;
use Modules\VasAccounting\Entities\VasBankStatementLine;
use Modules\VasAccounting\Entities\VasCashbook;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Http\Requests\StoreBankAccountRequest;
use Modules\VasAccounting\Http\Requests\StoreBankStatementImportRequest;
use Modules\VasAccounting\Http\Requests\StoreCashbookRequest;
use Modules\VasAccounting\Http\Requests\StoreTreasuryFinanceDocumentRequest;
use Modules\VasAccounting\Http\Requests\TreasuryReconciliationActionRequest;
use Modules\VasAccounting\Http\Requests\UpdateBankStatementLineRequest;
use Modules\VasAccounting\Services\BankStatementImportAdapterManager;
use Modules\VasAccounting\Contracts\TreasuryExceptionServiceInterface;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class CashBankController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected EnterpriseFinanceReportUtil $enterpriseReportUtil,
        protected BankStatementImportAdapterManager $statementImportAdapterManager,
        protected TreasuryExceptionServiceInterface $treasuryExceptionService,
        protected TreasuryReconciliationServiceInterface $treasuryReconciliationService,
        protected FinanceDocumentServiceInterface $financeDocumentService,
        protected PostingRuleEngineInterface $postingRuleEngine
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);
        $selectedLocationId = $this->selectedLocationId($request);
        $closeScope = $this->closeScope($request, $businessId);
        $closePeriod = $closeScope['period'];
        $periodStart = $closeScope['start_date'];
        $periodEnd = $closeScope['end_date'];
        $workspaceFocus = $this->workspaceFocus($request);
        $exceptionStatuses = $this->treasuryExceptionStatuses($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['cash_bank'] ?? true) === false) {
            abort(404);
        }

        $cashbooks = Schema::hasTable('vas_cashbooks')
            ? VasCashbook::query()
                ->with(['businessLocation', 'cashAccount'])
                ->where('business_id', $businessId)
                ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
                ->orderBy('code')
                ->get()
            : collect();

        $bankAccounts = Schema::hasTable('vas_bank_accounts')
            ? VasBankAccount::query()
                ->with(['businessLocation', 'ledgerAccount'])
                ->where('business_id', $businessId)
                ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
                ->orderBy('account_code')
                ->get()
            : collect();

        $statementImports = Schema::hasTable('vas_bank_statement_imports')
            ? VasBankStatementImport::query()
                ->with('bankAccount')
                ->where('business_id', $businessId)
                ->when($selectedLocationId, function ($query) use ($selectedLocationId) {
                    $query->whereHas('bankAccount', fn ($bankAccountQuery) => $bankAccountQuery->where('business_location_id', $selectedLocationId));
                })
                ->when($periodStart, fn ($query) => $query->whereDate('imported_at', '>=', $periodStart))
                ->when($periodEnd, fn ($query) => $query->whereDate('imported_at', '<=', $periodEnd))
                ->latest()
                ->take(12)
                ->get()
            : collect();

        $nativeTreasuryDocuments = Schema::hasTable('vas_fin_documents')
            ? FinanceDocument::query()
                ->where('business_id', $businessId)
                ->where('document_family', 'cash_bank')
                ->whereIn('document_type', ['cash_transfer', 'bank_transfer', 'petty_cash_expense'])
                ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
                ->when($periodStart || $periodEnd, fn ($query) => $this->applyDocumentDateScope($query, $periodStart, $periodEnd))
                ->when($workspaceFocus === 'pending_documents', function ($query) {
                    $query->whereIn('workflow_status', ['draft', 'submitted', 'approved']);
                })
                ->latest('document_date')
                ->latest('id')
                ->take(12)
                ->get()
            : collect();

        $statementLines = Schema::hasTable('vas_bank_statement_lines')
            ? VasBankStatementLine::query()
                ->with([
                    'statementImport.bankAccount',
                    'matchedVoucher',
                    'treasuryException.recommendedDocument',
                    'financeReconciliations.document',
                ])
                ->where('business_id', $businessId)
                ->when($selectedLocationId, function ($query) use ($selectedLocationId) {
                    $query->whereHas('statementImport.bankAccount', fn ($bankAccountQuery) => $bankAccountQuery->where('business_location_id', $selectedLocationId));
                })
                ->when($periodStart, fn ($query) => $query->whereDate('transaction_date', '>=', $periodStart))
                ->when($periodEnd, fn ($query) => $query->whereDate('transaction_date', '<=', $periodEnd))
                ->when($workspaceFocus === 'treasury_exceptions', function ($query) use ($exceptionStatuses) {
                    $query->whereHas('treasuryException', function ($exceptionQuery) use ($exceptionStatuses) {
                        $exceptionQuery->whereIn('status', $exceptionStatuses);
                    });
                })
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->get()
                ->sortBy(fn ($line) => ['unmatched' => 0, 'matched' => 1, 'ignored' => 2][$line->match_status] ?? 9)
                ->take(20)
                ->values()
            : collect();

        $candidateVouchers = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('status', 'posted')
            ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
            ->where(function ($query) {
                $query->where('module_area', 'cash_bank')
                    ->orWhereIn('voucher_type', ['cash_receipt', 'cash_payment', 'bank_receipt', 'bank_payment', 'fund_transfer', 'payment']);
            })
            ->latest('posting_date')
            ->latest('id')
            ->take(60)
            ->get();

        $summary = $this->enterpriseReportUtil->cashBankSummary($businessId);
        $treasuryExceptionSummary = Schema::hasTable('vas_fin_treasury_exceptions')
            ? $this->treasuryExceptionService->queueSummary($businessId, $selectedLocationId)
            : ['open' => 0, 'suggested' => 0, 'ignored' => 0, 'resolved' => 0];
        $treasuryExceptionQueue = Schema::hasTable('vas_fin_treasury_exceptions')
            ? $this->treasuryExceptionService->queue($businessId, 8, $selectedLocationId)
            : [];

        if ($workspaceFocus === 'treasury_exceptions') {
            $treasuryExceptionQueue = collect($treasuryExceptionQueue)
                ->filter(fn ($exception) => in_array((string) ($exception['status'] ?? 'open'), $exceptionStatuses, true))
                ->values()
                ->all();
        }

        if (Schema::hasTable('vas_fin_treasury_exceptions') && ($periodStart || $periodEnd)) {
            $treasuryExceptionSummary = [
                'open' => $this->treasuryExceptionScopeCount($businessId, $selectedLocationId, $periodStart, $periodEnd, ['open']),
                'suggested' => $this->treasuryExceptionScopeCount($businessId, $selectedLocationId, $periodStart, $periodEnd, ['suggested']),
                'ignored' => $this->treasuryExceptionScopeCount($businessId, $selectedLocationId, $periodStart, $periodEnd, ['ignored']),
                'resolved' => $this->treasuryExceptionScopeCount($businessId, $selectedLocationId, $periodStart, $periodEnd, ['resolved']),
            ];

            $treasuryExceptionQueue = collect($treasuryExceptionQueue)->filter(function ($exception) use ($periodStart, $periodEnd) {
                $transactionDate = optional(optional($exception->statementLine)->transaction_date)?->toDateString();
                if (! $transactionDate) {
                    return false;
                }

                if ($periodStart && $transactionDate < $periodStart) {
                    return false;
                }

                if ($periodEnd && $transactionDate > $periodEnd) {
                    return false;
                }

                return true;
            })->values()->all();
        }

        if ($selectedLocationId) {
            $summary = [
                'cashbooks' => $cashbooks->count(),
                'bank_accounts' => $bankAccounts->count(),
                'statement_imports' => $statementImports->count(),
                'unmatched_lines' => $statementLines->where('match_status', 'unmatched')->count(),
            ];
        }

        if ($closePeriod) {
            $summary = [
                'cashbooks' => $cashbooks->count(),
                'bank_accounts' => $bankAccounts->count(),
                'statement_imports' => $statementImports->count(),
                'unmatched_lines' => $statementLines->where('match_status', 'unmatched')->count(),
            ];
        }

        $cashLedgerRows = $this->enterpriseReportUtil->cashLedgerRows($businessId)
            ->when($selectedLocationId, fn ($rows) => $rows->filter(function ($row) use ($selectedLocationId) {
                $locationId = (int) ($row->business_location_id ?? $row->location_id ?? 0);

                return $locationId === $selectedLocationId;
            }))
            ->take(10)
            ->values();
        $bankLedgerRows = $this->enterpriseReportUtil->bankLedgerRows($businessId)
            ->when($selectedLocationId, fn ($rows) => $rows->filter(function ($row) use ($selectedLocationId) {
                $locationId = (int) ($row->business_location_id ?? $row->location_id ?? 0);

                return $locationId === $selectedLocationId;
            }))
            ->take(10)
            ->values();
        $reconciliationRows = $this->enterpriseReportUtil->reconciliationRows($businessId)
            ->when($selectedLocationId, fn ($rows) => $rows->filter(function ($row) use ($selectedLocationId) {
                $locationId = (int) ($row->business_location_id ?? $row->location_id ?? 0);

                return $locationId === $selectedLocationId;
            }))
            ->take(10)
            ->values();

        return view('vasaccounting::cash_bank.index', [
            'summary' => $summary,
            'cashbooks' => $cashbooks,
            'bankAccounts' => $bankAccounts,
            'statementImports' => $statementImports,
            'nativeTreasuryDocuments' => $nativeTreasuryDocuments,
            'statementLines' => $statementLines,
            'candidateVouchers' => $candidateVouchers,
            'cashLedgerRows' => $cashLedgerRows,
            'bankLedgerRows' => $bankLedgerRows,
            'reconciliationRows' => $reconciliationRows,
            'treasuryExceptionSummary' => $treasuryExceptionSummary,
            'treasuryExceptionQueue' => $treasuryExceptionQueue,
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'selectedLocationId' => $selectedLocationId,
            'closePeriod' => $closePeriod,
            'workspaceFocus' => $workspaceFocus,
            'workspaceFocusLabel' => $this->workspaceFocusLabel($workspaceFocus),
            'exceptionStatusFilter' => $exceptionStatuses,
            'chartOptions' => $this->vasUtil->chartOptions($businessId),
            'providerOptions' => $this->vasUtil->providerOptions('bank_statement_import_adapters'),
            'defaultProvider' => (string) (((array) $settings->integration_settings)['bank_statement_provider'] ?? 'manual'),
        ]);
    }

    public function storeTreasuryDocument(StoreTreasuryFinanceDocumentRequest $request): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);
        $validated = $request->validated();

        try {
            $document = $this->financeDocumentService->create(new DocumentCreateData(
                [
                    'business_id' => $businessId,
                    'document_family' => 'cash_bank',
                    'document_type' => $validated['document_type'],
                    'document_no' => $validated['document_no'],
                    'external_reference' => $validated['external_reference'] ?? null,
                    'business_location_id' => $validated['business_location_id'] ?? null,
                    'currency_code' => config('vasaccounting.book_currency', 'VND'),
                    'exchange_rate' => 1,
                    'document_date' => $validated['document_date'],
                    'posting_date' => $validated['posting_date'] ?? $validated['document_date'],
                    'workflow_status' => 'draft',
                    'accounting_status' => 'not_ready',
                    'gross_amount' => $validated['amount'],
                    'tax_amount' => 0,
                    'net_amount' => $validated['amount'],
                    'open_amount' => 0,
                    'meta' => [
                        'treasury_document' => [
                            'source_account_id' => (int) $validated['source_account_id'],
                            'target_account_id' => (int) $validated['target_account_id'],
                        ],
                    ],
                ],
                [[
                    'line_type' => $validated['document_type'] === 'petty_cash_expense' ? 'expense' : 'transfer',
                    'business_location_id' => $validated['business_location_id'] ?? null,
                    'description' => $validated['description'],
                    'quantity' => 1,
                    'unit_price' => $validated['amount'],
                    'line_amount' => $validated['amount'],
                    'tax_amount' => 0,
                    'gross_amount' => $validated['amount'],
                    'debit_account_id' => (int) $validated['target_account_id'],
                    'credit_account_id' => (int) $validated['source_account_id'],
                    'payload' => [
                        'treasury_document_type' => $validated['document_type'],
                    ],
                ]]
            ));

            return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.treasury_document_saved', ['document' => $document->document_no])]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToCashBank($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function storeCashbook(StoreCashbookRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);

        VasCashbook::create([
            'business_id' => $businessId,
            'code' => strtoupper((string) $request->input('code')),
            'name' => $request->input('name'),
            'business_location_id' => $request->input('business_location_id'),
            'cash_account_id' => $request->input('cash_account_id'),
            'status' => $request->input('status', 'active'),
        ]);

        return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.cashbook_saved')]);
    }

    public function storeBankAccount(StoreBankAccountRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);

        VasBankAccount::create([
            'business_id' => $businessId,
            'account_code' => strtoupper((string) $request->input('account_code')),
            'bank_name' => $request->input('bank_name'),
            'account_name' => $request->input('account_name'),
            'account_number' => $request->input('account_number'),
            'business_location_id' => $request->input('business_location_id'),
            'ledger_account_id' => $request->input('ledger_account_id'),
            'currency_code' => $request->input('currency_code', 'VND'),
            'status' => $request->input('status', 'active'),
        ]);

        return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.bank_account_saved')]);
    }

    public function importStatement(StoreBankStatementImportRequest $request): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $provider = (string) ($request->input('provider') ?: (((array) $settings->integration_settings)['bank_statement_provider'] ?? 'manual'));
        $lines = $this->parseStatementLines((string) $request->input('statement_lines'));
        $adapter = $this->statementImportAdapterManager->resolve($provider);
        $result = $adapter->import([
            'provider' => $provider,
            'lines' => $lines->all(),
        ]);

        $statementImport = VasBankStatementImport::create([
            'business_id' => $businessId,
            'bank_account_id' => $request->input('bank_account_id'),
            'provider' => $provider,
            'reference_no' => $request->input('reference_no'),
            'status' => $result['status'] ?? 'imported',
            'imported_by' => auth()->id(),
            'imported_at' => now(),
            'meta' => [
                'line_count' => $lines->count(),
            ],
        ]);

        foreach ((array) ($result['lines'] ?? []) as $line) {
            $statementImport->lines()->create([
                'business_id' => $businessId,
                'transaction_date' => $line['transaction_date'],
                'description' => $line['description'] ?? null,
                'amount' => $line['amount'] ?? 0,
                'running_balance' => $line['running_balance'] ?? null,
                'match_status' => $line['match_status'] ?? 'unmatched',
                'meta' => $line['meta'] ?? null,
            ]);
        }

        $this->treasuryExceptionService->refreshForImport($statementImport->fresh('lines'), $businessId);
        $this->refreshStatementImportStatus($statementImport->fresh('lines'));

        return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.statement_imported')]);
    }

    public function reconcileLine(UpdateBankStatementLineRequest $request, int $line): RedirectResponse
    {
        $businessId = $this->businessId($request);
        $lineModel = VasBankStatementLine::query()
            ->with('statementImport')
            ->where('business_id', $businessId)
            ->findOrFail($line);

        $matchStatus = (string) $request->input('match_status');
        $matchedVoucherId = null;

        if ($matchStatus === 'matched') {
            $matchedVoucherId = VasVoucher::query()
                ->where('business_id', $businessId)
                ->where('status', 'posted')
                ->findOrFail((int) $request->input('matched_voucher_id'))
                ->id;
        }

        $meta = (array) $lineModel->meta;
        $meta['reconciliation_notes'] = $request->input('notes');
        $meta['reconciled_at'] = now()->toDateTimeString();
        $meta['reconciled_by'] = auth()->id();

        $lineModel->update([
            'match_status' => $matchStatus,
            'matched_voucher_id' => $matchedVoucherId,
            'meta' => $meta,
        ]);

        $this->treasuryExceptionService->refreshForStatementLine($lineModel->fresh(), $businessId);
        $this->refreshStatementImportStatus($lineModel->statementImport->fresh('lines'));

        $messageKey = $matchStatus === 'matched'
            ? 'statement_line_reconciled'
            : ($matchStatus === 'ignored' ? 'statement_line_ignored' : 'statement_line_cleared');

        return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.' . $messageKey)]);
    }

    public function reconcileLineCanonically(TreasuryReconciliationActionRequest $request, int $line): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);

        try {
            $lineModel = VasBankStatementLine::query()
                ->with(['statementImport', 'treasuryException'])
                ->where('business_id', $businessId)
                ->findOrFail($line);
            $actionContext = $this->treasuryActionContext($request, $businessId, 'Cash/bank canonical reconciliation');

            $financeDocumentId = (int) ($request->input('finance_document_id')
                ?: optional($lineModel->treasuryException)->recommended_document_id
                ?: data_get($lineModel->treasuryException?->meta, 'top_candidate.document_id'));

            if ($financeDocumentId <= 0) {
                throw ValidationException::withMessages([
                    'finance_document_id' => 'No canonical finance document recommendation is available for this statement line.',
                ]);
            }

            $document = FinanceDocument::query()
                ->where('business_id', $businessId)
                ->findOrFail($financeDocumentId);

            $this->treasuryReconciliationService->reconcile(
                $lineModel,
                $document,
                $actionContext,
                $request->filled('finance_open_item_id') ? (int) $request->input('finance_open_item_id') : null
            );

            $this->treasuryExceptionService->refreshForStatementLine(
                $lineModel->fresh(),
                $businessId,
                $actionContext
            );
            $this->refreshStatementImportStatus($lineModel->statementImport->fresh('lines'));

            return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.statement_line_canonical_reconciled')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToCashBank($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function reverseCanonicalReconciliation(TreasuryReconciliationActionRequest $request, int $reconciliation): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);

        try {
            $reconciliationModel = FinanceTreasuryReconciliation::query()
                ->with('statementLine.statementImport')
                ->where('business_id', $businessId)
                ->findOrFail($reconciliation);
            $actionContext = $this->treasuryActionContext($request, $businessId, 'Cash/bank canonical reconciliation reversal');

            $reconciliationModel = $this->treasuryReconciliationService->reverse(
                $reconciliationModel,
                $actionContext
            );

            $statementLine = $reconciliationModel->statementLine->fresh();
            $this->treasuryExceptionService->refreshForStatementLine(
                $statementLine,
                $businessId,
                $actionContext
            );
            $this->refreshStatementImportStatus($reconciliationModel->statementLine->statementImport->fresh('lines'));

            return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.statement_line_canonical_reversal_completed')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToCashBank($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function refreshTreasuryException(TreasuryReconciliationActionRequest $request, int $line): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);

        try {
            $lineModel = VasBankStatementLine::query()
                ->with('statementImport')
                ->where('business_id', $businessId)
                ->findOrFail($line);
            $actionContext = $this->treasuryActionContext($request, $businessId, 'Cash/bank treasury exception refresh');

            $this->treasuryExceptionService->refreshForStatementLine($lineModel, $businessId, $actionContext);
            $this->refreshStatementImportStatus($lineModel->statementImport->fresh('lines'));

            return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.statement_line_treasury_exception_refreshed')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToCashBank($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function ignoreTreasuryException(TreasuryReconciliationActionRequest $request, int $line): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $lineModel = VasBankStatementLine::query()
                ->with('statementImport')
                ->where('business_id', $businessId)
                ->findOrFail($line);
            $actionContext = $this->treasuryActionContext($request, $businessId, 'Cash/bank treasury exception ignored');
            $meta = (array) $lineModel->meta;
            $meta['reconciliation_notes'] = $validated['reason'];
            $meta['reconciled_at'] = now()->toDateTimeString();
            $meta['reconciled_by'] = auth()->id();

            $lineModel->update([
                'match_status' => 'ignored',
                'matched_voucher_id' => null,
                'meta' => $meta,
            ]);

            $this->treasuryExceptionService->refreshForStatementLine($lineModel->fresh(), $businessId, $actionContext);
            $this->refreshStatementImportStatus($lineModel->statementImport->fresh('lines'));

            return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.statement_line_treasury_exception_ignored')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToCashBank($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function submitTreasuryDocument(TreasuryReconciliationActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);

        try {
            $documentModel = $this->treasuryDocumentOrFail($document, $businessId);
            $this->financeDocumentService->submit(
                $documentModel->id,
                $this->treasuryActionContext($request, $businessId, 'Cash/bank native treasury document submitted')
            );

            return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.treasury_document_submitted')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToCashBank($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function approveTreasuryDocument(TreasuryReconciliationActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);

        try {
            $documentModel = $this->treasuryDocumentOrFail($document, $businessId);
            $this->financeDocumentService->approve(
                $documentModel->id,
                $this->treasuryActionContext($request, $businessId, 'Cash/bank native treasury document approved')
            );

            return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.treasury_document_approved')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToCashBank($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function postTreasuryDocument(TreasuryReconciliationActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);

        try {
            $documentModel = $this->treasuryDocumentOrFail($document, $businessId);
            $this->postingRuleEngine->post(
                $documentModel,
                (string) $request->input('event_type', 'post'),
                new PostingContext(
                    (int) auth()->id(),
                    $businessId,
                    $request->input('reason') ?: 'Cash/bank native treasury document posted',
                    $request->input('request_id') ?: (string) Str::uuid(),
                    $request->ip(),
                    $request->userAgent(),
                    (array) $request->input('meta', [])
                )
            );

            return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.treasury_document_posted')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToCashBank($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function reverseTreasuryDocument(TreasuryReconciliationActionRequest $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);

        try {
            $documentModel = $this->treasuryDocumentOrFail($document, $businessId);
            $this->postingRuleEngine->reverse(
                $documentModel,
                (string) $request->input('event_type', 'post'),
                new ReversalContext(
                    (int) auth()->id(),
                    $businessId,
                    $request->input('reason') ?: 'Cash/bank native treasury document reversed',
                    $request->input('request_id') ?: (string) Str::uuid(),
                    $request->ip(),
                    $request->userAgent(),
                    (array) $request->input('meta', [])
                )
            );

            return $this->redirectBackToCashBank($request, ['success' => true, 'msg' => __('vasaccounting::lang.treasury_document_reversed')]);
        } catch (\Throwable $exception) {
            return $this->redirectBackToCashBank($request, ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    protected function parseStatementLines(string $input): Collection
    {
        $rows = collect(preg_split('/\r\n|\r|\n/', trim($input)))
            ->filter(fn ($line) => trim((string) $line) !== '')
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'statement_lines' => 'Provide at least one bank statement line.',
            ]);
        }

        return $rows->map(function (string $row, int $index) {
            $parts = array_map('trim', explode('|', $row));

            if (count($parts) < 3) {
                throw ValidationException::withMessages([
                    'statement_lines' => 'Line ' . ($index + 1) . ' must use YYYY-MM-DD|Description|Amount|Running balance(optional).',
                ]);
            }

            try {
                $transactionDate = Carbon::parse($parts[0])->toDateString();
            } catch (\Throwable $exception) {
                throw ValidationException::withMessages([
                    'statement_lines' => 'Line ' . ($index + 1) . ' has an invalid date.',
                ]);
            }

            if (! is_numeric(str_replace(',', '', $parts[2]))) {
                throw ValidationException::withMessages([
                    'statement_lines' => 'Line ' . ($index + 1) . ' has an invalid amount.',
                ]);
            }

            if (isset($parts[3]) && $parts[3] !== '' && ! is_numeric(str_replace(',', '', $parts[3]))) {
                throw ValidationException::withMessages([
                    'statement_lines' => 'Line ' . ($index + 1) . ' has an invalid running balance.',
                ]);
            }

            return [
                'transaction_date' => $transactionDate,
                'description' => $parts[1],
                'amount' => (float) str_replace(',', '', $parts[2]),
                'running_balance' => isset($parts[3]) && $parts[3] !== '' ? (float) str_replace(',', '', $parts[3]) : null,
                'match_status' => 'unmatched',
                'meta' => [
                    'raw_line' => $row,
                ],
            ];
        });
    }

    protected function refreshStatementImportStatus(VasBankStatementImport $statementImport): void
    {
        $matched = (int) $statementImport->lines->where('match_status', 'matched')->count();
        $ignored = (int) $statementImport->lines->where('match_status', 'ignored')->count();
        $unmatched = (int) $statementImport->lines->where('match_status', 'unmatched')->count();
        $openExceptions = \Modules\VasAccounting\Domain\FinanceCore\Models\FinanceTreasuryException::query()
            ->whereIn('statement_line_id', $statementImport->lines->pluck('id')->all())
            ->whereIn('status', ['open', 'suggested'])
            ->count();

        $statementImport->status = match (true) {
            $matched === 0 && $ignored === 0 && $unmatched > 0 => 'imported',
            $unmatched === 0 && $openExceptions === 0 && ($matched + $ignored) > 0 => 'reconciled',
            default => 'in_review',
        };
        $statementImport->save();
    }

    protected function treasuryActionContext(
        TreasuryReconciliationActionRequest $request,
        int $businessId,
        string $defaultReason
    ): ActionContext {
        return new ActionContext(
            (int) auth()->id(),
            $businessId,
            $request->input('reason') ?: $defaultReason,
            $request->input('request_id') ?: (string) Str::uuid(),
            $request->ip(),
            $request->userAgent(),
            (array) $request->input('meta', [])
        );
    }

    protected function treasuryDocumentOrFail(int $documentId, int $businessId): FinanceDocument
    {
        return FinanceDocument::query()
            ->where('business_id', $businessId)
            ->where('document_family', 'cash_bank')
            ->whereIn('document_type', ['cash_transfer', 'bank_transfer', 'petty_cash_expense'])
            ->findOrFail($documentId);
    }

    protected function redirectBackToCashBank(Request $request, array $status): RedirectResponse
    {
        $previousUrl = url()->previous();
        $cashBankUrl = route('vasaccounting.cash_bank.index');

        if ($previousUrl && str_starts_with($previousUrl, $cashBankUrl)) {
            return redirect()->to($previousUrl)->with('status', $status);
        }

        return redirect()
            ->route('vasaccounting.cash_bank.index', $this->cashBankIndexQuery($request))
            ->with('status', $status);
    }

    protected function closeScope(Request $request, int $businessId): array
    {
        $periodId = (int) $request->query('period_id', 0);
        if ($periodId <= 0) {
            return [
                'period' => null,
                'start_date' => null,
                'end_date' => null,
            ];
        }

        $period = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->find($periodId);

        return [
            'period' => $period,
            'start_date' => optional(optional($period)->start_date)->toDateString(),
            'end_date' => optional(optional($period)->end_date)->toDateString(),
        ];
    }

    protected function cashBankIndexQuery(Request $request): array
    {
        return array_filter([
            'period_id' => $request->query('period_id'),
            'focus' => $this->workspaceFocus($request),
            'exception_status' => $request->query('exception_status'),
        ], fn ($value) => filled($value));
    }

    protected function workspaceFocus(Request $request): ?string
    {
        $focus = (string) $request->query('focus', '');

        return in_array($focus, ['pending_documents', 'treasury_exceptions'], true)
            ? $focus
            : null;
    }

    protected function workspaceFocusLabel(?string $focus): ?string
    {
        return match ($focus) {
            'pending_documents' => 'Pending treasury documents',
            'treasury_exceptions' => 'Treasury reconciliation exceptions',
            default => null,
        };
    }

    protected function treasuryExceptionStatuses(Request $request): array
    {
        $raw = collect(explode(',', (string) $request->query('exception_status', '')))
            ->map(fn ($status) => trim((string) $status))
            ->filter()
            ->values();

        $allowed = ['open', 'suggested', 'ignored', 'resolved'];

        $statuses = $raw->isEmpty()
            ? ['open', 'suggested']
            : $raw->filter(fn ($status) => in_array($status, $allowed, true))->values()->all();

        return $statuses === [] ? ['open', 'suggested'] : $statuses;
    }

    protected function applyDocumentDateScope($query, ?string $periodStart, ?string $periodEnd)
    {
        if (! $periodStart && ! $periodEnd) {
            return $query;
        }

        if ($periodStart) {
            $query->whereDate(DB::raw('COALESCE(posting_date, document_date)'), '>=', $periodStart);
        }

        if ($periodEnd) {
            $query->whereDate(DB::raw('COALESCE(posting_date, document_date)'), '<=', $periodEnd);
        }

        return $query;
    }

    protected function treasuryExceptionScopeCount(
        int $businessId,
        ?int $selectedLocationId,
        ?string $periodStart,
        ?string $periodEnd,
        array $statuses
    ): int {
        return (int) FinanceTreasuryException::query()
            ->where('business_id', $businessId)
            ->whereIn('status', $statuses)
            ->when($selectedLocationId, function ($query) use ($selectedLocationId) {
                $query->whereHas('statementLine.statementImport.bankAccount', function ($bankAccountQuery) use ($selectedLocationId) {
                    $bankAccountQuery->where('business_location_id', $selectedLocationId);
                });
            })
            ->whereHas('statementLine', function ($query) use ($periodStart, $periodEnd) {
                if ($periodStart) {
                    $query->whereDate('transaction_date', '>=', $periodStart);
                }

                if ($periodEnd) {
                    $query->whereDate('transaction_date', '<=', $periodEnd);
                }
            })
            ->count();
    }
}
