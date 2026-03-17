<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class QuoteLine extends Model
{
    protected $table = 'projectx_quote_lines';

    protected $guarded = ['id'];

    protected $casts = [
        'fabric_snapshot' => 'array',
        'trim_snapshot' => 'array',
        'costing_input' => 'array',
        'costing_breakdown' => 'array',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function fabric()
    {
        return $this->belongsTo(Fabric::class, 'fabric_id');
    }

    public function trim()
    {
        return $this->belongsTo(Trim::class, 'trim_id');
    }
}
