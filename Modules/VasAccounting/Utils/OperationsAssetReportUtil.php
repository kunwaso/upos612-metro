<?php

namespace Modules\VasAccounting\Utils;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasFixedAsset;
use Modules\VasAccounting\Entities\VasTool;
use Modules\VasAccounting\Entities\VasToolAmortization;
use Modules\VasAccounting\Entities\VasWarehouse;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Services\VasToolAmortizationService;

class OperationsAssetReportUtil
{
    public function __construct(
        protected VasInventoryValuationService $inventoryValuationService,
        protected VasToolAmortizationService $toolAmortizationService
    ) {
    }

    public function warehouseSummary(int $businessId): array
    {
        $warehouses = Schema::hasTable('vas_warehouses')
            ? VasWarehouse::query()->where('business_id', $businessId)->get()
            : collect();
        $valuations = $this->inventoryValuationService->summaries($businessId);
        $stockLocations = $valuations->pluck('location_id')->filter()->unique();

        return [
            'warehouse_count' => $warehouses->count(),
            'active_warehouses' => $warehouses->where('status', 'active')->count(),
            'stock_locations' => $stockLocations->count(),
            'uncovered_locations' => $stockLocations->diff($warehouses->pluck('business_location_id')->filter()->unique())->count(),
        ];
    }

    public function inventoryMovementRows(int $businessId, int $limit = 60): Collection
    {
        $query = $this->inventoryMovementQuery($businessId);
        if (! $query) {
            return collect();
        }

        return DB::query()
            ->fromSub($query, 'inventory_movements')
            ->orderByDesc('transaction_date')
            ->orderByDesc('transaction_id')
            ->limit($limit)
            ->get();
    }

    public function warehouseReconciliationRows(int $businessId, int $limit = 30): Collection
    {
        $valuations = $this->inventoryValuationService->summaries($businessId);
        $warehouses = Schema::hasTable('vas_warehouses')
            ? VasWarehouse::query()->with('businessLocation')->where('business_id', $businessId)->orderBy('code')->get()
            : collect();
        $movementDatesByLocation = $this->inventoryMovementRows($businessId, 500)
            ->groupBy('location_id')
            ->map(fn (Collection $rows) => $rows->max('transaction_date'));

        $stockRows = $valuations
            ->groupBy('location_id')
            ->map(function (Collection $rows, $locationId) use ($warehouses, $movementDatesByLocation) {
                $warehouse = $warehouses->firstWhere('business_location_id', (int) $locationId);
                $firstRow = $rows->first();

                return [
                    'location_id' => (int) $locationId,
                    'location_name' => $firstRow['location_name'] ?: ('Location #' . $locationId),
                    'warehouse_code' => $warehouse?->code,
                    'warehouse_name' => $warehouse?->name,
                    'sku_count' => $rows->count(),
                    'qty_available' => round((float) $rows->sum('qty_available'), 4),
                    'inventory_value' => round((float) $rows->sum('inventory_value'), 4),
                    'last_movement_at' => $movementDatesByLocation->get((int) $locationId),
                    'coverage_status' => $warehouse ? 'aligned' : 'missing_master',
                ];
            })
            ->values();

        $emptyWarehouseRows = $warehouses
            ->filter(fn ($warehouse) => ! $stockRows->contains('location_id', (int) $warehouse->business_location_id))
            ->map(function ($warehouse) {
                return [
                    'location_id' => (int) $warehouse->business_location_id,
                    'location_name' => optional($warehouse->businessLocation)->name ?: 'Unmapped branch',
                    'warehouse_code' => $warehouse->code,
                    'warehouse_name' => $warehouse->name,
                    'sku_count' => 0,
                    'qty_available' => 0.0,
                    'inventory_value' => 0.0,
                    'last_movement_at' => null,
                    'coverage_status' => 'no_stock_activity',
                ];
            });

        return $stockRows
            ->concat($emptyWarehouseRows)
            ->sortBy([
                ['coverage_status', 'asc'],
                ['location_name', 'asc'],
            ])
            ->values()
            ->take($limit);
    }

    public function toolSummary(int $businessId): array
    {
        $tools = Schema::hasTable('vas_tools')
            ? VasTool::query()->where('business_id', $businessId)->get()
            : collect();
        $scheduleRows = $this->toolScheduleRows($businessId, 500);

        return [
            'tool_count' => $tools->count(),
            'active_tools' => $tools->whereIn('status', ['active', 'issued'])->count(),
            'remaining_value' => round((float) $tools->sum('remaining_value'), 4),
            'due_this_month' => $scheduleRows->filter(function ($row) {
                if (empty($row['next_run_date']) || $row['due_status'] === 'completed') {
                    return false;
                }

                return Carbon::parse($row['next_run_date'])->isSameMonth(Carbon::today());
            })->count(),
        ];
    }

