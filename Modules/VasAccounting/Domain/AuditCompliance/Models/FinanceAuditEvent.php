<?php

namespace Modules\VasAccounting\Domain\AuditCompliance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceAccountingEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceJournalEntry;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceAuditEvent extends BaseVasModel
{
    protected $table = 'vas_fin_audit_events';

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'meta' => 'array',
        'acted_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }

    public function accountingEvent(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountingEvent::class, 'accounting_event_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(FinanceJournalEntry::class, 'journal_entry_id');
    }
}
