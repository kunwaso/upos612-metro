<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasDocumentApproval extends BaseVasModel
{
    protected $table = 'vas_document_approvals';

    protected $casts = [
        'acted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function approvalRule(): BelongsTo
    {
        return $this->belongsTo(VasApprovalRule::class, 'approval_rule_id');
    }

    public function approvalRuleStep(): BelongsTo
    {
        return $this->belongsTo(VasApprovalRuleStep::class, 'approval_rule_step_id');
    }
}
