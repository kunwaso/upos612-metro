<?php

namespace Modules\VasAccounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasAccount;
use Modules\VasAccounting\Entities\VasOpeningBalanceBatch;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;
use Throwable;

class LegacyAccountingBackfillService
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected VasPostingService $postingService
    ) {
    }

    public function run(int $businessId, ?Carbon $fromDate, ?Carbon $toDate, int $userId, bool $dryRun = false): array
    {
        $this->assertLegacyTablesPresent();
        $this->vasUtil->ensureBusinessBootstrapped($businessId, $userId);

        $currentPeriod = $this->vasUtil->resolvePeriodForDate($businessId, now());
        $fromDate = $fromDate ?: Carbon::parse($currentPeriod->start_date);
        $toDate = $toDate ?: now();

        if ($fromDate->gt($toDate)) {
            throw new RuntimeException('Backfill start date cannot be after end date.');
        }

        $batch = null;
        if (! $dryRun) {
            $batch = VasOpeningBalanceBatch::create([
                'business_id' => $businessId,
                'accounting_period_id' => $currentPeriod->id,
                'reference_no' => 'BACKFILL-' . $fromDate->format('Ymd') . '-' . $toDate->format('Ymd'),
                'status' => 'processing',
                'imported_by' => $userId,
                'meta' => [
                    'mode' => 'legacy_backfill',
                    'from_date' => $fromDate->toDateString(),
                    'to_date' => $toDate->toDateString(),
                    'started_at' => now()->toDateTimeString(),
                ],
            ]);
        }

        $openingPayloads = $this->openingBalancePayloads($businessId, $fromDate, $userId, $batch?->id);
        $historicalPayloads = $this->historicalPayloads($businessId, $fromDate, $toDate, $userId, $batch?->id);

        $summary = [
            'business_id' => $businessId,
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'dry_run' => $dryRun,
            'batch_id' => $batch?->id,
            'opening_balance_count' => count($openingPayloads),
            'historical_transaction_count' => count($historicalPayloads),
            'opening_balance_total' => round((float) collect($openingPayloads)->sum('total_amount'), 2),
            'historical_transaction_total' => round((float) collect($historicalPayloads)->sum('total_amount'), 2),
        ];

        if ($dryRun) {
            return $summary;
        }

        try {
            foreach ($openingPayloads as $payload) {
                $this->postingService->postVoucherPayload($payload['voucher']);
            }

            foreach ($historicalPayloads as $payload) {
                $this->postingService->postVoucherPayload($payload['voucher']);
            }

            $batch?->update([
                'status' => 'completed',
                'meta' => array_replace((array) $batch?->meta, $summary, [
                    'completed_at' => now()->toDateTimeString(),
                ]),
            ]);
        } catch (Throwable $exception) {
            $batch?->update([
                'status' => 'failed',
                'meta' => array_replace((array) $batch?->meta, $summary, [
                    'failed_at' => now()->toDateTimeString(),
                    'error_message' => $exception->getMessage(),
                ]),
            ]);

            throw $exception;
        }

        return $summary;
    }

    protected function openingBalancePayloads(int $businessId, Carbon $fromDate, int $userId, ?int $batchId): array
    {
        $balanceRows = DB::table('accounts as account')
            ->leftJoin('account_types as account_type', 'account_type.id', '=', 'account.account_type_id')
            ->leftJoinSub(
                DB::table('account_transactions')
                    ->whereNull('deleted_at')
                    ->whereDate('operation_date', '<', $fromDate->toDateString())
                    ->selectRaw("account_id, SUM(IF(type = 'credit', amount, -1 * amount)) as legacy_balance")
                    ->groupBy('account_id'),
                'balances',
                function ($join) {
                    $join->on('balances.account_id', '=', 'account.id');
                }
            )
            ->where('account.business_id', $businessId)
            ->whereNull('account.deleted_at')
            ->select(
                'account.id',
                'account.name',
                'account.account_details',
                'account_type.name as account_type_name',
                DB::raw('COALESCE(balances.legacy_balance, 0) as legacy_balance')
            )
            ->get()
            ->filter(fn ($row) => abs((float) $row->legacy_balance) > 0.0001)
            ->values();

        return $balanceRows->map(function ($row) use ($businessId, $fromDate, $userId, $batchId) {
            $amount = round(abs((float) $row->legacy_balance), 4);
            $treasuryAccountId = $this->legacyTreasuryAccountId($businessId, $row);
            $counterpart = $this->openingCounterpartAccountIds($businessId);
            $sourceId = ((int) $row->id * 100000000) + (int) $fromDate->format('Ymd');

            $lines = (float) $row->legacy_balance >= 0
                ? [
                    ['account_id' => $treasuryAccountId, 'description' => 'Legacy opening balance ' . $row->name, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $counterpart['credit'], 'description' => 'Legacy opening balance offset ' . $row->name, 'debit' => 0, 'credit' => $amount],
                ]
                : [
                    ['account_id' => $counterpart['debit'], 'description' => 'Legacy opening balance offset ' . $row->name, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $treasuryAccountId, 'description' => 'Legacy opening balance ' . $row->name, 'debit' => 0, 'credit' => $amount],
                ];

            return [
                'total_amount' => $amount,
                'voucher' => [
                    'business_id' => $businessId,
                    'voucher_type' => 'opening_balance',
                    'sequence_key' => 'opening_balance',
                    'source_type' => 'legacy_opening_balance',
                    'source_id' => $sourceId,
                    'module_area' => 'accounting',
                    'document_type' => 'opening_balance',
                    'posting_date' => $fromDate->toDateString(),
                    'document_date' => $fromDate->toDateString(),
                    'description' => 'Legacy opening balance import for ' . $row->name,
                    'reference' => 'LEGACY-OPEN-' . $row->id,
                    'status' => 'posted',
                    'currency_code' => 'VND',
                    'created_by' => $userId,
                    'is_system_generated' => false,
                    'is_historical_import' => true,
                    'meta' => [
                        'legacy_account_id' => (int) $row->id,
                        'legacy_account_name' => $row->name,
                        'legacy_account_type' => $row->account_type_name,
                        'legacy_balance' => round((float) $row->legacy_balance, 4),
                        'historical_import_batch_id' => $batchId,
                        'cutoff_date' => $fromDate->toDateString(),
                    ],
                    'lines' => $lines,
                ],
            ];
        })->all();
    }

    protected function historicalPayloads(int $businessId, Carbon $fromDate, Carbon $toDate, int $userId, ?int $batchId): array
    {
        return DB::table('account_transactions as entry')
            ->join('accounts as account', 'account.id', '=', 'entry.account_id')
            ->leftJoin('account_types as account_type', 'account_type.id', '=', 'account.account_type_id')
            ->leftJoin('transactions as transaction', 'transaction.id', '=', 'entry.transaction_id')
            ->where('account.business_id', $businessId)
            ->whereNull('account.deleted_at')
            ->whereNull('entry.deleted_at')
            ->whereDate('entry.operation_date', '>=', $fromDate->toDateString())
            ->whereDate('entry.operation_date', '<=', $toDate->toDateString())
            ->orderBy('entry.operation_date')
            ->orderBy('entry.id')
            ->get([
                'entry.id',
                'entry.amount',
                'entry.type',
                'entry.sub_type',
                'entry.operation_date',
                'entry.transaction_id',
                'entry.transaction_payment_id',
                'entry.transfer_transaction_id',
                'entry.note',
                'account.id as legacy_account_id',
                'account.name as legacy_account_name',
                'account.account_details',
                'account_type.name as account_type_name',
                'transaction.location_id as business_location_id',
            ])
            ->filter(fn ($row) => abs((float) $row->amount) > 0.0001)
            ->map(function ($row) use ($businessId, $userId, $batchId) {
                $postingDate = Carbon::parse($row->operation_date)->toDateString();
                $amount = round(abs((float) $row->amount), 4);
                $treasuryAccountId = $this->legacyTreasuryAccountId($businessId, $row);
                $counterpart = $this->openingCounterpartAccountIds($businessId);
                $isCredit = (string) $row->type === 'credit';

                $lines = $isCredit
                    ? [
                        ['account_id' => $treasuryAccountId, 'description' => 'Legacy treasury movement ' . $row->legacy_account_name, 'debit' => $amount, 'credit' => 0],
                        ['account_id' => $counterpart['credit'], 'description' => 'Legacy treasury clearing ' . $row->legacy_account_name, 'debit' => 0, 'credit' => $amount],
                    ]
                    : [
                        ['account_id' => $counterpart['debit'], 'description' => 'Legacy treasury clearing ' . $row->legacy_account_name, 'debit' => $amount, 'credit' => 0],
                        ['account_id' => $treasuryAccountId, 'description' => 'Legacy treasury movement ' . $row->legacy_account_name, 'debit' => 0, 'credit' => $amount],
                    ];

                return [
                    'total_amount' => $amount,
                    'voucher' => [
                        'business_id' => $businessId,
                        'voucher_type' => 'historical_treasury',
                        'sequence_key' => 'historical_treasury',
                        'source_type' => 'legacy_account_transaction',
                        'source_id' => (int) $row->id,
                        'transaction_id' => $row->transaction_id,
                        'transaction_payment_id' => $row->transaction_payment_id,
                        'business_location_id' => $row->business_location_id,
                        'module_area' => 'cash_bank',
                        'document_type' => 'historical_treasury',
                        'posting_date' => $postingDate,
                        'document_date' => $postingDate,
                        'description' => 'Legacy treasury import #' . $row->id . ' for ' . $row->legacy_account_name,
                        'reference' => 'LEGACY-TX-' . $row->id,
                        'status' => 'posted',
                        'currency_code' => 'VND',
                        'created_by' => $userId,
                        'is_system_generated' => false,
                        'is_historical_import' => true,
                        'meta' => [
                            'legacy_account_id' => (int) $row->legacy_account_id,
                            'legacy_account_name' => $row->legacy_account_name,
                            'legacy_account_type' => $row->account_type_name,
                            'legacy_transaction_id' => (int) $row->id,
                            'legacy_transaction_type' => $row->type,
                            'legacy_sub_type' => $row->sub_type,
                            'legacy_note' => $row->note,
                            'transfer_transaction_id' => $row->transfer_transaction_id,
                            'historical_import_batch_id' => $batchId,
                        ],
                        'lines' => $lines,
                    ],
                ];
            })
            ->all();
    }

    protected function legacyTreasuryAccountId(int $businessId, object $legacyAccount): int
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $postingMap = (array) $settings->posting_map;
        $defaultCode = $this->looksLikeBank($legacyAccount) ? '112' : '111';
        $fallbackPostingMapKey = $defaultCode === '112' ? 'bank' : 'cash';
        $accountId = (int) VasAccount::query()
            ->where('business_id', $businessId)
            ->where('account_code', $defaultCode)
            ->value('id');

        $resolved = $accountId > 0 ? $accountId : (int) ($postingMap[$fallbackPostingMapKey] ?? 0);
        if ($resolved <= 0) {
            throw new RuntimeException('Missing treasury mapping for legacy account backfill.');
        }

        return $resolved;
    }

    protected function openingCounterpartAccountIds(int $businessId): array
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $postingMap = (array) $settings->posting_map;

        $creditAccountId = (int) VasAccount::query()
            ->where('business_id', $businessId)
            ->where('account_code', '3388')
            ->value('id');
        $debitAccountId = (int) VasAccount::query()
            ->where('business_id', $businessId)
            ->where('account_code', '1388')
            ->value('id');

        $resolved = [
            'credit' => $creditAccountId > 0 ? $creditAccountId : (int) ($postingMap['accounts_payable'] ?? 0),
            'debit' => $debitAccountId > 0 ? $debitAccountId : (int) ($postingMap['accounts_receivable'] ?? 0),
        ];

        if ($resolved['credit'] <= 0 || $resolved['debit'] <= 0) {
            throw new RuntimeException('Missing counterpart accounts for legacy opening balance backfill.');
        }

        return $resolved;
    }

    protected function looksLikeBank(object $legacyAccount): bool
    {
        $haystack = mb_strtolower(trim((string) ($legacyAccount->legacy_account_name ?? $legacyAccount->name ?? '')));
        $typeName = mb_strtolower(trim((string) ($legacyAccount->account_type_name ?? '')));
        $rawDetails = $legacyAccount->account_details ?? [];
        $details = is_string($rawDetails) ? ((array) json_decode($rawDetails, true)) : (array) $rawDetails;

        if ($typeName !== '' && str_contains($typeName, 'bank')) {
            return true;
        }

        if (str_contains($haystack, 'bank') || str_contains($haystack, 'ngan hang')) {
            return true;
        }

        return ! empty($details['account_number']) || ! empty($details['bank_name']);
    }

    protected function assertLegacyTablesPresent(): void
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasTable('account_transactions')) {
            throw new RuntimeException('Legacy accounting tables are missing, so historical backfill cannot run.');
        }
    }
}
