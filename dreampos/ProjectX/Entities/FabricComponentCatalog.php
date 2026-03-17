<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class FabricComponentCatalog extends Model
{
    protected $table = 'projectx_fabric_component_catalog';

    protected $guarded = ['id'];

    protected $casts = [
        'aliases' => 'array',
    ];

    public function compositionItems()
    {
        return $this->hasMany(FabricCompositionItem::class, 'fabric_component_catalog_id');
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where(function ($subQuery) use ($business_id) {
            $subQuery->whereNull('business_id')
                ->orWhere('business_id', $business_id);
        });
    }
}