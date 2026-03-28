<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VasBankStatementImport extends BaseVasModel
{
    protected $table = 'vas_bank_statement_imports';

    protected $casts = [
        'imported_at' => 'datetime',
        'meta' => 'array',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(VasBankAccount::class, 'bank_account_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VasBankStatementLine::class, 'statement_import_id')
            ->orderBy('transaction_date')
            ->orderBy('id');
    }
}
