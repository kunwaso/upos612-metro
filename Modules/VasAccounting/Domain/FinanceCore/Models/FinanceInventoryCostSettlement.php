<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceInventoryCostSettlement extends BaseVasModel
{
    protected $table = 'vas_fin_inventory_cost_settlements';

    protected $casts = [
        'settled_quantity' => 'decimal:4',
        'settled_value' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'meta' => 'array',
    ];

    public function issueMovement(): BelongsTo
    {
        return $this->belongsTo(FinanceInventoryMovement::class, 'issue_movement_id');
    }

    public function costLayer(): BelongsTo
    {
        return $this->belongsTo(FinanceInventoryCostLayer::class, 'cost_layer_id');
    }
}
