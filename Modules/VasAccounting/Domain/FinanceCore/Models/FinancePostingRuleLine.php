<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinancePostingRuleLine extends BaseVasModel
{
    protected $table = 'vas_fin_posting_rule_lines';

    protected $casts = [
        'conditions' => 'array',
        'meta' => 'array',
        'is_balancing_line' => 'boolean',
    ];

    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(FinancePostingRuleSet::class, 'posting_rule_set_id');
    }
}
