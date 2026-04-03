<?php

namespace Modules\StorageManager\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageDocumentLink extends Model
{
    protected $table = 'storage_document_links';

    protected $guarded = ['id'];

    protected $casts = [
        'synced_at' => 'datetime',
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(StorageDocument::class, 'document_id');
    }
}
