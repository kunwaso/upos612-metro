<?php

namespace Modules\VasAccounting\Entities;

class VasPayrollProfile extends BaseVasModel
{
    protected $table = 'vas_payroll_profiles';

    protected $casts = [
        'earning_components' => 'array',
        'deduction_components' => 'array',
        'statutory_components' => 'array',
        'meta' => 'array',
    ];
}
