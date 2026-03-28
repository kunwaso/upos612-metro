<?php

namespace Modules\VasAccounting\Utils;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnterpriseFinanceReportUtil
{
    public function __construct(protected VasAccountingUtil $vasUtil)
    {
    }

    public function cashBankSummary(int $businessId): array
    {
        return [
            'cashbooks' => Schema::hasTable('vas_cashbooks')
                ? (int) DB::table('vas_cashbooks')->where('business_id', $businessId)->count()
                : 0,
            'bank_accounts' => Schema::hasTable('vas_bank_accounts')
                ? (int) DB::table('vas_bank_accounts')->where('business_id', $businessId)->count()
                : 0,
            'statement_imports' => Schema::hasTable('vas_bank_statement_imports')
                ? (int) DB::table('vas_bank_statement_imports')->where('business_id', $businessId)->count()
                : 0,
            'unmatched_lines' => Schema::hasTable('vas_bank_statement_lines')
                ? (int) DB::table('vas_bank_statement_lines')->where('business_id', $businessId)->where('match_status', 'unmatched')->count()
                : 0,
        ];
    }

    public function cashLedgerRows(int $businessId): Collection
    {
        return $this->ledgerRowsForPostingKey($businessId, 'cash');
    }

    public function bankLedgerRows(int $businessId): Collection
    {
        return $this->ledgerRowsForPostingKey($businessId, 'bank');
    }

    public function reconciliationRows(int $businessId): Collection
    {
        if (! Schema::hasTable('vas_bank_statement_lines') || ! Schema::hasTable('vas_bank_statement_imports')) {
            return collect();
        }

        return DB::table('vas_bank_statement_lines as line')
            ->join('vas_bank_statement_imports as import', 'import.id', '=', 'line.statement_import_id')
            ->leftJoin('vas_bank_accounts as bank_account', 'bank_account.id', '=', 'import.bank_account_id')
            ->leftJoin('vas_vouchers as voucher', 'voucher.id', '=', 'line.matched_voucher_id')
            ->where('line.business_id', $businessId)
            ->select(
                'line.id',
                'line.transaction_date',
                'line.description',
                'line.amount',
                'line.running_balance',
                'line.match_status',
                'line.matched_voucher_id',
                'voucher.voucher_no',
                'voucher.reference as voucher_reference',
                'bank_account.account_code as bank_account_code',
                'bank_account.bank_name',
                'import.reference_no as statement_reference'
            )
            ->orderByDesc('line.transaction_date')
            ->orderByDesc('line.id')
            ->get();
    }

    public function receivableOpenItems(int $businessId): Collection
    {
        return $this->subledgerOpenItems(
            $businessId,
            'accounts_receivable',
            'vas_receivable_allocations',
            'invoice_voucher_id',
            'debit - credit'
        );
    }

    public function receivableReceiptItems(int $businessId): Collection
    {
        return $this->subledgerSettlementItems(
            $businessId,
            'accounts_receivable',
            'vas_receivable_allocations',
            'payment_voucher_id',
            'credit - debit'
        );
    }

    public function receivableAging(int $businessId): array
    {
        return $this->agingBuckets($this->receivableOpenItems($businessId));
    }

    public function payableOpenItems(int $businessId): Collection
    {
        return $this->subledgerOpenItems(
            $businessId,
            'accounts_payable',
            'vas_payable_allocations',
            'bill_voucher_id',
            'credit - debit'
        );
    }

    public function payablePaymentItems(int $businessId): Collection
    {
        return $this->subledgerSettlementItems(
            $businessId,
            'accounts_payable',
            'vas_payable_allocations',
            'payment_voucher_id',
            'debit - credit'
        );
    }

    public function payableAging(int $businessId): array
    {
        return $this->agingBuckets($this->payableOpenItems($businessId));
    }

    public function invoiceRegister(int $businessId): Collection
    {
        if (! Schema::hasTable('vas_vouchers')) {
            return collect();
        }

        $contactName = $this->contactNameExpression('contacts');

        return DB::table('vas_vouchers as voucher')
            ->leftJoin('contacts', 'contacts.id', '=', 'voucher.contact_id')
            ->leftJoin('vas_einvoice_documents as einvoice', 'einvoice.voucher_id', '=', 'voucher.id')
            ->where('voucher.business_id', $businessId)
            ->whereIn('voucher.voucher_type', ['sales_invoice', 'sales_return', 'purchase_invoice', 'purchase_return', 'expense'])
            ->select(
                'voucher.id',
                'voucher.voucher_no',
                'voucher.voucher_type',
                'voucher.module_area',
                'voucher.status',
                'voucher.posting_date',
                'voucher.document_date',
                'voucher.reference',
                'voucher.total_debit',
                'voucher.total_credit',
                'voucher.contact_id',
                DB::raw($contactName . ' as contact_name'),
                'einvoice.document_no as einvoice_document_no',
                'einvoice.status as einvoice_status'
            )
            ->orderByDesc('voucher.posting_date')
            ->orderByDesc('voucher.id')
            ->get()
            ->map(function ($row) {
                $row->amount = round((float) $row->total_debit, 2);

                return $row;
            });
    }

