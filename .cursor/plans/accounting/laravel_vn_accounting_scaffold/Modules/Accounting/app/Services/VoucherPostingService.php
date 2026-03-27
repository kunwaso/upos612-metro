<?php

namespace Modules\Accounting\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\app\Models\Voucher;

class VoucherPostingService
{
    public function post(Voucher $voucher): Voucher
    {
        return DB::transaction(function () use ($voucher) {
            if ($voucher->status === 'posted') {
                return $voucher;
            }

            $voucher->loadMissing('lines');

            $totalDebit = $voucher->lines->sum(function ($line) {
                return $line->debit_account_id ? (float) $line->amount : 0;
            });

            $totalCredit = $voucher->lines->sum(function ($line) {
                return $line->credit_account_id ? (float) $line->amount : 0;
            });

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new \RuntimeException('Voucher is not balanced.');
            }

            // TODO: write journal entries and roll up ledger balances.

            $voucher->status = 'posted';
            $voucher->posted_at = now();
            $voucher->save();

            return $voucher;
        });
    }
}
