<?php

namespace Modules\StorageManager\Entities;

use App\Product;
use App\Variation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageCountLine extends Model
{
    protected $table = 'storage_count_lines';

    protected $guarded = ['id'];

    protected $casts = [
        'expiry_date' => 'date',
        'system_qty' => 'decimal:4',
        'counted_qty' => 'decimal:4',
        'variance_qty' => 'decimal:4',
        'meta' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(StorageCountSession::class, 'count_session_id');
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
