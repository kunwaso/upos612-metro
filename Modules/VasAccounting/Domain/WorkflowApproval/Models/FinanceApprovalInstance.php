<?php

namespace Modules\VasAccounting\Domain\WorkflowApproval\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceApprovalInstance extends BaseVasModel
{
    protected $table = 'vas_fin_approval_instances';

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(FinanceApprovalStep::class, 'approval_instance_id')->orderBy('step_no');
    }
}
