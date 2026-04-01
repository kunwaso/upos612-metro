<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceAccountingEventLine extends BaseVasModel
{
    protected $table = 'vas_fin_accounting_event_lines';

    protected $casts = [
        'posting_date' => 'date',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'dimensions' => 'array',
        'meta' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountingEvent::class, 'accounting_event_id');
    }

    public function documentLine(): BelongsTo
    {
        return $this->belongsTo(FinanceDocumentLine::class, 'document_line_id');
    }
}
