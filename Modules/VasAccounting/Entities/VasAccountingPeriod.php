<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\HasMany;

class VasAccountingPeriod extends BaseVasModel
{
    protected $table = 'vas_accounting_periods';

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'meta' => 'array',
        'is_adjustment_period' => 'boolean',
    ];

    public function vouchers(): HasMany
    {
        return $this->hasMany(VasVoucher::class, 'accounting_period_id');
    }
}
