<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasContractMilestone extends BaseVasModel
{
    protected $table = 'vas_contract_milestones';

    protected $casts = [
        'milestone_date' => 'date',
        'billing_date' => 'date',
        'revenue_amount' => 'decimal:4',
        'advance_amount' => 'decimal:4',
        'retention_amount' => 'decimal:4',
        'meta' => 'array',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(VasContract::class, 'contract_id');
    }

    public function postedVoucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'posted_voucher_id');
    }
}