    public function toolRegisterRows(int $businessId): Collection
    {
        if (! Schema::hasTable('vas_tools')) {
            return collect();
        }

        $stats = Schema::hasTable('vas_tool_amortizations')
            ? VasToolAmortization::query()
                ->where('business_id', $businessId)
                ->where('status', 'posted')
                ->selectRaw('tool_id, COUNT(*) as periods_posted, SUM(amount) as amortized_amount, MAX(amortization_date) as last_amortized_at')
                ->groupBy('tool_id')
                ->get()
                ->keyBy('tool_id')
            : collect();

        return VasTool::query()
            ->with(['businessLocation', 'expenseAccount', 'assetAccount', 'department', 'costCenter', 'project'])
            ->where('business_id', $businessId)
            ->orderBy('tool_code')
            ->get()
            ->map(function (VasTool $tool) use ($stats) {
                $stat = $stats->get($tool->id);

                return [
                    'tool' => $tool,
                    'monthly_amount' => $this->toolAmortizationService->scheduledAmountForTool($tool),
                    'periods_posted' => (int) ($stat->periods_posted ?? 0),
                    'amortized_amount' => round((float) ($stat->amortized_amount ?? 0), 4),
                    'last_amortized_at' => $stat->last_amortized_at ?? null,
                ];
            });
    }

    public function toolScheduleRows(int $businessId, int $limit = 20): Collection
    {
        return $this->toolRegisterRows($businessId)
            ->map(function (array $row) {
                $tool = $row['tool'];
                $periodsPosted = (int) $row['periods_posted'];
                $isCompleted = (float) $tool->remaining_value <= 0 || $periodsPosted >= max(1, (int) $tool->amortization_months);
                $nextRunDate = null;

                if (! empty($tool->start_amortization_at)) {
                    $nextRunDate = Carbon::parse($tool->start_amortization_at)
                        ->startOfMonth()
                        ->addMonths($periodsPosted)
                        ->endOfMonth()
                        ->toDateString();
                }

                return [
                    'tool' => $tool,
                    'next_run_date' => $nextRunDate,
                    'next_amount' => $isCompleted ? 0.0 : min($row['monthly_amount'], (float) $tool->remaining_value),
                    'periods_posted' => $periodsPosted,
                    'due_status' => $isCompleted ? 'completed' : $this->dueStatus($nextRunDate),
                ];
            })
            ->sortBy([
                ['due_status', 'asc'],
                ['next_run_date', 'asc'],
            ])
            ->values()
            ->take($limit);
    }

    public function toolAmortizationHistory(int $businessId, int $limit = 20): Collection
    {
        if (! Schema::hasTable('vas_tool_amortizations')) {
            return collect();
        }

        return VasToolAmortization::query()
            ->with(['tool', 'voucher'])
            ->where('business_id', $businessId)
            ->latest('amortization_date')
            ->latest('id')
            ->take($limit)
            ->get();
    }

    public function fixedAssetRegisterRows(int $businessId): Collection
    {
        if (! Schema::hasTable('vas_fixed_assets')) {
            return collect();
        }

        $stats = DB::table('vas_asset_depreciations')
            ->where('business_id', $businessId)
            ->where('status', 'posted')
            ->selectRaw('fixed_asset_id, SUM(amount) as accumulated_depreciation, MAX(depreciation_date) as last_depreciated_at')
            ->groupBy('fixed_asset_id')
            ->get()
            ->keyBy('fixed_asset_id');

        return VasFixedAsset::query()
            ->with(['category', 'businessLocation'])
            ->where('business_id', $businessId)
            ->orderBy('asset_code')
            ->get()
            ->map(function (VasFixedAsset $asset) use ($stats) {
                $stat = $stats->get($asset->id);
                $accumulated = min((float) ($stat->accumulated_depreciation ?? 0), (float) $asset->original_cost);

                return [
                    'asset' => $asset,
                    'accumulated_depreciation' => round($accumulated, 4),
                    'net_book_value' => round(max(0, (float) $asset->original_cost - $accumulated), 4),
                    'last_depreciated_at' => $stat->last_depreciated_at ?? null,
                ];
            });
    }

    public function fixedAssetSummary(int $businessId): array
    {
        $rows = $this->fixedAssetRegisterRows($businessId);

        return [
            'asset_count' => $rows->count(),
            'active_assets' => $rows->filter(fn ($row) => $row['asset']->status === 'active')->count(),
            'disposed_assets' => $rows->filter(fn ($row) => $row['asset']->status === 'disposed')->count(),
            'net_book_value' => round((float) $rows->sum('net_book_value'), 4),
        ];
    }

