<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceInventoryMovement extends BaseVasModel
{
    protected $table = 'vas_fin_inventory_movements';

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'movement_date' => 'date',
        'reversed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }

    public function documentLine(): BelongsTo
    {
        return $this->belongsTo(FinanceDocumentLine::class, 'document_line_id');
    }

    public function accountingEvent(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountingEvent::class, 'accounting_event_id');
    }

    public function reversalMovement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_movement_id');
    }

    public function costSettlements(): HasMany
    {
        return $this->hasMany(FinanceInventoryCostSettlement::class, 'issue_movement_id')->orderBy('id');
    }
}
