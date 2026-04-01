<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceAccountingEvent extends BaseVasModel
{
    protected $table = 'vas_fin_accounting_events';

    protected $casts = [
        'posting_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'total_debit' => 'decimal:4',
        'total_credit' => 'decimal:4',
        'prepared_at' => 'datetime',
        'posted_at' => 'datetime',
        'warnings' => 'array',
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }

    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(FinancePostingRuleSet::class, 'posting_rule_set_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinanceAccountingEventLine::class, 'accounting_event_id')->orderBy('line_no');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(FinanceJournalEntry::class, 'accounting_event_id');
    }
}
