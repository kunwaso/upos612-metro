<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class TrimCategory extends Model
{
    protected $table = 'projectx_trim_categories';

    protected $guarded = ['id'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function trims()
    {
        return $this->hasMany(Trim::class, 'trim_category_id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('projectx_trim_categories.business_id', $business_id);
    }
}
