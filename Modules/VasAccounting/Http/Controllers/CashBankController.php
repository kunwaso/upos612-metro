<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\VasAccounting\Entities\VasBankAccount;
use Modules\VasAccounting\Entities\VasBankStatementImport;
use Modules\VasAccounting\Entities\VasBankStatementLine;
use Modules\VasAccounting\Entities\VasCashbook;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Http\Requests\StoreBankAccountRequest;
use Modules\VasAccounting\Http\Requests\StoreBankStatementImportRequest;
use Modules\VasAccounting\Http\Requests\StoreCashbookRequest;
use Modules\VasAccounting\Http\Requests\UpdateBankStatementLineRequest;
use Modules\VasAccounting\Services\BankStatementImportAdapterManager;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class CashBankController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected EnterpriseFinanceReportUtil $enterpriseReportUtil,
        protected BankStatementImportAdapterManager $statementImportAdapterManager
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.cash_bank.manage');

        $businessId = $this->businessId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['cash_bank'] ?? true) === false) {
            abort(404);
        }

        $cashbooks = Schema::hasTable('vas_cashbooks')
            ? VasCashbook::query()->with(['businessLocation', 'cashAccount'])->where('business_id', $businessId)->orderBy('code')->get()
            : collect();

        $bankAccounts = Schema::hasTable('vas_bank_accounts')
            ? VasBankAccount::query()->with(['businessLocation', 'ledgerAccount'])->where('business_id', $businessId)->orderBy('account_code')->get()
            : collect();

        $statementImports = Schema::hasTable('vas_bank_statement_imports')
            ? VasBankStatementImport::query()->with('bankAccount')->where('business_id', $businessId)->latest()->take(12)->get()
            : collect();

        $statementLines = Schema::hasTable('vas_bank_statement_lines')
            ? VasBankStatementLine::query()
                ->with(['statementImport.bankAccount', 'matchedVoucher'])
                ->where('business_id', $businessId)
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
            ->where(function ($query) {
                $query->where('module_area', 'cash_bank')
                    ->orWhereIn('voucher_type', ['cash_receipt', 'cash_payment', 'bank_receipt', 'bank_payment', 'fund_transfer', 'payment']);
            })
            ->latest('posting_date')
            ->latest('id')
            ->take(60)
            ->get();

        return view('vasaccounting::cash_bank.index', [
            'summary' => $this->enterpriseReportUtil->cashBankSummary($businessId),
            'cashbooks' => $cashbooks,
            'bankAccounts' => $bankAccounts,
            'statementImports' => $statementImports,
            'statementLines' => $statementLines,
            'candidateVouchers' => $candidateVouchers,
            'cashLedgerRows' => $this->enterpriseReportUtil->cashLedgerRows($businessId)->take(10),
            'bankLedgerRows' => $this->enterpriseReportUtil->bankLedgerRows($businessId)->take(10),
            'reconciliationRows' => $this->enterpriseReportUtil->reconciliationRows($businessId)->take(10),
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'chartOptions' => $this->vasUtil->chartOptions($businessId),
            'providerOptions' => array_keys((array) config('vasaccounting.bank_statement_import_adapters', [])),
            'defaultProvider' => (string) (((array) $settings->integration_settings)['bank_statement_provider'] ?? 'manual'),
        ]);
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

        return redirect()
            ->route('vasaccounting.cash_bank.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.cashbook_saved')]);
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

        return redirect()
            ->route('vasaccounting.cash_bank.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.bank_account_saved')]);
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

        $this->refreshStatementImportStatus($statementImport->fresh('lines'));

        return redirect()
            ->route('vasaccounting.cash_bank.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.statement_imported')]);
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

        $this->refreshStatementImportStatus($lineModel->statementImport->fresh('lines'));

        $messageKey = $matchStatus === 'matched'
            ? 'statement_line_reconciled'
            : ($matchStatus === 'ignored' ? 'statement_line_ignored' : 'statement_line_cleared');

        return redirect()
            ->route('vasaccounting.cash_bank.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.' . $messageKey)]);
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

        $statementImport->status = match (true) {
            $matched === 0 && $ignored === 0 && $unmatched > 0 => 'imported',
            $unmatched === 0 && ($matched + $ignored) > 0 => 'reconciled',
            default => 'in_review',
        };
        $statementImport->save();
    }
}