    public function salesVatBook(int $businessId): Collection
    {
        return $this->vatBook($businessId, 'output', 'credit - debit');
    }

    public function purchaseVatBook(int $businessId): Collection
    {
        return $this->vatBook($businessId, 'input', 'debit - credit');
    }

    protected function ledgerRowsForPostingKey(int $businessId, string $postingMapKey): Collection
    {
        if (! Schema::hasTable('vas_journal_entries')) {
            return collect();
        }

        $accountId = $this->postingMapAccountId($businessId, $postingMapKey);
        if ($accountId <= 0) {
            return collect();
        }

        $contactName = $this->contactNameExpression('contacts');

        return DB::table('vas_journal_entries as journal')
            ->join('vas_vouchers as voucher', 'voucher.id', '=', 'journal.voucher_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'journal.contact_id')
            ->where('journal.business_id', $businessId)
            ->where('journal.account_id', $accountId)
            ->select(
                'journal.posting_date',
                'voucher.voucher_no',
                'voucher.reference',
                'voucher.voucher_type',
                'voucher.description as voucher_description',
                'journal.description',
                'journal.debit',
                'journal.credit',
                DB::raw($contactName . ' as contact_name')
            )
            ->orderByDesc('journal.posting_date')
            ->orderByDesc('journal.id')
            ->limit(250)
            ->get();
    }

    protected function vatBook(int $businessId, string $direction, string $taxAmountFormula): Collection
    {
        if (! Schema::hasTable('vas_journal_entries') || ! Schema::hasTable('vas_tax_codes')) {
            return collect();
        }

        $contactName = $this->contactNameExpression('contacts');

        return DB::table('vas_journal_entries as journal')
            ->join('vas_vouchers as voucher', 'voucher.id', '=', 'journal.voucher_id')
            ->join('vas_tax_codes as tax_code', 'tax_code.id', '=', 'journal.tax_code_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'voucher.contact_id')
            ->where('journal.business_id', $businessId)
            ->where('tax_code.direction', $direction)
            ->select(
                'voucher.id as voucher_id',
                'voucher.voucher_no',
                'voucher.posting_date',
                'voucher.total_debit',
                DB::raw($contactName . ' as contact_name'),
                'tax_code.code as tax_code',
                'tax_code.name as tax_name',
                DB::raw('SUM(' . $taxAmountFormula . ') as tax_amount')
            )
            ->groupBy('voucher.id', 'voucher.voucher_no', 'voucher.posting_date', 'voucher.total_debit', 'contact_name', 'tax_code.code', 'tax_code.name')
            ->havingRaw('ABS(SUM(' . $taxAmountFormula . ')) > 0.0001')
            ->orderByDesc('voucher.posting_date')
            ->orderByDesc('voucher.id')
            ->get()
            ->map(function ($row) {
                $row->tax_amount = round((float) $row->tax_amount, 2);
                $row->gross_amount = round((float) $row->total_debit, 2);

                return $row;
            });
    }

    protected function subledgerOpenItems(
        int $businessId,
        string $postingMapKey,
        string $allocationTable,
        string $allocationGroupColumn,
        string $netFormula
    ): Collection {
        if (! Schema::hasTable('vas_voucher_lines') || ! Schema::hasTable('vas_vouchers') || ! Schema::hasTable($allocationTable)) {
            return collect();
        }

        $accountId = $this->postingMapAccountId($businessId, $postingMapKey);
        if ($accountId <= 0) {
            return collect();
        }

        $lineTotals = DB::table('vas_voucher_lines')
            ->where('business_id', $businessId)
            ->where('account_id', $accountId)
            ->select('voucher_id', DB::raw('SUM(' . $netFormula . ') as source_amount'))
            ->groupBy('voucher_id');

        $allocations = DB::table($allocationTable)
            ->where('business_id', $businessId)
            ->select($allocationGroupColumn, DB::raw('SUM(amount) as allocated_amount'))
            ->groupBy($allocationGroupColumn);

        $contactName = $this->contactNameExpression('contacts');

        return DB::table('vas_vouchers as voucher')
            ->joinSub($lineTotals, 'line_totals', function ($join) {
                $join->on('line_totals.voucher_id', '=', 'voucher.id');
            })
            ->leftJoinSub($allocations, 'allocation_totals', function ($join) use ($allocationGroupColumn) {
                $join->on('allocation_totals.' . $allocationGroupColumn, '=', 'voucher.id');
            })
            ->leftJoin('contacts', 'contacts.id', '=', 'voucher.contact_id')
            ->where('voucher.business_id', $businessId)
            ->where('voucher.status', 'posted')
            ->where('line_totals.source_amount', '>', 0.0001)
            ->select(
                'voucher.id',
                'voucher.voucher_no',
                'voucher.voucher_type',
                'voucher.posting_date',
                'voucher.reference',
                'voucher.contact_id',
                DB::raw($contactName . ' as contact_name'),
                DB::raw('line_totals.source_amount as source_amount'),
                DB::raw('COALESCE(allocation_totals.allocated_amount, 0) as allocated_amount')
            )
            ->orderBy('voucher.posting_date')
            ->orderBy('voucher.id')
            ->get()
            ->map(function ($row) {
                $row->source_amount = round((float) $row->source_amount, 2);
                $row->allocated_amount = round((float) $row->allocated_amount, 2);
                $row->outstanding_amount = round(max($row->source_amount - $row->allocated_amount, 0), 2);
                $row->age_days = Carbon::parse($row->posting_date)->diffInDays(now());

                return $row;
            })
            ->filter(fn ($row) => $row->outstanding_amount > 0.0001)
            ->values();
    }

    protected function subledgerSettlementItems(
        int $businessId,
        string $postingMapKey,
        string $allocationTable,
        string $allocationGroupColumn,
        string $netFormula
    ): Collection {
        if (! Schema::hasTable('vas_voucher_lines') || ! Schema::hasTable('vas_vouchers') || ! Schema::hasTable($allocationTable)) {
            return collect();
        }

        $accountId = $this->postingMapAccountId($businessId, $postingMapKey);
        if ($accountId <= 0) {
            return collect();
        }

        $lineTotals = DB::table('vas_voucher_lines')
            ->where('business_id', $businessId)
            ->where('account_id', $accountId)
            ->select('voucher_id', DB::raw('SUM(' . $netFormula . ') as source_amount'))
            ->groupBy('voucher_id');

        $allocations = DB::table($allocationTable)
            ->where('business_id', $businessId)
            ->select($allocationGroupColumn, DB::raw('SUM(amount) as allocated_amount'))
            ->groupBy($allocationGroupColumn);

        $contactName = $this->contactNameExpression('contacts');

        return DB::table('vas_vouchers as voucher')
            ->joinSub($lineTotals, 'line_totals', function ($join) {
                $join->on('line_totals.voucher_id', '=', 'voucher.id');
            })
            ->leftJoinSub($allocations, 'allocation_totals', function ($join) use ($allocationGroupColumn) {
                $join->on('allocation_totals.' . $allocationGroupColumn, '=', 'voucher.id');
            })
            ->leftJoin('contacts', 'contacts.id', '=', 'voucher.contact_id')
            ->where('voucher.business_id', $businessId)
            ->where('voucher.status', 'posted')
            ->where('line_totals.source_amount', '>', 0.0001)
            ->select(
                'voucher.id',
                'voucher.voucher_no',
                'voucher.voucher_type',
                'voucher.posting_date',
                'voucher.reference',
                'voucher.contact_id',
                DB::raw($contactName . ' as contact_name'),
                DB::raw('line_totals.source_amount as source_amount'),
                DB::raw('COALESCE(allocation_totals.allocated_amount, 0) as allocated_amount')
            )
            ->orderBy('voucher.posting_date')
            ->orderBy('voucher.id')
            ->get()
            ->map(function ($row) {
                $row->source_amount = round((float) $row->source_amount, 2);
                $row->allocated_amount = round((float) $row->allocated_amount, 2);
                $row->available_amount = round(max($row->source_amount - $row->allocated_amount, 0), 2);

                return $row;
            })
            ->filter(fn ($row) => $row->available_amount > 0.0001)
            ->values();
    }

    protected function agingBuckets(Collection $items): array
    {
        $buckets = [
            'current' => 0.0,
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_90_plus' => 0.0,
            'total' => 0.0,
        ];

        foreach ($items as $item) {
            $amount = (float) ($item->outstanding_amount ?? 0);
            $ageDays = (int) ($item->age_days ?? 0);

            if ($ageDays <= 0) {
                $buckets['current'] += $amount;
            } elseif ($ageDays <= 30) {
                $buckets['days_1_30'] += $amount;
            } elseif ($ageDays <= 60) {
                $buckets['days_31_60'] += $amount;
            } elseif ($ageDays <= 90) {
                $buckets['days_61_90'] += $amount;
            } else {
                $buckets['days_90_plus'] += $amount;
            }

            $buckets['total'] += $amount;
        }

        return array_map(fn ($value) => round((float) $value, 2), $buckets);
    }

    protected function postingMapAccountId(int $businessId, string $postingMapKey): int
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);

        return (int) ((array) $settings->posting_map)[$postingMapKey];
    }

    protected function contactNameExpression(string $table): string
    {
        return "COALESCE(NULLIF({$table}.supplier_business_name, ''), NULLIF({$table}.name, ''), 'Contact')";
    }
}
