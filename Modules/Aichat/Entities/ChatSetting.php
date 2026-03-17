<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;

class ChatSetting extends Model
{
    protected $table = 'aichat_chat_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'enabled' => 'boolean',
        'moderation_enabled' => 'boolean',
        'model_allowlist' => 'array',
        'suggested_replies' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('aichat_chat_settings.business_id', $business_id);
    }
}
