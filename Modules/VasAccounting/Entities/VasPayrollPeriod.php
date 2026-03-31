<?php

namespace Modules\VasAccounting\Entities;

class VasPayrollPeriod extends BaseVasModel
{
    protected $table = 'vas_payroll_periods';

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
        'meta' => 'array',
    ];
}
