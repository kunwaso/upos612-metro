<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class ChatMemory extends Model
{
    protected $table = 'projectx_chat_memory';

    protected $guarded = ['id'];

    protected $casts = [
        'memory_value' => 'encrypted',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function updater()
    {
        return $this->belongsTo(\App\User::class, 'updated_by');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('projectx_chat_memory.business_id', $business_id);
    }
}
