<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceOpenItem extends BaseVasModel
{
    protected $table = 'vas_fin_open_items';

    protected $casts = [
        'document_date' => 'date',
        'posting_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:8',
        'original_amount' => 'decimal:4',
        'open_amount' => 'decimal:4',
        'settled_amount' => 'decimal:4',
        'reversed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }

    public function accountingEvent(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountingEvent::class, 'accounting_event_id');
    }

    public function allocationsFrom(): HasMany
    {
        return $this->hasMany(FinanceOpenItemAllocation::class, 'source_open_item_id')->orderBy('allocation_date');
    }

    public function allocationsTo(): HasMany
    {
        return $this->hasMany(FinanceOpenItemAllocation::class, 'target_open_item_id')->orderBy('allocation_date');
    }
}
