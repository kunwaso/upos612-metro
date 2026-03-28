<?php

namespace Modules\VasAccounting\Entities;

use App\BusinessLocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasWarehouse extends BaseVasModel
{
    protected $table = 'vas_warehouses';

    protected $casts = [
        'meta' => 'array',
    ];

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }
}
