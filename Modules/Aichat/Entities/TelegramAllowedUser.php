<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;

class TelegramAllowedUser extends Model
{
    protected $table = 'aichat_telegram_allowed_users';

    protected $guarded = ['id'];

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
        return $query->where('aichat_telegram_allowed_users.business_id', $business_id);
    }
}

