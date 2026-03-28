<?php

namespace Modules\VasAccounting\Entities;

use App\BusinessLocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VasTool extends BaseVasModel
{
    protected $table = 'vas_tools';

    protected $casts = [
        'original_cost' => 'decimal:4',
        'remaining_value' => 'decimal:4',
        'amortization_months' => 'integer',
        'start_amortization_at' => 'date',
        'meta' => 'array',
    ];

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(VasAccount::class, 'expense_account_id');
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(VasAccount::class, 'asset_account_id');
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

    public function amortizations(): HasMany
    {
        return $this->hasMany(VasToolAmortization::class, 'tool_id');
    }
}
