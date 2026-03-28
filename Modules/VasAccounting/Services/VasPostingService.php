<?php

namespace Modules\VasAccounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Entities\VasVoucherLine;
use Modules\VasAccounting\Jobs\ProcessSourceDocumentPostingJob;
use Modules\VasAccounting\Utils\LedgerPostingUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;
use Throwable;

class VasPostingService
{
    public function __construct(
        protected SourceDocumentAdapterManager $adapterManager,
        protected VasAccountingUtil $vasUtil,
        protected LedgerPostingUtil $ledgerPostingUtil
    ) {
    }

    public function queueSourceDocument(string $sourceType, $sourceDocument, array $context = []): void
    {
        $sourceId = method_exists($sourceDocument, 'getKey') ? (int) $sourceDocument->getKey() : (int) ($context['source_id'] ?? 0);
        if ($sourceId <= 0) {
            return;
        }

        dispatch(new ProcessSourceDocumentPostingJob($sourceType, $sourceId, $context));
    }

    public function processSourceDocument(string $sourceType, int $sourceId, array $context = []): VasVoucher
    {
        $sourceDocument = null;

        try {
            $adapter = $this->adapterManager->resolve($sourceType);
            $sourceDocument = $adapter->loadSourceDocument($sourceId, $context);
            $payload = $adapter->toVoucherPayload($sourceDocument, $context);

            return $this->postVoucherPayload($payload);
        } catch (Throwable $exception) {
            $businessId = (int) (($context['business_id'] ?? 0) ?: ($sourceDocument->business_id ?? 0));
            $this->recordFailure($businessId, $sourceType, $sourceId, $context, $exception);
            throw $exception;
        }
    }

