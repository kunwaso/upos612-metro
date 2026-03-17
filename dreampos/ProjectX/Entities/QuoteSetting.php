<?php

namespace Modules\ProjectX\Entities;

use Illuminate\Database\Eloquent\Model;

class QuoteSetting extends Model
{
    protected $table = 'projectx_quote_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'incoterm_options' => 'array',
        'purchase_uom_options' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function defaultCurrency()
    {
        return $this->belongsTo(\App\Currency::class, 'default_currency_id');
    }
}

