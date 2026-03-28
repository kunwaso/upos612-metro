<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasLoanRepaymentSchedule extends BaseVasModel
{
    protected $table = 'vas_loan_repayment_schedules';

    protected $casts = [
        'due_date' => 'date',
        'principal_due' => 'decimal:4',
        'interest_due' => 'decimal:4',
        'meta' => 'array',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(VasLoan::class, 'loan_id');
    }

    public function settledVoucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'settled_voucher_id');
    }
}
