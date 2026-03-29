<?php

namespace Modules\VasAccounting\Services;

use App\BusinessLocation;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class CutoverParityService
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected EnterpriseFinanceReportUtil $enterpriseFinanceReportUtil,
        protected VasInventoryValuationService $inventoryValuationService,
        protected TransactionUtil $transactionUtil
    ) {
    }

    public function build(int $businessId, ?string $period = null, array $branchIds = []): array
    {
        $window = $this->resolveWindow($period);
        $normalizedBranchIds = collect($branchIds)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->values()
            ->all();

        $legacyActivity = $this->legacyTreasuryMovement($businessId, $window['start'], $window['end'], $normalizedBranchIds);
        $vasActivity = $this->vasHistoricalTreasuryMovement($businessId, $window['start'], $window['end'], $normalizedBranchIds);
        $legacyTreasury = $this->legacyTreasuryBalance($businessId, $window['end'], $normalizedBranchIds);
        $vasTreasury = $this->vasTreasuryBalance($businessId, $window['end'], $normalizedBranchIds);
        $legacyReceivables = $this->legacyReceivablesOutstanding($businessId, $window['end'], null);
        $vasReceivables = $this->vasSubledgerOutstanding($businessId, 'accounts_receivable', 'vas_receivable_allocations', 'invoice_voucher_id', 'debit - credit', $window['end'], null);
        $legacyPayables = $this->legacyPayablesOutstanding($businessId, $window['end'], null);
        $vasPayables = $this->vasSubledgerOutstanding($businessId, 'accounts_payable', 'vas_payable_allocations', 'bill_voucher_id', 'credit - debit', $window['end'], null);
        $legacyInventory = $this->legacyInventoryValue($businessId, $window['end'], $normalizedBranchIds);
        $vasInventory = $this->vasInventoryValue($businessId, $normalizedBranchIds);
        $inventoryRows = $this->inventoryRows($businessId, $normalizedBranchIds);

        return [
            'generated_at' => now()->toDateTimeString(),
            'period' => [
                'token' => $window['token'],
                'label' => $window['label'],
                'start_date' => $window['start']->toDateString(),
                'end_date' => $window['end']->toDateString(),
            ],
            'sections' => [
                $this->section('gl_activity', 'Historical GL activity', $legacyActivity['movement'], $vasActivity['movement'], [
                    'legacy_transactions' => $legacyActivity['count'],
                    'vas_historical_vouchers' => $vasActivity['count'],
                ]),
                $this->section('treasury_balance', 'Treasury balance', $legacyTreasury, $vasTreasury, [
                    'legacy_accounts' => $this->legacyAccountCount($businessId),
                    'vas_cash_bank_entries' => $this->vasCashBankEntryCount($businessId, $window['end'], $normalizedBranchIds),
                ]),
                $this->section('receivables', 'Receivable outstanding', $legacyReceivables, $vasReceivables),
                $this->section('payables', 'Payable outstanding', $legacyPayables, $vasPayables),
                $this->section('inventory_value', 'Inventory value', $legacyInventory, $vasInventory, [
                    'vas_inventory_rows' => $inventoryRows->count(),
                ]),
            ],
            'branches' => $this->branchParityRows($businessId, $window, $normalizedBranchIds),
        ];
    }

    protected function resolveWindow(?string $period): array
    {
        if (empty($period)) {
            $current = now();

            return [
                'token' => $current->format('Y-m'),
                'label' => $current->format('F Y'),
                'start' => $current->copy()->startOfMonth(),
                'end' => $current->copy()->endOfMonth(),
            ];
        }

        try {
            $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        } catch (\Throwable) {
            $start = Carbon::parse($period)->startOfMonth();
        }

        return [
            'token' => $start->format('Y-m'),
            'label' => $start->format('F Y'),
            'start' => $start,
            'end' => $start->copy()->endOfMonth(),
        ];
    }

    protected function section(string $key, string $label, float $legacyValue, float $vasValue, array $meta = []): array
    {
        $legacy = round($legacyValue, 2);
        $vas = round($vasValue, 2);
        $delta = round($vas - $legacy, 2);

        return [
            'key' => $key,
            'label' => $label,
            'legacy_value' => $legacy,
            'vas_value' => $vas,
            'delta' => $delta,
            'status' => abs($delta) <= 0.01 ? 'aligned' : 'attention',
            'meta' => $meta,
        ];
    }

    protected function branchParityRows(int $businessId, array $window, array $branchIds = []): array
    {
        if (! Schema::hasTable('business_locations')) {
            return [];
        }

        $query = BusinessLocation::query()->where('business_id', $businessId);
        if (! empty($branchIds)) {
            $query->whereIn('id', $branchIds);
        }

        return $query->orderBy('name')->get()->map(function (BusinessLocation $branch) use ($businessId, $window) {
            $branchId = (int) $branch->id;
            $legacyTreasury = $this->legacyTreasuryBalance($businessId, $window['end'], [$branchId]);
            $vasTreasury = $this->vasTreasuryBalance($businessId, $window['end'], [$branchId]);
            $legacyReceivables = $this->legacyReceivablesOutstanding($businessId, $window['end'], $branchId);
            $vasReceivables = $this->vasSubledgerOutstanding($businessId, 'accounts_receivable', 'vas_receivable_allocations', 'invoice_voucher_id', 'debit - credit', $window['end'], $branchId);
            $legacyPayables = $this->legacyPayablesOutstanding($businessId, $window['end'], $branchId);
            $vasPayables = $this->vasSubledgerOutstanding($businessId, 'accounts_payable', 'vas_payable_allocations', 'bill_voucher_id', 'credit - debit', $window['end'], $branchId);
            $legacyInventory = $this->legacyInventoryValue($businessId, $window['end'], [$branchId]);
            $vasInventory = $this->vasInventoryValue($businessId, [$branchId]);

            return [
                'branch_id' => $branchId,
                'branch_name' => $branch->name,
                'treasury_delta' => round($vasTreasury - $legacyTreasury, 2),
                'receivables_delta' => round($vasReceivables - $legacyReceivables, 2),
                'payables_delta' => round($vasPayables - $legacyPayables, 2),
                'inventory_delta' => round($vasInventory - $legacyInventory, 2),
                'overall_status' => $this->branchStatus([
                    $vasTreasury - $legacyTreasury,
                    $vasReceivables - $legacyReceivables,
                    $vasPayables - $legacyPayables,
                    $vasInventory - $legacyInventory,
                ]),
            ];
        })->values()->all();
    }

    protected function branchStatus(array $deltas): string
    {
        foreach ($deltas as $delta) {
            if (abs((float) $delta) > 0.01) {
                return 'attention';
            }
        }

        return 'aligned';
    }

    protected function legacyAccountCount(int $businessId): int
    {
        if (! Schema::hasTable('accounts')) {
            return 0;
        }

        return (int) DB::table('accounts')
            ->where('business_id', $businessId)
            ->count();
    }

    protected function legacyTreasuryMovement(int $businessId, Carbon $startDate, Carbon $endDate, array $branchIds = []): array
    {
        if (! Schema::hasTable('account_transactions') || ! Schema::hasTable('accounts')) {
            return ['count' => 0, 'movement' => 0.0];
        }

        $query = DB::table('account_transactions as at')
            ->join('accounts as a', 'a.id', '=', 'at.account_id')
            ->leftJoin('transactions as t', 't.id', '=', 'at.transaction_id')
            ->where('a.business_id', $businessId)
            ->whereNull('at.deleted_at')
            ->whereDate('at.operation_date', '>=', $startDate->toDateString())
            ->whereDate('at.operation_date', '<=', $endDate->toDateString());

        if (! empty($branchIds)) {
            $query->whereIn('t.location_id', $branchIds);
        }

        return [
            'count' => (int) $query->count(),
            'movement' => (float) $query->selectRaw('COALESCE(SUM(ABS(at.amount)), 0) as movement')->value('movement'),
        ];
    }

    protected function vasHistoricalTreasuryMovement(int $businessId, Carbon $startDate, Carbon $endDate, array $branchIds = []): array
    {
        if (! Schema::hasTable('vas_vouchers') || ! Schema::hasTable('vas_journal_entries') || ! Schema::hasTable('vas_accounts')) {
            return ['count' => 0, 'movement' => 0.0];
        }

        $voucherQuery = DB::table('vas_vouchers')
            ->where('business_id', $businessId)
            ->where('is_historical_import', true)
            ->whereDate('posting_date', '>=', $startDate->toDateString())
            ->whereDate('posting_date', '<=', $endDate->toDateString());

        if (! empty($branchIds)) {
            $voucherQuery->whereIn('business_location_id', $branchIds);
        }

        $journalQuery = DB::table('vas_journal_entries as je')
            ->join('vas_vouchers as voucher', 'voucher.id', '=', 'je.voucher_id')
            ->join('vas_accounts as account', 'account.id', '=', 'je.account_id')
            ->where('voucher.business_id', $businessId)
            ->where('voucher.is_historical_import', true)
            ->whereDate('je.posting_date', '>=', $startDate->toDateString())
            ->whereDate('je.posting_date', '<=', $endDate->toDateString())
            ->where(function ($query) {
                $query->where('account.account_code', 'like', '111%')
                    ->orWhere('account.account_code', 'like', '112%');
            });

        if (! empty($branchIds)) {
            $journalQuery->whereIn('voucher.business_location_id', $branchIds);
        }

        return [
            'count' => (int) $voucherQuery->count(),
            'movement' => (float) $journalQuery->selectRaw('COALESCE(SUM(ABS(je.debit - je.credit)), 0) as movement')->value('movement'),
        ];
    }

    protected function legacyTreasuryBalance(int $businessId, Carbon $endDate, array $branchIds = []): float
    {
        if (! Schema::hasTable('account_transactions') || ! Schema::hasTable('accounts')) {
            return 0.0;
        }

        $query = DB::table('account_transactions as at')
            ->join('accounts as a', 'a.id', '=', 'at.account_id')
            ->leftJoin('transactions as t', 't.id', '=', 'at.transaction_id')
            ->where('a.business_id', $businessId)
            ->whereNull('at.deleted_at')
            ->whereDate('at.operation_date', '<=', $endDate->toDateString());

        if (! empty($branchIds)) {
            $query->whereIn('t.location_id', $branchIds);
        }

        return (float) $query
            ->selectRaw("COALESCE(SUM(IF(at.type = 'credit', at.amount, -1 * at.amount)), 0) as balance")
            ->value('balance');
    }

    protected function vasTreasuryBalance(int $businessId, Carbon $endDate, array $branchIds = []): float
    {
        if (! Schema::hasTable('vas_journal_entries') || ! Schema::hasTable('vas_accounts')) {
            return 0.0;
        }

        $query = DB::table('vas_journal_entries as je')
            ->join('vas_accounts as account', 'account.id', '=', 'je.account_id')
            ->join('vas_vouchers as voucher', 'voucher.id', '=', 'je.voucher_id')
            ->where('je.business_id', $businessId)
            ->whereDate('je.posting_date', '<=', $endDate->toDateString())
            ->where(function ($builder) {
                $builder->where('account.account_code', 'like', '111%')
                    ->orWhere('account.account_code', 'like', '112%');
            });

        if (! empty($branchIds)) {
            $query->whereIn('voucher.business_location_id', $branchIds);
        }

        return (float) $query
            ->selectRaw('COALESCE(SUM(je.debit - je.credit), 0) as balance')
            ->value('balance');
    }

    protected function vasCashBankEntryCount(int $businessId, Carbon $endDate, array $branchIds = []): int
    {
        if (! Schema::hasTable('vas_journal_entries') || ! Schema::hasTable('vas_accounts')) {
            return 0;
        }

        $query = DB::table('vas_journal_entries as je')
            ->join('vas_accounts as account', 'account.id', '=', 'je.account_id')
            ->join('vas_vouchers as voucher', 'voucher.id', '=', 'je.voucher_id')
            ->where('je.business_id', $businessId)
            ->whereDate('je.posting_date', '<=', $endDate->toDateString())
            ->where(function ($builder) {
                $builder->where('account.account_code', 'like', '111%')
                    ->orWhere('account.account_code', 'like', '112%');
            });

        if (! empty($branchIds)) {
            $query->whereIn('voucher.business_location_id', $branchIds);
        }

        return (int) $query->count();
    }

    protected function legacyReceivablesOutstanding(int $businessId, Carbon $endDate, ?int $branchId): float
    {
        if (! Schema::hasTable('transactions')) {
            return 0.0;
        }

        $totals = $this->transactionUtil->getSellTotals($businessId, null, $endDate->toDateString(), $branchId);

        return (float) ($totals['invoice_due'] ?? 0);
    }

    protected function legacyPayablesOutstanding(int $businessId, Carbon $endDate, ?int $branchId): float
    {
        if (! Schema::hasTable('transactions')) {
            return 0.0;
        }

        $totals = $this->transactionUtil->getPurchaseTotals($businessId, null, $endDate->toDateString(), $branchId);

        return (float) ($totals['purchase_due'] ?? 0);
    }

    protected function legacyInventoryValue(int $businessId, Carbon $endDate, array $branchIds = []): float
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasTable('purchase_lines')) {
            return 0.0;
        }

        $targets = ! empty($branchIds)
            ? $branchIds
            : (Schema::hasTable('business_locations')
                ? BusinessLocation::query()->where('business_id', $businessId)->pluck('id')->all()
                : []);

        if (empty($targets)) {
            return (float) $this->transactionUtil->getOpeningClosingStock($businessId, $endDate->toDateString(), null, false);
        }

        return collect($targets)->sum(function (int $branchId) use ($businessId, $endDate) {
            return (float) $this->transactionUtil->getOpeningClosingStock($businessId, $endDate->toDateString(), $branchId, false);
        });
    }

    protected function vasInventoryValue(int $businessId, array $branchIds = []): float
    {
        return (float) $this->inventoryRows($businessId, $branchIds)
            ->sum('inventory_value');
    }

    protected function inventoryRows(int $businessId, array $branchIds = []): Collection
    {
        if (! Schema::hasTable('variation_location_details') || ! Schema::hasTable('products') || ! Schema::hasTable('variations')) {
            return collect();
        }

        return $this->inventoryValuationService->summaries($businessId)
            ->when(! empty($branchIds), fn (Collection $rows) => $rows->whereIn('location_id', $branchIds));
    }

    protected function vasSubledgerOutstanding(
        int $businessId,
        string $postingMapKey,
        string $allocationTable,
        string $allocationGroupColumn,
        string $netFormula,
        Carbon $endDate,
        ?int $branchId
    ): float {
        if (! Schema::hasTable('vas_voucher_lines') || ! Schema::hasTable('vas_vouchers') || ! Schema::hasTable($allocationTable)) {
            return 0.0;
        }

        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $accountId = (int) data_get((array) $settings->posting_map, $postingMapKey, 0);
        if ($accountId <= 0) {
            return 0.0;
        }

        $lineTotals = DB::table('vas_voucher_lines as line')
            ->join('vas_vouchers as voucher', 'voucher.id', '=', 'line.voucher_id')
            ->where('line.business_id', $businessId)
            ->where('line.account_id', $accountId)
            ->where('voucher.status', 'posted')
            ->whereDate('voucher.posting_date', '<=', $endDate->toDateString())
            ->when($branchId, fn ($query) => $query->where('voucher.business_location_id', $branchId))
            ->select('voucher.id', DB::raw('SUM(' . $netFormula . ') as source_amount'))
            ->groupBy('voucher.id');

        $allocations = DB::table($allocationTable)
            ->where('business_id', $businessId)
            ->whereDate('allocation_date', '<=', $endDate->toDateString())
            ->select($allocationGroupColumn, DB::raw('SUM(amount) as allocated_amount'))
            ->groupBy($allocationGroupColumn);

        return (float) DB::table('vas_vouchers as voucher')
            ->joinSub($lineTotals, 'line_totals', function ($join) {
                $join->on('line_totals.id', '=', 'voucher.id');
            })
            ->leftJoinSub($allocations, 'allocation_totals', function ($join) use ($allocationGroupColumn) {
                $join->on('allocation_totals.' . $allocationGroupColumn, '=', 'voucher.id');
            })
            ->where('voucher.business_id', $businessId)
            ->selectRaw('COALESCE(SUM(GREATEST(line_totals.source_amount - COALESCE(allocation_totals.allocated_amount, 0), 0)), 0) as outstanding_amount')
            ->value('outstanding_amount');
    }
}
