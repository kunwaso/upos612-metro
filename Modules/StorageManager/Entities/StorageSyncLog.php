<?php

namespace Modules\StorageManager\Entities;

use App\User;
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
