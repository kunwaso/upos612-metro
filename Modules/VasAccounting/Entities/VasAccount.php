<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VasAccount extends BaseVasModel
{
    protected $table = 'vas_accounts';

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'allows_manual_entries' => 'boolean',
        'is_control_account' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
