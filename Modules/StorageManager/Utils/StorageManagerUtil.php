<?php

namespace Modules\StorageManager\Utils;

use App\BusinessLocation;
use App\Category;
use App\ProductRack;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\StorageManager\Entities\StorageSlot;

class StorageManagerUtil
{
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
}
