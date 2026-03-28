<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VasBudget extends BaseVasModel
{
    protected $table = 'vas_budgets';

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'meta' => 'array',
    ];

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

    public function lines(): HasMany
    {
        return $this->hasMany(VasBudgetLine::class, 'budget_id')->orderBy('id');
    }
}
