<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;

class TelegramBot extends Model
{
    protected $table = 'aichat_telegram_bots';

    protected $guarded = ['id'];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'linked_user_id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('aichat_telegram_bots.business_id', $business_id);
    }

    public function scopeForWebhookKey($query, string $webhookKey)
    {
        return $query->where('aichat_telegram_bots.webhook_key', $webhookKey);
    }
}

