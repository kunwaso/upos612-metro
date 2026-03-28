<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VasLoan extends BaseVasModel
{
    protected $table = 'vas_loans';

    protected $casts = [
        'principal_amount' => 'decimal:4',
        'interest_rate' => 'decimal:4',
        'disbursement_date' => 'date',
        'maturity_date' => 'date',
        'meta' => 'array',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(VasBankAccount::class, 'bank_account_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(VasContract::class, 'contract_id');
    }

    public function repaymentSchedules(): HasMany
    {
        return $this->hasMany(VasLoanRepaymentSchedule::class, 'loan_id')->orderBy('due_date');
    }
}
