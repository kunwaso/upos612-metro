<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class ChatAuditLog extends Model
{
    protected $table = 'projectx_chat_audit_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('projectx_chat_audit_logs.business_id', $business_id);
    }
}

