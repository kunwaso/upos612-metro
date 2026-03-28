<?php

namespace Modules\VasAccounting\Entities;

use App\BusinessLocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasCashbook extends BaseVasModel
{
    protected $table = 'vas_cashbooks';

    protected $casts = [
        'meta' => 'array',
    ];

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(VasAccount::class, 'cash_account_id');
    }
}
