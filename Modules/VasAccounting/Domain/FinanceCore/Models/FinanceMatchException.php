<?php

namespace Modules\VasAccounting\Domain\FinanceCore\Models;

use App\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VasAccounting\Entities\BaseVasModel;

class FinanceMatchException extends BaseVasModel
{
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_RESOLVED = 'resolved';

    protected $table = 'vas_fin_match_exceptions';

    protected $casts = [
        'meta' => 'array',
        'owner_assigned_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(FinanceDocument::class, 'document_id');
    }

    public function matchRun(): BelongsTo
    {
        return $this->belongsTo(FinanceMatchRun::class, 'match_run_id');
    }

    public function documentLine(): BelongsTo
    {
        return $this->belongsTo(FinanceDocumentLine::class, 'document_line_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public static function unresolvedStatuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_IN_REVIEW,
        ];
    }
}
