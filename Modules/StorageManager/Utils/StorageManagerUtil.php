<?php

namespace Modules\StorageManager\Utils;

use App\Business;
use App\BusinessLocation;
use App\Category;
use App\ProductRack;
use App\PurchaseLine;
use App\VariationLocationDetails;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\StorageManager\Entities\StorageSlot;

class StorageManagerUtil
{
    public const DEFAULT_WIDGET_LIMIT = 10;
    public const DEFAULT_EXPIRY_ALERT_DAYS = 30;

    /**
     * Return all slots for a given warehouse location, grouped by category.
     * Each group carries the category model, the slots (with occupancy), and zone totals.
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @return array  [ ['category' => Category, 'slots' => Collection<StorageSlot+occupancy>, 'occupied' => int, 'capacity' => int], ... ]
     */
    public function getSlotsForLocation(int $business_id, int $location_id): array
    {
        $slots = StorageSlot::forBusiness($business_id)
            ->forLocation($location_id)
            ->with('category')
            ->withCount('productRacks as occupancy')
            ->orderBy('category_id')
            ->orderBy('row')
            ->orderBy('position')
            ->get();

        $zones = [];
        foreach ($slots->groupBy('category_id') as $categoryId => $categorySlots) {
            $category = $categorySlots->first()->category;
            $totalCapacity = $categorySlots->sum('max_capacity');
            $totalOccupied = $categorySlots->sum('occupancy');

            $categorySlots->each(function (StorageSlot $slot) {
                $slot->setAttribute('is_full', $slot->max_capacity > 0 && $slot->occupancy >= $slot->max_capacity);
            });

            $zones[] = [
                'category'  => $category,
                'slots'     => $categorySlots,
                'occupied'  => $totalOccupied,
                'capacity'  => $totalCapacity,
            ];
        }

        return $zones;
    }

    /**
     * Return the count of products (product_racks rows) assigned to a slot.
     */
    public function getSlotOccupancy(int $slot_id): int
    {
        return ProductRack::where('slot_id', $slot_id)->count();
    }

    /**
     * Assign a product to a storage slot.
     * Updates the product_rack row for the given product+location, setting slot_id.
     * Creates the product_rack row if it does not exist.
     *
     * @param  int  $business_id
     * @param  int  $product_id
     * @param  int  $slot_id
     * @return void
     */
    public function assignProductToSlot(int $business_id, int $product_id, int $slot_id): void
    {
        $slot = StorageSlot::forBusiness($business_id)->findOrFail($slot_id);

        ProductRack::updateOrCreate(
            [
                'business_id' => $business_id,
                'product_id'  => $product_id,
                'location_id' => $slot->location_id,
            ],
            [
                'slot_id' => $slot_id,
            ]
        );
    }

    /**
     * Remove slot assignment for a product at a given location.
     */
    public function unassignProductFromSlot(int $business_id, int $product_id, int $location_id): void
    {
        ProductRack::where('business_id', $business_id)
            ->where('product_id', $product_id)
            ->where('location_id', $location_id)
            ->update(['slot_id' => null]);
    }

    /**
     * Auto-generate a slot code from category short_code + row + position.
     * E.g. category short_code "A", row "1", position "2" → "A12"
     */
    public function generateSlotCode(Category $category, string $row, string $position): string
    {
        $prefix = strtoupper(trim($category->short_code ?? substr($category->name, 0, 1)));

        return $prefix . $row . $position;
    }