    public function postVoucherPayload(array $payload): VasVoucher
    {
        $businessId = (int) ($payload['business_id'] ?? 0);
        if ($businessId <= 0) {
            throw new RuntimeException('VAS voucher payload is missing business_id.');
        }

        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        if (! $this->vasUtil->isPostingMapComplete($settings)) {
            throw new RuntimeException(__('vasaccounting::lang.posting_map_incomplete'));
        }

        $lines = $this->ledgerPostingUtil->normalizeLines((array) ($payload['lines'] ?? []));
        if ($lines->isEmpty()) {
            throw new RuntimeException('VAS voucher payload has no postable lines.');
        }

        $this->assertBalanced($lines);

        $postingDate = Carbon::parse($payload['posting_date']);
        $period = $this->vasUtil->resolvePeriodForDate($businessId, $postingDate);
        if (in_array($period->status, ['soft_locked', 'closed'], true)) {
            throw new RuntimeException("VAS accounting period [{$period->name}] is locked for posting.");
        }

        $payload['module_area'] = $this->ledgerPostingUtil->moduleAreaForPayload($payload);
        $payload['document_type'] = $this->ledgerPostingUtil->documentTypeForPayload($payload);
        $payload['status'] = $this->ledgerPostingUtil->validateVoucherStatus((string) ($payload['status'] ?? 'posted'));
        $payload['source_hash'] = $this->ledgerPostingUtil->buildSourceHash($payload, $lines);

        return DB::transaction(function () use ($payload, $lines, $period, $postingDate) {
            $latest = $this->latestSourceVoucher($payload);

            if ($latest && $latest->status === 'posted' && $latest->source_hash === $payload['source_hash']) {
                return $latest->load('lines');
            }

            if ($latest && $latest->status === 'posted' && $latest->source_hash !== $payload['source_hash']) {
                $this->createReversalVoucher($latest, (int) ($payload['created_by'] ?? 0));
            }

            $sequenceNo = $this->nextVoucherNumber((int) $payload['business_id'], (string) ($payload['sequence_key'] ?? 'general_journal'));
            $version = $latest ? ((int) $latest->version_no + 1) : 1;
            $status = (string) $payload['status'];

            $voucher = VasVoucher::create([
                'business_id' => (int) $payload['business_id'],
                'accounting_period_id' => (int) $period->id,
                'voucher_no' => $sequenceNo,
                'voucher_type' => (string) ($payload['voucher_type'] ?? 'general_journal'),
                'module_area' => (string) $payload['module_area'],
                'document_type' => (string) $payload['document_type'],
                'sequence_key' => (string) ($payload['sequence_key'] ?? 'general_journal'),
                'source_type' => $payload['source_type'] ?? null,
                'source_id' => $payload['source_id'] ?? null,
                'source_hash' => (string) $payload['source_hash'],
                'transaction_id' => $payload['transaction_id'] ?? null,
                'transaction_payment_id' => $payload['transaction_payment_id'] ?? null,
                'contact_id' => $payload['contact_id'] ?? null,
                'business_location_id' => $payload['business_location_id'] ?? null,
                'posting_date' => $postingDate->toDateString(),
                'document_date' => Carbon::parse($payload['document_date'])->toDateString(),
                'description' => $payload['description'] ?? null,
                'reference' => $payload['reference'] ?? null,
                'external_reference' => $payload['external_reference'] ?? null,
                'status' => $status,
                'currency_code' => $payload['currency_code'] ?? 'VND',
                'exchange_rate' => $payload['exchange_rate'] ?? 1,
                'total_debit' => $lines->sum('debit'),
                'total_credit' => $lines->sum('credit'),
                'is_system_generated' => (bool) ($payload['is_system_generated'] ?? true),
                'is_historical_import' => (bool) ($payload['is_historical_import'] ?? false),
                'is_reversal' => false,
                'version_no' => $version,
                'meta' => $payload['meta'] ?? null,
                'posted_at' => $status === 'posted' ? now() : null,
                'posted_by' => $status === 'posted' ? ($payload['created_by'] ?? null) : null,
                'submitted_at' => in_array($status, ['pending_approval', 'approved', 'posted'], true) ? now() : null,
                'submitted_by' => in_array($status, ['pending_approval', 'approved', 'posted'], true) ? ($payload['created_by'] ?? null) : null,
                'approved_at' => in_array($status, ['approved', 'posted'], true) ? now() : null,
                'approved_by' => in_array($status, ['approved', 'posted'], true) ? ($payload['created_by'] ?? null) : null,
                'created_by' => $payload['created_by'] ?? null,
            ]);

            $lineNo = 1;
            foreach ($lines as $line) {
                VasVoucherLine::create([
                    'business_id' => (int) $payload['business_id'],
                    'voucher_id' => (int) $voucher->id,
                    'line_no' => $lineNo++,
                    'account_id' => (int) $line['account_id'],
                    'business_location_id' => $line['business_location_id'] ?? ($payload['business_location_id'] ?? null),
                    'contact_id' => $line['contact_id'] ?? ($payload['contact_id'] ?? null),
                    'employee_id' => $line['employee_id'] ?? null,
                    'department_id' => $line['department_id'] ?? null,
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'project_id' => $line['project_id'] ?? null,
                    'product_id' => $line['product_id'] ?? null,
                    'warehouse_id' => $line['warehouse_id'] ?? null,
                    'asset_id' => $line['asset_id'] ?? null,
                    'contract_id' => $line['contract_id'] ?? null,
                    'budget_id' => $line['budget_id'] ?? null,
                    'tax_code_id' => $line['tax_code_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'meta' => $line['meta'] ?? null,
                ]);
            }

            if ($status === 'posted') {
                $this->ledgerPostingUtil->publishVoucher($voucher->fresh('lines'), $postingDate);
            }

            VasPostingFailure::query()
                ->where('business_id', (int) $payload['business_id'])
                ->where('source_type', $payload['source_type'] ?? null)
                ->where('source_id', $payload['source_id'] ?? null)
                ->whereNull('resolved_at')
                ->update([
                    'resolved_at' => now(),
                    'resolved_by' => $payload['created_by'] ?? null,
                ]);

            return $voucher->fresh('lines');
        });
    }

    public function postExistingVoucher(VasVoucher $voucher, int $userId): VasVoucher
    {
        if ($voucher->status === 'posted') {
            return $voucher->fresh('lines');
        }

        if (in_array($voucher->status, ['cancelled', 'reversed'], true)) {
            throw new RuntimeException("Voucher [{$voucher->voucher_no}] cannot be posted from status [{$voucher->status}].");
        }

        return DB::transaction(function () use ($voucher, $userId) {
            $voucher = $voucher->fresh('lines');
            $this->ledgerPostingUtil->publishVoucher($voucher);

            $voucher->status = 'posted';
            $voucher->posted_at = now();
            $voucher->posted_by = $userId;
            $voucher->submitted_at = $voucher->submitted_at ?: now();
            $voucher->submitted_by = $voucher->submitted_by ?: $userId;
            $voucher->approved_at = $voucher->approved_at ?: now();
            $voucher->approved_by = $voucher->approved_by ?: $userId;
            $voucher->save();

            return $voucher->fresh('lines');
        });
    }

    public function reverseVoucher(VasVoucher $voucher, int $userId): VasVoucher
    {
        if ($voucher->status !== 'posted') {
            throw new RuntimeException("Only posted vouchers can be reversed. Voucher [{$voucher->voucher_no}] is [{$voucher->status}].");
        }

        return DB::transaction(function () use ($voucher, $userId) {
            return $this->createReversalVoucher($voucher->fresh(['lines']), $userId);
        });
    }

    public function replayFailure(VasPostingFailure $failure): ?VasVoucher
    {
        $voucher = $this->processSourceDocument((string) $failure->source_type, (int) $failure->source_id, (array) $failure->payload);
        $failure->resolved_at = now();
        $failure->save();

        return $voucher;
    }

    protected function latestSourceVoucher(array $payload): ?VasVoucher
    {
        if (empty($payload['source_type']) || empty($payload['source_id'])) {
            return null;
        }

        return VasVoucher::query()
            ->where('business_id', (int) $payload['business_id'])
            ->where('source_type', (string) $payload['source_type'])
            ->where('source_id', (int) $payload['source_id'])
            ->orderByDesc('version_no')
            ->first();
    }

    protected function createReversalVoucher(VasVoucher $voucher, int $userId): VasVoucher
    {
        if ($voucher->reversed_at) {
            return $voucher;
        }

        if ($voucher->status !== 'posted') {
            throw new RuntimeException("Voucher [{$voucher->voucher_no}] must be posted before reversal.");
        }

        $reversalLines = $voucher->lines->map(function (VasVoucherLine $line) {
            return [
                'account_id' => (int) $line->account_id,
                'business_location_id' => $line->business_location_id,
                'contact_id' => $line->contact_id,
                'employee_id' => $line->employee_id,
                'department_id' => $line->department_id,
                'cost_center_id' => $line->cost_center_id,
                'project_id' => $line->project_id,
                'product_id' => $line->product_id,
                'warehouse_id' => $line->warehouse_id,
                'asset_id' => $line->asset_id,
                'contract_id' => $line->contract_id,
                'budget_id' => $line->budget_id,
                'tax_code_id' => $line->tax_code_id,
                'description' => 'Reversal: ' . ($line->description ?: 'Source line'),
                'debit' => (float) $line->credit,
                'credit' => (float) $line->debit,
                'meta' => $line->meta,
            ];
        });

        $reversal = $this->postVoucherPayload([
            'business_id' => (int) $voucher->business_id,
            'voucher_type' => 'reversal',
            'module_area' => $voucher->module_area ?: 'accounting',
            'document_type' => 'reversal',
            'sequence_key' => 'general_journal',
            'source_type' => $voucher->source_type ? $voucher->source_type . '_reversal' : 'manual_reversal',
            'source_id' => $voucher->source_id ? ((int) $voucher->source_id * 1000 + (int) $voucher->id) : (int) $voucher->id,
            'transaction_id' => $voucher->transaction_id,
            'transaction_payment_id' => $voucher->transaction_payment_id,
            'contact_id' => $voucher->contact_id,
            'business_location_id' => $voucher->business_location_id,
            'posting_date' => $voucher->posting_date,
            'document_date' => $voucher->document_date,
            'description' => 'Automatic reversal for voucher ' . $voucher->voucher_no,
            'reference' => $voucher->voucher_no,
            'status' => 'posted',
            'currency_code' => $voucher->currency_code,
            'exchange_rate' => (float) $voucher->exchange_rate,
            'created_by' => $userId,
            'is_system_generated' => true,
            'meta' => [
                'reversal_of_voucher_id' => (int) $voucher->id,
            ],
            'lines' => $reversalLines->all(),
        ]);

        $reversal->update([
            'is_reversal' => true,
            'reversed_voucher_id' => (int) $voucher->id,
        ]);

        $voucher->update([
            'status' => 'reversed',
            'reversed_at' => now(),
            'reversed_by' => $userId,
            'reversed_voucher_id' => (int) $reversal->id,
        ]);

        return $reversal;
    }

    protected function nextVoucherNumber(int $businessId, string $sequenceKey): string
    {
        return $this->ledgerPostingUtil->nextVoucherNumber($businessId, $sequenceKey);
    }

    protected function assertBalanced(Collection $lines): void
    {
        $this->ledgerPostingUtil->assertBalanced($lines);
    }

    protected function syncLedgerBalances(int $businessId, int $periodId, array $accountIds): void
    {
        $this->ledgerPostingUtil->syncLedgerBalances($businessId, $periodId, $accountIds);
    }

    protected function recordFailure(int $businessId, string $sourceType, int $sourceId, array $payload, Throwable $exception): void
    {
        if ($businessId <= 0) {
            return;
        }

        $failure = VasPostingFailure::firstOrNew([
            'business_id' => $businessId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'resolved_at' => null,
        ]);

        $failure->listener = static::class;
        $failure->payload = $payload;
        $failure->error_message = $exception->getMessage();
        $failure->error_trace = substr($exception->getTraceAsString(), 0, 65000);
        $failure->failed_at = now();
        $failure->retry_count = (int) $failure->retry_count + 1;
        $failure->save();
    }
}
