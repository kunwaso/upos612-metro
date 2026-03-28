<?php

namespace Modules\VasAccounting\Entities;

class VasLedgerBalance extends BaseVasModel
{
    protected $table = 'vas_ledger_balances';

    protected $casts = [
        'opening_debit' => 'decimal:4',
        'opening_credit' => 'decimal:4',
        'period_debit' => 'decimal:4',
        'period_credit' => 'decimal:4',
        'closing_debit' => 'decimal:4',
        'closing_credit' => 'decimal:4',
        'last_posted_at' => 'datetime',
    ];
}
