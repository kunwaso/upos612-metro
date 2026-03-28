<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasVoucherLine extends BaseVasModel
{
    protected $table = 'vas_voucher_lines';

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'meta' => 'array',
        'line_no' => 'integer',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'voucher_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(VasAccount::class, 'account_id');
    }
}
