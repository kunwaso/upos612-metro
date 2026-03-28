<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasBankStatementLine extends BaseVasModel
{
    protected $table = 'vas_bank_statement_lines';

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:4',
        'running_balance' => 'decimal:4',
        'meta' => 'array',
    ];

    public function statementImport(): BelongsTo
    {
        return $this->belongsTo(VasBankStatementImport::class, 'statement_import_id');
    }

    public function matchedVoucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'matched_voucher_id');
    }
}
