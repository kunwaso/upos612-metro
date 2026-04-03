<?php

namespace Modules\StorageManager\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageCountSession extends Model
{
    protected $table = 'storage_count_sessions';

    protected $guarded = ['id'];

    protected $casts = [
        'blind_count' => 'boolean',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(StorageArea::class, 'area_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StorageCountLine::class, 'count_session_id');
    }
}
