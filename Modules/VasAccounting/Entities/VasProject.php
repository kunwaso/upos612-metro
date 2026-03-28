<?php

namespace Modules\VasAccounting\Entities;

use App\Contact;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasProject extends BaseVasModel
{
    protected $table = 'vas_projects';

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget_amount' => 'decimal:4',
        'meta' => 'array',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(VasCostCenter::class, 'cost_center_id');
    }
}
