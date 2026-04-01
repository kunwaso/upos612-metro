<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceInventoryCostLayer extends BaseVasModel
{
    protected $table = 'vas_fin_inventory_cost_layers';

    protected $casts = [
        'quantity_in' => 'decimal:4',
        'quantity_out' => 'decimal:4',
        'quantity_on_hand' => 'decimal:4',
        'total_value_in' => 'decimal:4',
        'total_value_out' => 'decimal:4',
        'total_value_on_hand' => 'decimal:4',
        'average_unit_cost' => 'decimal:4',
        'meta' => 'array',
    ];

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'source_document_id');
    }

    public function sourceDocumentLine(): BelongsTo
    {
        return $this->belongsTo(FinanceDocumentLine::class, 'source_document_line_id');
    }

    public function receiptMovement(): BelongsTo
    {
        return $this->belongsTo(FinanceInventoryMovement::class, 'receipt_movement_id');
    }
}
