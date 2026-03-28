<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VasInventoryValuationService
{
    public function summaries(int $businessId): Collection
    {
        $purchaseCosts = DB::table('purchase_lines as pl')
            ->join('transactions as t', 't.id', '=', 'pl.transaction_id')
            ->where('t.business_id', $businessId)
            ->whereIn('t.type', ['purchase', 'opening_stock', 'stock_transfer'])
            ->groupBy('pl.variation_id')
            ->selectRaw('pl.variation_id, SUM(pl.quantity) as purchased_qty, SUM(pl.quantity * pl.purchase_price) as purchased_cost')
            ->get()
            ->keyBy('variation_id');

        return DB::table('variation_location_details as vld')
            ->join('variations as v', 'v.id', '=', 'vld.variation_id')
            ->join('products as p', 'p.id', '=', 'vld.product_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'vld.location_id')
            ->where('p.business_id', $businessId)
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'v.id as variation_id',
                'v.sub_sku',
                'vld.location_id',
                'bl.name as location_name',
                'vld.qty_available'
            )
            ->get()
            ->map(function ($row) use ($purchaseCosts) {
                $costRow = $purchaseCosts->get($row->variation_id);
                $purchasedQty = (float) ($costRow->purchased_qty ?? 0);
                $purchasedCost = (float) ($costRow->purchased_cost ?? 0);
                $averageCost = $purchasedQty > 0 ? round($purchasedCost / $purchasedQty, 4) : 0.0;

                return [
                    'product_id' => (int) $row->product_id,
                    'product_name' => $row->product_name,
                    'variation_id' => (int) $row->variation_id,
                    'sku' => $row->sub_sku,
                    'location_id' => (int) $row->location_id,
                    'location_name' => $row->location_name,
                    'qty_available' => round((float) $row->qty_available, 4),
                    'average_cost' => $averageCost,
                    'inventory_value' => round((float) $row->qty_available * $averageCost, 4),
                ];
            });
    }

    public function totals(int $businessId): array
    {
        $rows = $this->summaries($businessId);

        return [
            'sku_count' => $rows->count(),
            'quantity_on_hand' => round((float) $rows->sum('qty_available'), 4),
            'inventory_value' => round((float) $rows->sum('inventory_value'), 4),
        ];
    }
}
