<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;

class UserChatProfile extends Model
{
    protected $table = 'aichat_user_chat_profile';

    protected $guarded = ['id'];

    protected $casts = [
        'concerns_topics' => 'encrypted',
        'preferences' => 'encrypted',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('aichat_user_chat_profile.business_id', $business_id);
    }

    public function scopeForUser($query, int $business_id, int $user_id)
    {
        return $query
            ->where('aichat_user_chat_profile.business_id', $business_id)
            ->where('aichat_user_chat_profile.user_id', $user_id);
    }
}

