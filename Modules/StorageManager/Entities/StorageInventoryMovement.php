<?php

namespace Modules\StorageManager\Entities;

use App\Product;
use App\User;
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

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActorLabelAttribute(): string
    {
        $actorName = trim((string) optional($this->createdByUser)->user_full_name);

        if ($actorName !== '') {
            return $actorName;
        }

        if (! empty($this->created_by)) {
            return '#' . $this->created_by;
        }

        return (string) __('lang_v1.system');
    }
}
