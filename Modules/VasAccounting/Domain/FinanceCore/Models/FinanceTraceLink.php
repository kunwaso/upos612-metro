<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceTraceLink extends BaseVasModel
{
    protected $table = 'vas_fin_trace_links';

    protected $casts = [
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }
}
