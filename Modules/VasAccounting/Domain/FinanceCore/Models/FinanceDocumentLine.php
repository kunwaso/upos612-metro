<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceDocumentLine extends BaseVasModel
{
    protected $table = 'vas_fin_document_lines';

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'line_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'gross_amount' => 'decimal:4',
        'dimensions' => 'array',
        'payload' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }
}
