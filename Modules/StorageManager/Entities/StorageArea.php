<?php

namespace Modules\StorageManager\Entities;

use App\BusinessLocation;
use App\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageArea extends Model
{
    protected $table = 'storage_areas';

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(StorageSlot::class, 'area_id');
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }
}
