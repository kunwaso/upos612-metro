<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ChatPendingAction extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_EXECUTED = 'executed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_FAILED = 'failed';

    protected $table = 'aichat_pending_actions';

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'result_payload' => 'array',
        'confirmed_at' => 'datetime',
        'executed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('aichat_pending_actions.business_id', $business_id);
    }

    public function scopeForConversation($query, string $conversation_id)
    {
        return $query->where('aichat_pending_actions.conversation_id', $conversation_id);
    }

    public function scopeForUser($query, int $user_id)
    {
        return $query->where('aichat_pending_actions.user_id', $user_id);
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof Carbon && $this->expires_at->isPast();
    }
}