    protected function inventoryMovementQuery(int $businessId)
    {
        if (! Schema::hasTable('transactions')) {
            return null;
        }

        $purchaseReceipts = DB::table('purchase_lines as line')
            ->join('transactions as t', 't.id', '=', 'line.transaction_id')
            ->join('products as p', 'p.id', '=', 'line.product_id')
            ->join('variations as v', 'v.id', '=', 'line.variation_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('t.business_id', $businessId)
            ->whereIn('t.type', ['purchase', 'opening_stock', 'purchase_transfer'])
            ->selectRaw(
                "'receipt' as movement_type, t.id as transaction_id, t.transaction_date,
                COALESCE(NULLIF(t.ref_no, ''), NULLIF(t.invoice_no, ''), CONCAT('TXN-', t.id)) as reference,
                t.location_id, COALESCE(bl.name, CONCAT('Location #', t.location_id)) as location_name,
                line.product_id, p.name as product_name, line.variation_id,
                COALESCE(v.sub_sku, CONCAT('VAR-', v.id)) as sku, 'in' as direction,
                line.quantity as quantity, line.purchase_price as unit_amount,
                (line.quantity * line.purchase_price) as movement_value"
            );

        $purchaseReturns = DB::table('purchase_lines as line')
            ->join('transactions as t', 't.id', '=', 'line.transaction_id')
            ->join('products as p', 'p.id', '=', 'line.product_id')
            ->join('variations as v', 'v.id', '=', 'line.variation_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'purchase_return')
            ->selectRaw(
                "'purchase_return' as movement_type, t.id as transaction_id, t.transaction_date,
                COALESCE(NULLIF(t.ref_no, ''), NULLIF(t.invoice_no, ''), CONCAT('TXN-', t.id)) as reference,
                t.location_id, COALESCE(bl.name, CONCAT('Location #', t.location_id)) as location_name,
                line.product_id, p.name as product_name, line.variation_id,
                COALESCE(v.sub_sku, CONCAT('VAR-', v.id)) as sku, 'out' as direction,
                (line.quantity * -1) as quantity, line.purchase_price as unit_amount,
                ((line.quantity * line.purchase_price) * -1) as movement_value"
            );

        $salesIssues = DB::table('transaction_sell_lines as line')
            ->join('transactions as t', 't.id', '=', 'line.transaction_id')
            ->join('products as p', 'p.id', '=', 'line.product_id')
            ->join('variations as v', 'v.id', '=', 'line.variation_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('t.business_id', $businessId)
            ->whereIn('t.type', ['sell', 'sell_transfer'])
            ->selectRaw(
                "'issue' as movement_type, t.id as transaction_id, t.transaction_date,
                COALESCE(NULLIF(t.ref_no, ''), NULLIF(t.invoice_no, ''), CONCAT('TXN-', t.id)) as reference,
                t.location_id, COALESCE(bl.name, CONCAT('Location #', t.location_id)) as location_name,
                line.product_id, p.name as product_name, line.variation_id,
                COALESCE(v.sub_sku, CONCAT('VAR-', v.id)) as sku, 'out' as direction,
                (line.quantity * -1) as quantity, COALESCE(line.unit_price, 0) as unit_amount,
                ((line.quantity * COALESCE(line.unit_price, 0)) * -1) as movement_value"
            );

        $salesReturns = DB::table('transaction_sell_lines as line')
            ->join('transactions as t', 't.id', '=', 'line.transaction_id')
            ->join('products as p', 'p.id', '=', 'line.product_id')
            ->join('variations as v', 'v.id', '=', 'line.variation_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell_return')
            ->selectRaw(
                "'sell_return' as movement_type, t.id as transaction_id, t.transaction_date,
                COALESCE(NULLIF(t.ref_no, ''), NULLIF(t.invoice_no, ''), CONCAT('TXN-', t.id)) as reference,
                t.location_id, COALESCE(bl.name, CONCAT('Location #', t.location_id)) as location_name,
                line.product_id, p.name as product_name, line.variation_id,
                COALESCE(v.sub_sku, CONCAT('VAR-', v.id)) as sku, 'in' as direction,
                line.quantity as quantity, COALESCE(line.unit_price, 0) as unit_amount,
                (line.quantity * COALESCE(line.unit_price, 0)) as movement_value"
            );

        $stockAdjustments = DB::table('stock_adjustment_lines as line')
            ->join('transactions as t', 't.id', '=', 'line.transaction_id')
            ->join('products as p', 'p.id', '=', 'line.product_id')
            ->join('variations as v', 'v.id', '=', 'line.variation_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'stock_adjustment')
            ->selectRaw(
                "'stock_adjustment' as movement_type, t.id as transaction_id, t.transaction_date,
                COALESCE(NULLIF(t.ref_no, ''), NULLIF(t.invoice_no, ''), CONCAT('TXN-', t.id)) as reference,
                t.location_id, COALESCE(bl.name, CONCAT('Location #', t.location_id)) as location_name,
                line.product_id, p.name as product_name, line.variation_id,
                COALESCE(v.sub_sku, CONCAT('VAR-', v.id)) as sku, 'out' as direction,
                (line.quantity * -1) as quantity, COALESCE(line.unit_price, 0) as unit_amount,
                ((line.quantity * COALESCE(line.unit_price, 0)) * -1) as movement_value"
            );

        return $purchaseReceipts
            ->unionAll($purchaseReturns)
            ->unionAll($salesIssues)
            ->unionAll($salesReturns)
            ->unionAll($stockAdjustments);
    }

    protected function dueStatus(?string $nextRunDate): string
    {
        if (empty($nextRunDate)) {
            return 'setup_needed';
        }

        $nextRun = Carbon::parse($nextRunDate);
        $today = Carbon::today();

        if ($nextRun->lt($today)) {
            return 'overdue';
        }

        if ($nextRun->isSameMonth($today)) {
            return 'due_now';
        }

        return 'scheduled';
    }
}
