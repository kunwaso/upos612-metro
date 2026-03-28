<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasJournalEntry extends BaseVasModel
{
    protected $table = 'vas_journal_entries';

    protected $casts = [
        'posting_date' => 'date',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'meta' => 'array',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'voucher_id');
    }
}
