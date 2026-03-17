<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class ChatSetting extends Model
{
    protected $table = 'projectx_chat_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'enabled' => 'boolean',
        'fabric_insight_enabled' => 'boolean',
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
        return $query->where('projectx_chat_settings.business_id', $business_id);
    }
}

