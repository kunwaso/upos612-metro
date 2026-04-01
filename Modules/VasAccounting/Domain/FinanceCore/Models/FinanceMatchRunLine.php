<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceMatchRunLine extends BaseVasModel
{
    protected $table = 'vas_fin_match_run_lines';

    protected $casts = [
        'matched_quantity' => 'decimal:4',
        'matched_amount' => 'decimal:4',
        'matched_tax_amount' => 'decimal:4',
        'variance_quantity' => 'decimal:4',
        'variance_amount' => 'decimal:4',
        'variance_tax_amount' => 'decimal:4',
        'meta' => 'array',
    ];

    public function matchRun(): BelongsTo
    {
        return $this->belongsTo(FinanceMatchRun::class, 'match_run_id');
    }

    public function documentLine(): BelongsTo
    {
        return $this->belongsTo(FinanceDocumentLine::class, 'document_line_id');
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'source_document_id');
    }

    public function sourceDocumentLine(): BelongsTo
    {
        return $this->belongsTo(FinanceDocumentLine::class, 'source_document_line_id');
    }
}
