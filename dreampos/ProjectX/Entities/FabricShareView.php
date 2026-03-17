<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class FabricShareView extends Model
{
    protected $table = 'projectx_fabric_share_views';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function fabric()
    {
        return $this->belongsTo(Fabric::class, 'fabric_id');
    }
}