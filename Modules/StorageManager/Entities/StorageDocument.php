<?php

namespace Modules\StorageManager\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\VasAccounting\Entities\VasInventoryDocument;

class StorageDocument extends Model
{
    protected $table = 'storage_documents';

    protected $guarded = ['id'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'area_id');
    }

    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_document_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StorageDocumentLine::class, 'document_id')->orderBy('line_no');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(StorageTask::class, 'document_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(StorageDocumentLink::class, 'document_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(StorageSyncLog::class, 'document_id');
    }

    public function vasInventoryDocument(): BelongsTo
    {
        return $this->belongsTo(VasInventoryDocument::class, 'vas_inventory_document_id');
    }
}
