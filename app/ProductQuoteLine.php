<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductQuoteLine extends Model
{
    protected $table = 'product_quote_lines';

    protected $guarded = ['id'];

    protected $casts = [
        'product_snapshot' => 'array',
        'costing_input' => 'array',
        'costing_breakdown' => 'array',
    ];

    public function quote()
    {
        return $this->belongsTo(ProductQuote::class, 'quote_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
