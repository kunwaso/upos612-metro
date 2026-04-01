<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceMatchRun extends BaseVasModel
{
    protected $table = 'vas_fin_match_runs';

    protected $casts = [
        'parent_document_ids' => 'array',
        'meta' => 'array',
        'matched_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinanceMatchRunLine::class, 'match_run_id')->orderBy('id');
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(FinanceMatchException::class, 'match_run_id')->orderBy('id');
    }
}
