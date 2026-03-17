<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;

class TelegramLinkCode extends Model
{
    protected $table = 'aichat_telegram_link_codes';

    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
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
        return $query->where('aichat_telegram_link_codes.business_id', $business_id);
    }

    public function scopeNotExpired($query)
    {
        return $query->where('aichat_telegram_link_codes.expires_at', '>', now());
    }
}

