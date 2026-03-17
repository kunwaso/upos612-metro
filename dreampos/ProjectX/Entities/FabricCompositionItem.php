<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class FabricCompositionItem extends Model
{
    protected $table = 'projectx_fabric_composition_items';

    protected $guarded = ['id'];

    protected $casts = [
        'percent' => 'float',
    ];

    public function fabric()
    {
        return $this->belongsTo(Fabric::class, 'fabric_id');
    }

    public function catalogComponent()
    {
        return $this->belongsTo(FabricComponentCatalog::class, 'fabric_component_catalog_id');
    }
}