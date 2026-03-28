<?php

namespace Modules\VasAccounting\Entities;

use App\BusinessLocation;
use App\Contact;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VasContract extends BaseVasModel
{
    protected $table = 'vas_contracts';

    protected $casts = [
        'signed_at' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'contract_value' => 'decimal:4',
        'advance_amount' => 'decimal:4',
        'retention_amount' => 'decimal:4',
        'meta' => 'array',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(VasProject::class, 'project_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(VasCostCenter::class, 'cost_center_id');
    }

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(VasContractMilestone::class, 'contract_id')->orderBy('milestone_date');
    }
}
