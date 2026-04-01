<?php

namespace Modules\VasAccounting\Application\DTOs;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceAccountingEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceJournalEntry;

class PostingResult
{
    /**
     * @param string[] $warnings
     */
    public function __construct(
        public FinanceAccountingEvent $event,
        public FinanceJournalEntry $journalEntry,
        public array $warnings = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->event->id,
            'event_type' => $this->event->event_type,
            'journal_entry_id' => $this->journalEntry->id,
            'journal_no' => $this->journalEntry->journal_no,
            'total_debit' => (string) $this->journalEntry->total_debit,
            'total_credit' => (string) $this->journalEntry->total_credit,
            'warnings' => $this->warnings,
        ];
    }
}
