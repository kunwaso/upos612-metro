<?php

namespace Modules\VasAccounting\Entities;

class VasExchangeRate extends BaseVasModel
{
    protected $table = 'vas_exchange_rates';

    protected $casts = [
        'rate_date' => 'date',
        'rate' => 'decimal:8',
        'inverse_rate' => 'decimal:8',
        'is_manual' => 'boolean',
        'meta' => 'array',
    ];
}
