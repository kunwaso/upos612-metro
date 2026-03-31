<?php

namespace Modules\VasAccounting\Entities;

class VasPayrollRunLine extends BaseVasModel
{
    protected $table = 'vas_payroll_run_lines';

    protected $casts = [
        'gross_amount' => 'decimal:4',
        'employee_deductions' => 'decimal:4',
        'employer_contributions' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'earnings' => 'array',
        'deductions' => 'array',
        'statutory_breakdown' => 'array',
        'meta' => 'array',
    ];
}
