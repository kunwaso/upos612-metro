<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChatConversation extends Model
{
    protected $table = 'aichat_chat_conversations';

    protected $guarded = ['id'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'is_favorite' => 'boolean',
        'is_archived' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $conversation) {
            if (empty($conversation->id)) {
                $conversation->id = (string) Str::uuid();
            }

            if (empty($conversation->title)) {
                $conversation->title = 'New Chat';
            }
        });
    }

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('aichat_chat_conversations.business_id', $business_id);
    }

    public function scopeForUser($query, int $user_id)
    {
        return $query->where('aichat_chat_conversations.user_id', $user_id);
    }
}
