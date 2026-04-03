<?php

namespace Modules\StorageManager\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageTask extends Model
{
    protected $table = 'storage_tasks';

    protected $guarded = ['id'];

    protected $casts = [
        'target_qty' => 'decimal:4',
        'completed_qty' => 'decimal:4',
        'due_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(StorageTaskEvent::class, 'task_id');
    }
}
