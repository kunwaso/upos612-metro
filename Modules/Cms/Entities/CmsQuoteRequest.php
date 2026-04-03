<?php

namespace Modules\Cms\Entities;

use App\Product;
use Illuminate\Database\Eloquent\Model;

class CmsQuoteRequest extends Model
{
    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
