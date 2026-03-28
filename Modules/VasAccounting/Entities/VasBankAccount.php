<?php

namespace Modules\VasAccounting\Entities;

use App\BusinessLocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasBankAccount extends BaseVasModel
{
    protected $table = 'vas_bank_accounts';

    protected $casts = [
        'meta' => 'array',
    ];

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(VasAccount::class, 'ledger_account_id');
    }
}
