<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasToolAmortization extends BaseVasModel
{
    protected $table = 'vas_tool_amortizations';

    protected $casts = [
        'amortization_date' => 'date',
        'amount' => 'decimal:4',
        'posted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(VasTool::class, 'tool_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'voucher_id');
    }
}
