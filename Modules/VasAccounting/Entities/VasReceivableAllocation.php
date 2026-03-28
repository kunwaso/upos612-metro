<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasReceivableAllocation extends BaseVasModel
{
    protected $table = 'vas_receivable_allocations';

    protected $casts = [
        'allocation_date' => 'date',
        'amount' => 'decimal:4',
        'meta' => 'array',
    ];

    public function invoiceVoucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'invoice_voucher_id');
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'payment_voucher_id');
    }
}
