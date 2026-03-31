<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasApprovalRuleStep extends BaseVasModel
{
    protected $table = 'vas_approval_rule_steps';

    protected $casts = [
        'is_required' => 'boolean',
        'meta' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(VasApprovalRule::class, 'approval_rule_id');
    }
}
