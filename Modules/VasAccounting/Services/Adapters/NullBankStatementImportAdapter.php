<?php

namespace Modules\VasAccounting\Services\Adapters;

use Illuminate\Support\Arr;
use Modules\VasAccounting\Contracts\BankStatementImportAdapterInterface;

class NullBankStatementImportAdapter implements BankStatementImportAdapterInterface
{
    public function import(array $payload): array
    {
        $lines = collect((array) Arr::get($payload, 'lines', []))
            ->map(fn (array $line) => $this->normalize($line))
            ->all();

        return [
            'status' => 'queued_for_manual_reconciliation',
            'provider' => Arr::get($payload, 'provider', 'manual'),
            'lines' => $lines,
        ];
    }

    public function normalize(array $payload): array
    {
        return [
            'transaction_date' => Arr::get($payload, 'transaction_date'),
            'description' => Arr::get($payload, 'description'),
            'amount' => (float) Arr::get($payload, 'amount', 0),
            'running_balance' => Arr::get($payload, 'running_balance'),
            'match_status' => Arr::get($payload, 'match_status', 'unmatched'),
            'meta' => Arr::get($payload, 'meta', []),
        ];
    }
}
