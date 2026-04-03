<?php

namespace Modules\StorageManager\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageSyncLog extends Model
{
    protected $table = 'storage_sync_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(StorageDocument::class, 'document_id');
    }
}
