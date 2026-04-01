<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceJournalEntryLine extends BaseVasModel
{
    protected $table = 'vas_fin_journal_entry_lines';

    protected $casts = [
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'dimensions' => 'array',
        'meta' => 'array',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(FinanceJournalEntry::class, 'journal_entry_id');
    }
}
