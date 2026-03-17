<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $table = 'aichat_chat_messages';

    protected $guarded = ['id'];

    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_ERROR = 'error';
    public const ROLE_SYSTEM = 'system';

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

    public function feedback()
    {
        return $this->hasMany(ChatMessageFeedback::class, 'message_id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('aichat_chat_messages.business_id', $business_id);
    }
}
