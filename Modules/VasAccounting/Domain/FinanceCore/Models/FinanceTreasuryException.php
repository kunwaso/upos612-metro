<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;
use Modules\VasAccounting\Entities\VasBankStatementLine;

class FinanceTreasuryException extends BaseVasModel
{
    protected $table = 'vas_fin_treasury_exceptions';

    protected $casts = [
        'top_match_score' => 'decimal:4',
        'reviewed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function statementLine(): BelongsTo
    {
        return $this->belongsTo(VasBankStatementLine::class, 'statement_line_id');
    }

    public function recommendedDocument(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'recommended_document_id');
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(FinanceTreasuryReconciliation::class, 'reconciliation_id');
    }
}
