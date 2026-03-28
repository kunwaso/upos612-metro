<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasBudgetLine extends BaseVasModel
{
    protected $table = 'vas_budget_lines';

    protected $casts = [
        'budget_amount' => 'decimal:4',
        'committed_amount' => 'decimal:4',
        'actual_amount' => 'decimal:4',
        'meta' => 'array',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(VasBudget::class, 'budget_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(VasAccount::class, 'account_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(VasDepartment::class, 'department_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(VasCostCenter::class, 'cost_center_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(VasProject::class, 'project_id');
    }
}
