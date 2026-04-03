<?php

namespace Modules\StorageManager\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageTaskEvent extends Model
{
    protected $table = 'storage_task_events';

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(StorageTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
