<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\HasMany;

class VasApprovalRule extends BaseVasModel
{
    protected $table = 'vas_approval_rules';

    protected $casts = [
        'min_amount' => 'decimal:4',
        'max_amount' => 'decimal:4',
        'auto_approve_below' => 'decimal:4',
        'is_active' => 'boolean',
        'conditions' => 'array',
        'meta' => 'array',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(VasApprovalRuleStep::class, 'approval_rule_id')->orderBy('step_no');
    }
}
