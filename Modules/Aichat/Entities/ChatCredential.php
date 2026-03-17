<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;

class ChatCredential extends Model
{
    protected $table = 'aichat_chat_credentials';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'rotated_at' => 'datetime',
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
        return $query->where('aichat_chat_credentials.business_id', $business_id);
    }

    public function scopeForScope($query, string $scope, ?int $user_id = null)
    {
        if ($scope === 'user') {
            return $query->whereNotNull('user_id')->where('user_id', $user_id);
        }

        return $query->whereNull('user_id');
    }
}


