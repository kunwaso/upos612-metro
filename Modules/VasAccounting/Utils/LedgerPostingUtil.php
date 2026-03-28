<?php

namespace Modules\VasAccounting\Utils;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasDocumentSequence;
use Modules\VasAccounting\Entities\VasJournalEntry;
use Modules\VasAccounting\Entities\VasLedgerBalance;
use Modules\VasAccounting\Entities\VasVoucher;
use RuntimeException;

class LedgerPostingUtil
{
    public function normalizeLines(array $lines): Collection
    {
        return collect($lines)
            ->map(function ($line) {
                $line['debit'] = round((float) ($line['debit'] ?? 0), 4);
                $line['credit'] = round((float) ($line['credit'] ?? 0), 4);

                return $line;
            })
            ->filter(fn ($line) => (($line['debit'] ?? 0) > 0) || (($line['credit'] ?? 0) > 0))
            ->values();
    }

    public function assertBalanced(Collection $lines): void
    {
        $debit = round((float) $lines->sum('debit'), 4);
        $credit = round((float) $lines->sum('credit'), 4);

        if (abs($debit - $credit) > 0.0001) {
            throw new RuntimeException("Unbalanced VAS voucher payload. Debit [{$debit}] does not equal credit [{$credit}].");
        }
    }

    public function buildSourceHash(array $payload, Collection $lines): string
    {
        return md5(json_encode([
            'voucher_type' => $payload['voucher_type'] ?? null,
            'module_area' => $payload['module_area'] ?? null,
            'document_type' => $payload['document_type'] ?? null,
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'posting_date' => Carbon::parse($payload['posting_date'])->toDateString(),
            'lines' => $lines->all(),
            'reference' => $payload['reference'] ?? null,
        ]));
    }

    public function validateVoucherStatus(string $status): string
    {
        $allowedStatuses = array_keys((array) config('vasaccounting.document_statuses', []));
        if (! in_array($status, $allowedStatuses, true)) {
            throw new RuntimeException("Unsupported VAS voucher status [{$status}].");
        }

        return $status;
    }

    public function nextVoucherNumber(int $businessId, string $sequenceKey): string
    {
        return DB::transaction(function () use ($businessId, $sequenceKey) {
            $sequence = VasDocumentSequence::query()
                ->where('business_id', $businessId)
                ->where('sequence_key', $sequenceKey)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = VasDocumentSequence::create([
                    'business_id' => $businessId,
                    'sequence_key' => $sequenceKey,
                    'prefix' => strtoupper(substr($sequenceKey, 0, 3)),
                    'next_number' => 1,
                    'padding' => 5,
                    'reset_frequency' => 'yearly',
                    'is_active' => true,
                ]);
            }

            $currentNumber = (int) $sequence->next_number;
            $sequence->next_number = $currentNumber + 1;
            $sequence->save();

            return sprintf('%s-%s', $sequence->prefix, str_pad((string) $currentNumber, (int) $sequence->padding, '0', STR_PAD_LEFT));
        });
    }

    public function publishVoucher(VasVoucher $voucher, ?Carbon $postingDate = null): void
    {
        $postingDate = $postingDate ?: Carbon::parse($voucher->posting_date);
        $period = VasAccountingPeriod::findOrFail((int) $voucher->accounting_period_id);

        if (in_array($period->status, ['soft_locked', 'closed'], true)) {
            throw new RuntimeException("VAS accounting period [{$period->name}] is locked for posting.");
        }

        if ($voucher->journals()->exists()) {
            throw new RuntimeException("Voucher [{$voucher->voucher_no}] already has posted journal entries.");
        }

        foreach ($voucher->lines()->orderBy('line_no')->get() as $voucherLine) {
            VasJournalEntry::create([
                'business_id' => (int) $voucher->business_id,
                'accounting_period_id' => (int) $voucher->accounting_period_id,
                'voucher_id' => (int) $voucher->id,
                'voucher_line_id' => (int) $voucherLine->id,
                'account_id' => (int) $voucherLine->account_id,
                'business_location_id' => $voucherLine->business_location_id,
                'contact_id' => $voucherLine->contact_id,
                'employee_id' => $voucherLine->employee_id,
                'department_id' => $voucherLine->department_id,
                'cost_center_id' => $voucherLine->cost_center_id,
                'project_id' => $voucherLine->project_id,
                'product_id' => $voucherLine->product_id,
                'warehouse_id' => $voucherLine->warehouse_id,
                'asset_id' => $voucherLine->asset_id,
                'contract_id' => $voucherLine->contract_id,
                'budget_id' => $voucherLine->budget_id,
                'tax_code_id' => $voucherLine->tax_code_id,
                'posting_date' => $postingDate->toDateString(),
                'debit' => $voucherLine->debit,
                'credit' => $voucherLine->credit,
                'description' => $voucherLine->description,
                'meta' => $voucherLine->meta,
            ]);
        }

        $this->syncLedgerBalances(
            (int) $voucher->business_id,
            (int) $voucher->accounting_period_id,
            $voucher->lines()->pluck('account_id')->map(fn ($id) => (int) $id)->unique()->all()
        );
    }

    public function syncLedgerBalances(int $businessId, int $periodId, array $accountIds): void
    {
        $period = VasAccountingPeriod::findOrFail($periodId);

        foreach ($accountIds as $accountId) {
            $openingDebit = (float) VasJournalEntry::query()
                ->where('business_id', $businessId)
                ->where('account_id', $accountId)
                ->whereDate('posting_date', '<', $period->start_date)
                ->sum('debit');

            $openingCredit = (float) VasJournalEntry::query()
                ->where('business_id', $businessId)
                ->where('account_id', $accountId)
                ->whereDate('posting_date', '<', $period->start_date)
                ->sum('credit');

            $periodDebit = (float) VasJournalEntry::query()
                ->where('business_id', $businessId)
                ->where('account_id', $accountId)
                ->where('accounting_period_id', $periodId)
                ->sum('debit');

            $periodCredit = (float) VasJournalEntry::query()
                ->where('business_id', $businessId)
                ->where('account_id', $accountId)
                ->where('accounting_period_id', $periodId)
                ->sum('credit');

            VasLedgerBalance::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'accounting_period_id' => $periodId,
                    'account_id' => $accountId,
                ],
                [
                    'opening_debit' => round($openingDebit, 4),
                    'opening_credit' => round($openingCredit, 4),
                    'period_debit' => round($periodDebit, 4),
                    'period_credit' => round($periodCredit, 4),
                    'closing_debit' => round($openingDebit + $periodDebit, 4),
                    'closing_credit' => round($openingCredit + $periodCredit, 4),
                    'last_posted_at' => now(),
                ]
            );
        }
    }

    public function moduleAreaForPayload(array $payload): string
    {
        if (! empty($payload['module_area'])) {
            return (string) $payload['module_area'];
        }

        $sourceType = (string) ($payload['source_type'] ?? '');

        return (string) config("vasaccounting.module_area_by_source.{$sourceType}", 'accounting');
    }

    public function documentTypeForPayload(array $payload): string
    {
        if (! empty($payload['document_type'])) {
            return (string) $payload['document_type'];
        }

        return (string) ($payload['voucher_type'] ?? 'general_journal');
    }
}
