<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinancePostingRuleSet extends BaseVasModel
{
    protected $table = 'vas_fin_posting_rule_sets';

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
        'conditions' => 'array',
        'meta' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(FinancePostingRuleLine::class, 'posting_rule_set_id')->orderBy('line_no');
    }
}
