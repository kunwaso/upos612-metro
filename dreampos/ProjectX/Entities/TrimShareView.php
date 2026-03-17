<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class TrimShareView extends Model
{
    protected $table = 'projectx_trim_share_views';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function trim()
    {
        return $this->belongsTo(Trim::class, 'trim_id');
    }
}
