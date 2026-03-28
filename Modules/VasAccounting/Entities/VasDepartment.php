<?php

namespace Modules\VasAccounting\Entities;

use App\BusinessLocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasDepartment extends BaseVasModel
{
    protected $table = 'vas_departments';

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }
}
