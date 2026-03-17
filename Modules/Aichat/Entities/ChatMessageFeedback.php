<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;

class ChatMessageFeedback extends Model
{
    protected $table = 'aichat_chat_message_feedback';

    protected $guarded = ['id'];

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

    public function message()
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('aichat_chat_message_feedback.business_id', $business_id);
    }
}


