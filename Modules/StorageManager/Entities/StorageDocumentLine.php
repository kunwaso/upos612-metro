<?php

namespace Modules\StorageManager\Entities;

use App\Product;
use App\Variation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageDocumentLine extends Model
{
    protected $table = 'storage_document_lines';

    protected $guarded = ['id'];

    protected $casts = [
        'expiry_date' => 'date',
        'expected_qty' => 'decimal:4',
        'executed_qty' => 'decimal:4',
        'variance_qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(StorageDocument::class, 'document_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class, 'variation_id');
    }

    public function fromArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'from_area_id');
    }

    public function toArea(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'to_area_id');
    }

    public function fromSlot(): BelongsTo
    {
        return $this->belongsTo(StorageSlot::class, 'from_slot_id');
    }

    public function toSlot(): BelongsTo
    {
        return $this->belongsTo(StorageSlot::class, 'to_slot_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(StorageTask::class, 'document_line_id');
    }
}
