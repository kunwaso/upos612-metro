<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasCostCenter extends BaseVasModel
{
    protected $table = 'vas_cost_centers';

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(VasDepartment::class, 'department_id');
    }
}
