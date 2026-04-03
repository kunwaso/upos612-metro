<?php

namespace Modules\StorageManager\Entities;

use App\Product;
use App\Variation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageInventoryMovement extends Model
{
    protected $table = 'storage_inventory_movements';

    protected $guarded = ['id'];

    protected $casts = [
        'expiry_date' => 'date',
        'moved_at' => 'datetime',
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(StorageDocument::class, 'document_id');
    }

    public function documentLine(): BelongsTo
    {
        return $this->belongsTo(StorageDocumentLine::class, 'document_line_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(StorageTask::class, 'task_id');
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
