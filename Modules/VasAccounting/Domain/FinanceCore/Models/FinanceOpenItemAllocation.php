<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceOpenItemAllocation extends BaseVasModel
{
    protected $table = 'vas_fin_open_item_allocations';

    protected $casts = [
        'allocation_date' => 'date',
        'amount' => 'decimal:4',
        'reversed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function sourceOpenItem(): BelongsTo
    {
        return $this->belongsTo(FinanceOpenItem::class, 'source_open_item_id');
    }

    public function targetOpenItem(): BelongsTo
    {
        return $this->belongsTo(FinanceOpenItem::class, 'target_open_item_id');
    }

    public function accountingEvent(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountingEvent::class, 'accounting_event_id');
    }
}
