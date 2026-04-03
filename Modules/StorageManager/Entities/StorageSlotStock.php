<?php

namespace Modules\StorageManager\Entities;

use App\Product;
use App\Variation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageSlotStock extends Model
{
    protected $table = 'storage_slot_stock';

    protected $guarded = ['id'];

    protected $casts = [
        'expiry_date' => 'date',
        'last_movement_at' => 'datetime',
        'qty_on_hand' => 'decimal:4',
        'qty_reserved' => 'decimal:4',
        'qty_inbound' => 'decimal:4',
        'qty_outbound' => 'decimal:4',
        'qty_count_pending' => 'decimal:4',
        'meta' => 'array',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'area_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(StorageSlot::class, 'slot_id');
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
