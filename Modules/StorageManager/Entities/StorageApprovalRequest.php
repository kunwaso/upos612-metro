<?php

namespace Modules\StorageManager\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageApprovalRequest extends Model
{
    protected $table = 'storage_approval_requests';

    protected $guarded = ['id'];

    protected $casts = [
        'threshold_value' => 'decimal:4',
        'resolved_at' => 'datetime',
        'payload' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(StorageDocument::class, 'document_id');
    }

    public function documentLine(): BelongsTo
    {
        return $this->belongsTo(StorageDocumentLine::class, 'document_line_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
