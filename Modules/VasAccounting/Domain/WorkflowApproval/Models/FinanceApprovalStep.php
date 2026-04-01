<?php

namespace Modules\VasAccounting\Domain\WorkflowApproval\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceApprovalStep extends BaseVasModel
{
    protected $table = 'vas_fin_approval_steps';

    protected $casts = [
        'acted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function approvalInstance(): BelongsTo
    {
        return $this->belongsTo(FinanceApprovalInstance::class, 'approval_instance_id');
    }
}
