<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;
use Modules\VasAccounting\Entities\VasBankStatementLine;

class FinanceTreasuryReconciliation extends BaseVasModel
{
    protected $table = 'vas_fin_treasury_reconciliations';

    protected $casts = [
        'match_confidence' => 'decimal:4',
        'statement_amount' => 'decimal:4',
        'document_amount' => 'decimal:4',
        'matched_amount' => 'decimal:4',
        'reconciled_at' => 'datetime',
        'reversed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function statementLine(): BelongsTo
    {
        return $this->belongsTo(VasBankStatementLine::class, 'statement_line_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }

    public function openItem(): BelongsTo
    {
        return $this->belongsTo(FinanceOpenItem::class, 'open_item_id');
    }

    public function accountingEvent(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountingEvent::class, 'accounting_event_id');
    }
}
