<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasPayableAllocation extends BaseVasModel
{
    protected $table = 'vas_payable_allocations';

    protected $casts = [
        'allocation_date' => 'date',
        'amount' => 'decimal:4',
        'meta' => 'array',
    ];

    public function billVoucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'bill_voucher_id');
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'payment_voucher_id');
    }
}
