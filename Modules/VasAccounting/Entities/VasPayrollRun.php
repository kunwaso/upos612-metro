<?php

namespace Modules\VasAccounting\Entities;

class VasPayrollRun extends BaseVasModel
{
    protected $table = 'vas_payroll_runs';

    protected $casts = [
        'gross_total' => 'decimal:4',
        'employee_deduction_total' => 'decimal:4',
        'employer_contribution_total' => 'decimal:4',
        'net_total' => 'decimal:4',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'meta' => 'array',
    ];
}
