<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceDocumentLink extends BaseVasModel
{
    protected $table = 'vas_fin_document_links';

    protected $casts = [
        'meta' => 'array',
    ];

    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'parent_document_id');
    }

    public function childDocument(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'child_document_id');
    }
}