    /**
     * Get all locations for dropdown, scoped to business.
     *
     * @param  int  $business_id
     * @return \Illuminate\Support\Collection
     */
    public function getLocationsDropdown(int $business_id): Collection
    {
        return BusinessLocation::where('business_id', $business_id)
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Get product categories for zone dropdown, scoped to business.
     *
     * @param  int  $business_id
     * @return \Illuminate\Support\Collection
     */
    public function getCategoriesDropdown(int $business_id): Collection
    {
        return Category::where('business_id', $business_id)
            ->where('category_type', 'product')
            ->where('parent_id', 0)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Return available (not-full) slots for a given location as a keyed dropdown array.
     * Legacy format: {id => label}. Kept for backward compatibility.
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @return array
     */
    public function getAvailableSlotsDropdown(int $business_id, int $location_id): array
    {
        $dropdown = [];
        foreach ($this->getAvailableSlotsWithDetails($business_id, $location_id) as $item) {
            $dropdown[$item['id']] = $item['text'];
        }

        return $dropdown;
    }

    /**
     * Return available (not-full) slots for a given location as a structured array.
     * Each item includes 'id', 'text' (for select2), 'rack', 'row', and 'position'
     * so the product form can auto-fill the rack/row/position inputs on slot selection.
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @return array<int, array{id: int, text: string, rack: string, row: string, position: string}>
     */
    public function getAvailableSlotsWithDetails(int $business_id, int $location_id): array
    {
        $slots = StorageSlot::forBusiness($business_id)
            ->forLocation($location_id)
            ->with('category')
            ->withCount('productRacks as occupancy')
            ->orderBy('category_id')
            ->orderBy('row')
            ->orderBy('position')
            ->get();

        $results = [];
        foreach ($slots as $slot) {
            $isFull = $slot->max_capacity > 0 && $slot->occupancy >= $slot->max_capacity;
            if ($isFull) {
                continue;
            }
            $zoneName  = optional($slot->category)->name ?? '—';
            $slotCode  = $slot->slot_code ?: "{$slot->row}-{$slot->position}";
            $results[] = [
                'id'       => $slot->id,
                'text'     => "{$slotCode} — {$zoneName}",
                'rack'     => $slotCode,
                'row'      => (string) ($slot->row ?? ''),
                'position' => (string) ($slot->position ?? ''),
            ];
        }

        return $results;
    }

    /**
     * Resolve business-level expiry alert window (days).
     */
    public function resolveExpiryAlertDays(int $business_id): int
    {
        $days = (int) Business::where('id', $business_id)->value('stock_expiry_alert_days');

        return $days > 0 ? $days : self::DEFAULT_EXPIRY_ALERT_DAYS;
    }

    /**
     * Widget data provider: products that are running out of stock.
     *
     * Contract per row:
     * - product_id, product_label, meta_line, storage_label, status, days_left, link_url
     */
    public function getRunningOutStockItems(
        int $business_id,
        int $location_id,
        $permitted_locations = null,
        ?int $limit = self::DEFAULT_WIDGET_LIMIT,
        ?int $product_id = null
    ): Collection {
        $query = VariationLocationDetails::join(
            'product_variations as pv',
            'variation_location_details.product_variation_id',
            '=',
            'pv.id'
        )
            ->join(
                'variations as v',
                'variation_location_details.variation_id',
                '=',
                'v.id'
            )
            ->join(
                'products as p',
                'variation_location_details.product_id',
                '=',
                'p.id'
            )
            ->leftJoin(
                'business_locations as l',
                'variation_location_details.location_id',
                '=',
                'l.id'
            )
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('p.business_id', $business_id)
            ->where('p.enable_stock', 1)
            ->where('p.is_inactive', 0)
            ->whereNull('v.deleted_at')
            ->whereNotNull('p.alert_quantity')
            ->whereRaw('variation_location_details.qty_available <= p.alert_quantity');

        if ($location_id > 0) {
            $query->where('variation_location_details.location_id', $location_id);
        }

        $allowedLocations = $this->normalizePermittedLocations($permitted_locations);
        if (! empty($allowedLocations)) {
            $query->whereIn('variation_location_details.location_id', $allowedLocations);
        }

        if (! empty($product_id)) {
            $query->where('p.id', $product_id);
        }

        $query->select(
            'p.id as product_id',
            'variation_location_details.location_id as location_id',
            'p.name as product',
            'p.type',
            'p.sku',
            'pv.name as product_variation',
            'v.name as variation',
            'v.sub_sku',
            'variation_location_details.qty_available as stock',
            'u.short_name as unit'
        )
            ->groupBy('variation_location_details.id')
            ->orderBy('stock', 'asc');

        if (! is_null($limit)) {
            $query->limit($limit);
        }

        $rows = $query->get();
        $slotLabels = $this->getProductSlotLabels(
            $business_id,
            $rows->pluck('product_id')->map(fn ($value) => (int) $value)->unique()->values()->all(),
            $rows->pluck('location_id')->map(fn ($value) => (int) $value)->unique()->values()->all()
        );

        return $rows->map(function ($row) use ($slotLabels) {
            $locationId = (int) $row->location_id;
            $productId = (int) $row->product_id;
            $slotMapKey = $this->slotMapKey($locationId, $productId);
            $stockValue = $this->formatQuantity((float) $row->stock);
            $unit = trim((string) ($row->unit ?? ''));

            return [
                'product_id'    => $productId,
                'product_label' => $this->buildProductLabel(
                    (string) $row->type,
                    (string) $row->product,
                    $row->sku,
                    $row->product_variation,
                    $row->variation,
                    $row->sub_sku
                ),
                'meta_line'     => trim(__('lang_v1.stock') . ': ' . $stockValue . ' ' . $unit),
                'storage_label' => $slotLabels[$slotMapKey] ?? '—',
                'status'        => 'running_out',
                'days_left'     => null,
                'link_url'      => null,
            ];
        })->values();
    }

    /**
     * Widget data provider: products already expired or expiring soon.
     *
     * Contract per row:
     * - product_id, product_label, meta_line, storage_label, status, days_left, link_url
     */
    public function getExpiringProductsItems(
        int $business_id,
        int $location_id,
        int $daysWindow,
        $permitted_locations = null,
        ?int $limit = self::DEFAULT_WIDGET_LIMIT
    ): Collection {
        $daysWindow = $daysWindow > 0 ? $daysWindow : self::DEFAULT_EXPIRY_ALERT_DAYS;
        $expDateFilter = Carbon::today()->addDays($daysWindow)->toDateString();

        $query = PurchaseLine::leftJoin(
            'transactions as t',
            'purchase_lines.transaction_id',
            '=',
            't.id'
        )
            ->leftJoin(
                'products as p',
                'purchase_lines.product_id',
                '=',
                'p.id'
            )
            ->leftJoin(
                'variations as v',
                'purchase_lines.variation_id',
                '=',
                'v.id'
            )
            ->leftJoin(
                'product_variations as pv',
                'v.product_variation_id',
                '=',
                'pv.id'
            )
            ->leftJoin('business_locations as l', 't.location_id', '=', 'l.id')
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('t.business_id', $business_id)
            ->where('p.enable_stock', 1)
            ->whereNotNull('purchase_lines.exp_date')
            ->whereDate('purchase_lines.exp_date', '<=', $expDateFilter);

        if ($location_id > 0) {
            $query->where('t.location_id', $location_id);
        }

        $allowedLocations = $this->normalizePermittedLocations($permitted_locations);
        if (! empty($allowedLocations)) {
            $query->whereIn('t.location_id', $allowedLocations);
        }

        $query->select(
            'p.id as product_id',
            't.location_id as location_id',
            'p.name as product',
            'p.sku',
            'p.type as product_type',
            'v.name as variation',
            'v.sub_sku',
            'pv.name as product_variation',
            'purchase_lines.exp_date as exp_date',
            DB::raw('SUM(COALESCE(quantity, 0) - COALESCE(quantity_sold, 0) - COALESCE(quantity_adjusted, 0) - COALESCE(quantity_returned, 0)) as stock_left')
        )
            ->having('stock_left', '>', 0)
            ->groupBy('purchase_lines.variation_id')
            ->groupBy('purchase_lines.exp_date')
            ->groupBy('purchase_lines.lot_number')
            ->orderBy('purchase_lines.exp_date', 'asc');

        if (! is_null($limit)) {
            $query->limit($limit);
        }

        $rows = $query->get();
        $slotLabels = $this->getProductSlotLabels(
            $business_id,
            $rows->pluck('product_id')->map(fn ($value) => (int) $value)->unique()->values()->all(),
            $rows->pluck('location_id')->map(fn ($value) => (int) $value)->unique()->values()->all()
        );

        return $rows->map(function ($row) use ($slotLabels) {
            $locationId = (int) $row->location_id;
            $productId = (int) $row->product_id;
            $slotMapKey = $this->slotMapKey($locationId, $productId);
            $daysLeft = Carbon::today()->diffInDays(Carbon::parse($row->exp_date), false);

            return [
                'product_id'    => $productId,
                'product_label' => $this->buildProductLabel(
                    (string) $row->product_type,
                    (string) $row->product,
                    $row->sku,
                    $row->product_variation,
                    $row->variation,
                    $row->sub_sku
                ),
                'meta_line'     => $daysLeft < 0
                    ? __('lang_v1.expired_days_ago', ['days' => abs($daysLeft)])
                    : __('lang_v1.expiring_in_days', ['days' => $daysLeft]),
                'storage_label' => $slotLabels[$slotMapKey] ?? '—',
                'status'        => $daysLeft < 0 ? 'expired' : 'expiring',
                'days_left'     => $daysLeft,
                'link_url'      => null,
            ];
        })->values();
    }

    /**
     * Return products assigned to a storage slot with variation-level stock
     * at the slot's location.
     *
     * @return array{
     *   items: array<int, array{product_id:int, product_label:string, type:string, variations:array}>,
     *   total:int,
     *   truncated:bool
     * }
     */
    public function getSlotAssignedProducts(int $business_id, int $slot_id, int $limit = 10): array
    {
        $slot = StorageSlot::forBusiness($business_id)->select('id', 'location_id')->find($slot_id);
        if (! $slot) {
            return [
                'items' => [],
                'total' => 0,
                'truncated' => false,
            ];
        }

        $limit = max(1, $limit);

        $productIds = ProductRack::query()
            ->where('business_id', $business_id)
            ->where('slot_id', $slot_id)
            ->pluck('product_id')
            ->unique()
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        if (empty($productIds)) {
            return ['items' => [], 'total' => 0, 'truncated' => false];
        }

        $total = count($productIds);
        $limitedIds = array_slice($productIds, 0, $limit);

        $rows = DB::table('products as p')
            ->join('variations as v', 'v.product_id', '=', 'p.id')
            ->leftJoin('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->leftJoin('variation_location_details as vld', function ($join) use ($slot) {
                $join->on('vld.variation_id', '=', 'v.id')
                    ->where('vld.location_id', '=', $slot->location_id);
            })
            ->leftJoin('units as u', 'p.unit_id', '=', 'u.id')
            ->where('p.business_id', $business_id)
            ->whereIn('p.id', $limitedIds)
            ->whereNull('v.deleted_at')
            ->select(
                'p.id as product_id',
                'p.name as product',
                'p.type',
                'p.sku',
                'u.short_name as unit',
                'pv.name as product_variation',
                'v.name as variation',
                'v.sub_sku',
                DB::raw('COALESCE(vld.qty_available, 0) as qty_available')
            )
            ->orderBy('p.name')
            ->orderBy('pv.name')
            ->orderBy('v.name')
            ->get();

        $items = [];
        foreach ($rows->groupBy('product_id') as $productId => $variationRows) {
            $first = $variationRows->first();
            $type = (string) ($first->type ?? 'single');

            $variations = $variationRows->map(function ($row) use ($type) {
                $label = null;
                if ($type !== 'single') {
                    $segments = array_values(array_filter(
                        [$row->product_variation, $row->variation],
                        fn ($v) => ! empty($v)
                    ));
                    $label = implode(' - ', $segments);
                    if (! empty($row->sub_sku)) {
                        $label .= " ({$row->sub_sku})";
                    }
                }

                return [
                    'label' => $label,
                    'qty'   => $this->formatQuantity((float) $row->qty_available),
                    'unit'  => trim((string) ($row->unit ?? '')),
                ];
            })->values()->all();

            $items[] = [
                'product_id'    => (int) $productId,
                'product_label' => $this->buildProductLabel(
                    $type,
                    (string) $first->product,
                    $first->sku,
                    null,
                    null,
                    null
                ),
                'type'       => $type,
                'variations' => $variations,
            ];
        }

        return [
            'items'     => $items,
            'total'     => $total,
            'truncated' => $total > count($items),
        ];
    }

    /**
     * Normalize permitted locations into a location-id array.
     */
    protected function normalizePermittedLocations($permitted_locations): array
    {
        if (empty($permitted_locations) || $permitted_locations === 'all') {
            return [];
        }

        if (! is_array($permitted_locations)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $permitted_locations)));
    }

    /**
     * Build user-facing product label.
     */
    protected function buildProductLabel(
        string $type,
        string $product,
        ?string $sku,
        ?string $productVariation,
        ?string $variation,
        ?string $subSku
    ): string {
        if ($type === 'single') {
            return ! empty($sku) ? "{$product} ({$sku})" : $product;
        }

        $segments = array_values(array_filter([$product, $productVariation, $variation], fn ($value) => ! empty($value)));
        $label = implode(' - ', $segments);

        return ! empty($subSku) ? "{$label} ({$subSku})" : $label;
    }

    /**
     * Load storage labels keyed by "location_id:product_id".
     */
    protected function getProductSlotLabels(int $business_id, array $productIds, array $locationIds): array
    {
        if (empty($productIds) || empty($locationIds)) {
            return [];
        }

        $racks = ProductRack::query()
            ->leftJoin('storage_slots as ss', function ($join) use ($business_id) {
                $join->on('ss.id', '=', 'product_racks.slot_id')
                    ->where('ss.business_id', '=', $business_id);
            })
            ->where('product_racks.business_id', $business_id)
            ->whereIn('product_racks.product_id', $productIds)
            ->whereIn('product_racks.location_id', $locationIds)
            ->select(
                'product_racks.product_id',
                'product_racks.location_id',
                'product_racks.rack',
                'product_racks.row',
                'product_racks.position',
                'ss.slot_code'
            )
            ->orderBy('product_racks.id')
            ->get();

        $labels = [];
        foreach ($racks as $rack) {
            $key = $this->slotMapKey((int) $rack->location_id, (int) $rack->product_id);
            if (isset($labels[$key])) {
                continue;
            }

            $labels[$key] = $this->buildStorageLabel(
                $rack->slot_code,
                $rack->rack,
                $rack->row,
                $rack->position
            );
        }

        return $labels;
    }

    protected function slotMapKey(int $locationId, int $productId): string
    {
        return $locationId . ':' . $productId;
    }

    protected function buildStorageLabel(?string $slotCode, ?string $rack, ?string $row, ?string $position): string
    {
        $slotCode = trim((string) $slotCode);
        if ($slotCode !== '') {
            return $slotCode;
        }

        $parts = array_values(array_filter([$rack, $row, $position], fn ($value) => trim((string) $value) !== ''));

        return ! empty($parts) ? implode(' / ', $parts) : '—';
    }

    protected function formatQuantity(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
