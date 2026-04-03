<?php

namespace Modules\StorageManager\Entities;

use App\BusinessLocation;
use App\Category;
use App\ProductRack;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class StorageSlot extends Model
{
    protected $table = 'storage_slots';

    protected $guarded = ['id'];

    protected $casts = [
        'allows_mixed_sku' => 'boolean',
        'allows_mixed_lot' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * The location (warehouse) this slot belongs to.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    /**
     * The category (zone/section) this slot belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'area_id');
    }

    /**
     * Products assigned to this slot via product_racks.
     */
    public function productRacks(): HasMany
    {
        return $this->hasMany(ProductRack::class, 'slot_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(StorageSlotStock::class, 'slot_id');
    }

    /**
     * Count products currently occupying this slot.
     */
    public function getOccupancyAttribute(): int
    {
        return $this->productRacks()->count();
    }

    /**
     * Whether this slot is full (occupancy >= max_capacity > 0).
     */
    public function getIsFullAttribute(): bool
    {
        if ($this->max_capacity <= 0) {
            return false;
        }

        return $this->productRacks()->count() >= $this->max_capacity;
    }

    /**
     * Scope: filter by business.
     */
    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('business_id', $business_id);
    }

    /**
     * Scope: filter by location.
     */
    public function scopeForLocation($query, int $location_id)
    {
        return $query->where('location_id', $location_id);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
