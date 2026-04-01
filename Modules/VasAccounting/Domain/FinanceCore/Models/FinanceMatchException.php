<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceMatchException extends BaseVasModel
{
    protected $table = 'vas_fin_match_exceptions';

    protected $casts = [
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }

    public function matchRun(): BelongsTo
    {
        return $this->belongsTo(FinanceMatchRun::class, 'match_run_id');
    }

    public function documentLine(): BelongsTo
    {
        return $this->belongsTo(FinanceDocumentLine::class, 'document_line_id');
    }
}
