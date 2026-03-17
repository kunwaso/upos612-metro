<?php

namespace Modules\Aichat\Entities;

use Illuminate\Database\Eloquent\Model;

class PersistentMemory extends Model
{
    protected $table = 'aichat_persistent_memory';

    protected $guarded = ['id'];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function memoryFacts()
    {
        return $this->hasMany(ChatMemory::class, 'business_id', 'business_id')
            ->orderBy('memory_key');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('aichat_persistent_memory.business_id', $business_id);
    }
}

