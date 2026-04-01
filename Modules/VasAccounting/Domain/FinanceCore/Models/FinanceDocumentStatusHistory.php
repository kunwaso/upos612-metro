<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceDocumentStatusHistory extends BaseVasModel
{
    protected $table = 'vas_fin_document_status_history';

    protected $casts = [
        'acted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }
}
