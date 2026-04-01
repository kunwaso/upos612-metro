<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceJournalEntry extends BaseVasModel
{
    protected $table = 'vas_fin_journal_entries';

    protected $casts = [
        'posting_date' => 'date',
        'total_debit' => 'decimal:4',
        'total_credit' => 'decimal:4',
        'posted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountingEvent::class, 'accounting_event_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinanceJournalEntryLine::class, 'journal_entry_id')->orderBy('line_no');
    }
}
