<?php

namespace Modules\StorageManager\Entities;

use App\Product;
use App\Variation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageReplenishmentRule extends Model
{
    protected $table = 'storage_replenishment_rules';

    protected $guarded = ['id'];

    protected $casts = [
        'min_qty' => 'decimal:4',
        'max_qty' => 'decimal:4',
        'replenish_qty' => 'decimal:4',
        'meta' => 'array',
    ];

    public function sourceArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'source_area_id');
    }

    public function destinationArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'destination_area_id');
    }

    public function sourceSlot(): BelongsTo
    {
        return $this->belongsTo(StorageSlot::class, 'source_slot_id');
    }

    public function destinationSlot(): BelongsTo
    {
        return $this->belongsTo(StorageSlot::class, 'destination_slot_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class, 'variation_id');
    }
}
