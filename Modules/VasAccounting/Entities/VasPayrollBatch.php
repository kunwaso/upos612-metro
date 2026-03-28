<?php

namespace Modules\VasAccounting\Entities;

use App\BusinessLocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VasPayrollBatch extends BaseVasModel
{
    protected $table = 'vas_payroll_batches';

    protected $casts = [
        'payroll_month' => 'date',
        'gross_total' => 'decimal:4',
        'net_total' => 'decimal:4',
        'finalized_at' => 'datetime',
        'meta' => 'array',
    ];

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(VasVoucher::class, 'source_id')->where('source_type', 'payroll_batch');
    }
}
