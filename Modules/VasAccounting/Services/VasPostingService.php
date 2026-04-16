<?php

namespace Modules\VasAccounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\StorageManager\Entities\StorageDocumentLink;
use Modules\VasAccounting\Entities\VasDocumentApproval;
use Modules\VasAccounting\Entities\VasDocumentAttachment;
use Modules\VasAccounting\Entities\VasDocumentAuditLog;
use Modules\VasAccounting\Entities\VasInventoryDocument;
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
        protected LedgerPostingUtil $ledgerPostingUtil,
        protected ?DocumentApprovalService $documentApprovalService = null,
        protected ?ExchangeRateService $exchangeRateService = null,
        protected ?ComplianceProfileService $complianceProfileService = null
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

            if (! empty($context['is_deleted'])) {
                return $this->cleanupDeletedSourceDocument($sourceType, $sourceId, $context, $sourceDocument);
            }

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
        $compliance = $this->vasUtil->complianceCompletionStatus($settings);
        if (! ((bool) ($compliance['is_complete'] ?? false))) {
            throw new RuntimeException('Compliance setup is incomplete. Complete setup checkpoints before posting.');
        }
        if (! $this->vasUtil->isPostingMapComplete($settings)) {
            throw new RuntimeException(__('vasaccounting::lang.posting_map_incomplete'));
        }

        $payload['currency_code'] = strtoupper((string) ($payload['currency_code'] ?? $settings->book_currency ?? config('vasaccounting.book_currency', 'VND')));
        $payload['status'] = $this->ledgerPostingUtil->validateVoucherStatus((string) ($payload['status'] ?? 'posted'));
        $payload['exchange_rate'] = $this->resolvePayloadExchangeRate($settings, $payload);
        $this->ensureImmediatePostingAllowed($payload);

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
        $payload['source_hash'] = $this->ledgerPostingUtil->buildSourceHash($payload, $lines);

        return DB::transaction(function () use ($payload, $lines, $period, $postingDate) {
            $latest = $this->latestSourceVoucher($payload);

            if ($latest && $latest->status === 'posted' && $latest->source_hash === $payload['source_hash']) {
                return $latest->load('lines');
            }

            if ($latest && $latest->status === 'posted' && $latest->source_hash !== $payload['source_hash']) {
                $this->createReversalVoucher($latest, (int) ($payload['created_by'] ?? 0));
            }

            $coexistenceDuplicate = $this->coexistenceDuplicateVoucher($payload);
            if ($coexistenceDuplicate) {
                return $coexistenceDuplicate->load('lines');
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

        if (! $this->approvalService()->canPostVoucher($voucher)) {
            throw new RuntimeException("Voucher [{$voucher->voucher_no}] must be approved before posting.");
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
        $status = $this->voucherReversalStatus($voucher);

        if (! ($status['allowed'] ?? false)) {
            throw new RuntimeException((string) ($status['reason'] ?? __('vasaccounting::lang.manual_voucher_reverse_not_allowed')));
        }

        return DB::transaction(function () use ($voucher, $userId) {
            return $this->createReversalVoucher($voucher->fresh(['lines']), $userId);
        });
    }

    public function voucherReversalStatus(VasVoucher $voucher): array
    {
        if ((string) $voucher->status !== 'posted') {
            return $this->blockedReversalStatus('status_not_posted', __('vasaccounting::lang.manual_voucher_reverse_requires_posted'));
        }

        if ((bool) $voucher->is_reversal || $this->isReversalSourceType((string) $voucher->source_type)) {
            return $this->blockedReversalStatus('reversal_chain_blocked', __('vasaccounting::lang.manual_voucher_reverse_nested_blocked'));
        }

        return [
            'allowed' => true,
            'code' => null,
            'reason' => null,
            'meta' => [],
        ];
    }

    public function draftVoucherDeletionStatus(VasVoucher $voucher): array
    {
        if ((string) $voucher->status !== 'draft') {
            return $this->blockedDeletionStatus('status_not_draft', __('vasaccounting::lang.manual_voucher_delete_requires_draft'));
        }

        if ((bool) $voucher->is_system_generated) {
            return $this->blockedDeletionStatus('system_generated', __('vasaccounting::lang.manual_voucher_delete_system_generated_blocked'));
        }

        $sourceType = strtolower(trim((string) $voucher->source_type));
        if ($sourceType !== '' && $sourceType !== 'manual') {
            return $this->blockedDeletionStatus('source_linked', __('vasaccounting::lang.manual_voucher_delete_source_linked'));
        }

        if (! empty($voucher->source_id)) {
            return $this->blockedDeletionStatus('source_id_linked', __('vasaccounting::lang.manual_voucher_delete_source_linked'));
        }

        if (! empty($voucher->posted_at) || ! empty($voucher->posted_by) || ! empty($voucher->reversed_at) || ! empty($voucher->reversed_by)) {
            return $this->blockedDeletionStatus('already_processed', __('vasaccounting::lang.manual_voucher_delete_already_processed'));
        }

        if ($voucher->journals()->exists()) {
            return $this->blockedDeletionStatus('journal_linked', __('vasaccounting::lang.manual_voucher_delete_journal_linked'));
        }

        $linkedInventoryDocumentIds = $this->linkedInventoryDocumentIds($voucher);
        if ($linkedInventoryDocumentIds !== []) {
            return $this->blockedDeletionStatus(
                'inventory_document_linked',
                __('vasaccounting::lang.manual_voucher_delete_inventory_linked', ['count' => count($linkedInventoryDocumentIds)]),
                ['linked_inventory_document_ids' => $linkedInventoryDocumentIds]
            );
        }

        if ($this->hasLinkedStorageDocuments($voucher, $linkedInventoryDocumentIds)) {
            return $this->blockedDeletionStatus('storage_document_linked', __('vasaccounting::lang.manual_voucher_delete_storage_linked'));
        }

        return [
            'allowed' => true,
            'code' => null,
            'reason' => null,
            'meta' => [],
        ];
    }

    public function deleteDraftVoucher(VasVoucher $voucher): void
    {
        $status = $this->draftVoucherDeletionStatus($voucher);

        if (! ($status['allowed'] ?? false)) {
            throw new RuntimeException((string) ($status['reason'] ?? __('vasaccounting::lang.manual_voucher_delete_not_allowed')));
        }

        DB::transaction(function () use ($voucher) {
            $this->deleteVoucherRecords($voucher);
        });
    }

    public function deleteDraftSourceVouchers(string $sourceType, int $sourceId, int $businessId): int
    {
        $vouchers = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->orderByDesc('version_no')
            ->get();

        if ($vouchers->isEmpty()) {
            return 0;
        }

        foreach ($vouchers as $voucher) {
            $status = $this->draftSourceVoucherDeletionStatus($voucher, $sourceType, $sourceId);

            if (! ($status['allowed'] ?? false)) {
                throw new RuntimeException((string) ($status['reason'] ?? __('vasaccounting::lang.inventory_document_delete_not_allowed')));
            }
        }

        DB::transaction(function () use ($vouchers) {
            foreach ($vouchers as $voucher) {
                $this->deleteVoucherRecords($voucher);
            }
        });

        return $vouchers->count();
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

    protected function cleanupDeletedSourceDocument(string $sourceType, int $sourceId, array $context, $sourceDocument): VasVoucher
    {
        $businessId = $this->resolveDeletedSourceBusinessId($context, $sourceDocument);
        if ($businessId <= 0) {
            throw new RuntimeException('VAS delete cleanup is missing business_id.');
        }

        $sourceVouchers = VasVoucher::query()
            ->with('lines')
            ->where('business_id', $businessId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->orderByDesc('version_no')
            ->get();

        $placeholder = $sourceVouchers->first() ?: $this->makeDeletedSourcePlaceholderVoucher($businessId, $sourceType, $sourceId);

        if ($sourceVouchers->isEmpty()) {
            $this->markPostingFailuresResolved(
                $businessId,
                $sourceType,
                $sourceId,
                $this->resolveDeletedSourceUserId($context, $sourceDocument)
            );

            return $placeholder;
        }

        $reversalVouchers = VasVoucher::query()
            ->with('lines')
            ->where('business_id', $businessId)
            ->whereIn('reversed_voucher_id', $sourceVouchers->pluck('id')->all())
            ->get();

        $vouchersToDelete = $sourceVouchers
            ->concat($reversalVouchers)
            ->unique(fn (VasVoucher $voucher) => (int) $voucher->id)
            ->values();

        $accountIdsByPeriod = [];
        foreach ($vouchersToDelete as $voucher) {
            $periodId = (int) $voucher->accounting_period_id;
            if ($periodId <= 0) {
                continue;
            }

            foreach ($voucher->lines as $line) {
                $accountId = (int) $line->account_id;
                if ($accountId <= 0) {
                    continue;
                }

                $accountIdsByPeriod[$periodId][$accountId] = true;
            }
        }

        DB::transaction(function () use ($vouchersToDelete, $accountIdsByPeriod, $businessId, $sourceType, $sourceId, $context, $sourceDocument) {
            foreach ($vouchersToDelete as $voucher) {
                $this->deleteVoucherRecords($voucher);
            }

            foreach ($accountIdsByPeriod as $periodId => $accountIdMap) {
                $this->syncLedgerBalances($businessId, (int) $periodId, array_map('intval', array_keys($accountIdMap)));
            }

            $this->markPostingFailuresResolved(
                $businessId,
                $sourceType,
                $sourceId,
                $this->resolveDeletedSourceUserId($context, $sourceDocument)
            );
        });

        return $placeholder;
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

    protected function resolvePayloadExchangeRate($settings, array $payload): float
    {
        $bookCurrency = strtoupper((string) ($settings->book_currency ?? config('vasaccounting.book_currency', 'VND')));
        $currencyCode = strtoupper((string) ($payload['currency_code'] ?? $bookCurrency));
        $providedRate = round((float) ($payload['exchange_rate'] ?? 0), 8);

        if ($currencyCode === '' || $currencyCode === $bookCurrency) {
            return 1.0;
        }

        if ($providedRate > 0) {
            return $providedRate;
        }

        return $this->exchangeRateService()->resolveRate(
            (int) ($payload['business_id'] ?? 0),
            $currencyCode,
            $bookCurrency,
            $payload['document_date'] ?? $payload['posting_date'] ?? null
        );
    }

    protected function ensureImmediatePostingAllowed(array $payload): void
    {
        if ((string) ($payload['status'] ?? 'draft') !== 'posted') {
            return;
        }

        $meta = (array) ($payload['meta'] ?? []);
        $context = [
            'document_family' => data_get($meta, 'document_family'),
            'source_type' => $payload['source_type'] ?? null,
            'module_area' => $payload['module_area'] ?? null,
            'document_type' => $payload['document_type'] ?? ($payload['voucher_type'] ?? null),
            'business_location_id' => $payload['business_location_id'] ?? null,
            'currency_code' => $payload['currency_code'] ?? null,
            'amount' => max(
                round((float) collect((array) ($payload['lines'] ?? []))->sum('debit'), 4),
                round((float) collect((array) ($payload['lines'] ?? []))->sum('credit'), 4)
            ),
            'requires_approval' => data_get($meta, 'lifecycle.requires_approval', data_get($meta, 'approval.requires_approval')),
        ];
        $documentFamily = $this->approvalRuleService()->documentFamilyForContext($context);

        if ($this->approvalRuleService()->requiresApproval((int) ($payload['business_id'] ?? 0), $documentFamily, $context)) {
            throw new RuntimeException('VAS voucher payload cannot be posted immediately while approval is required.');
        }
    }

    protected function coexistenceDuplicateVoucher(array $payload): ?VasVoucher
    {
        $businessEventUid = trim((string) data_get((array) ($payload['meta'] ?? []), 'coexistence.business_event_uid', ''));
        if ($businessEventUid === '') {
            return null;
        }

        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return null;
        }

        return VasVoucher::query()
            ->where('business_id', (int) ($payload['business_id'] ?? 0))
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.coexistence.business_event_uid')) = ?", [$businessEventUid])
            ->when(! empty($payload['source_type']) && ! empty($payload['source_id']), function ($query) use ($payload) {
                $query->where(function ($nested) use ($payload) {
                    $nested->where('source_type', '!=', (string) $payload['source_type'])
                        ->orWhere('source_id', '!=', (int) $payload['source_id'])
                        ->orWhereNull('source_id');
                });
            })
            ->orderByDesc('version_no')
            ->first();
    }

    protected function linkedInventoryDocumentIds(VasVoucher $voucher): array
    {
        if (! Schema::hasTable('vas_inventory_documents')) {
            return [];
        }

        return VasInventoryDocument::query()
            ->where('business_id', (int) $voucher->business_id)
            ->where(function ($query) use ($voucher) {
                $query->where('posted_voucher_id', (int) $voucher->id)
                    ->orWhere('reversal_voucher_id', (int) $voucher->id);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function hasLinkedStorageDocuments(VasVoucher $voucher, array $linkedInventoryDocumentIds = []): bool
    {
        if (! class_exists(StorageDocumentLink::class) || ! Schema::hasTable('storage_document_links')) {
            return false;
        }

        $query = StorageDocumentLink::query()
            ->where('business_id', (int) $voucher->business_id)
            ->where('linked_system', 'vas');

        if ($linkedInventoryDocumentIds !== []) {
            $query->where(function ($linkQuery) use ($voucher, $linkedInventoryDocumentIds) {
                $linkQuery->where(function ($inventoryQuery) use ($linkedInventoryDocumentIds) {
                    $inventoryQuery->where('linked_type', 'vas_inventory_document')
                        ->whereIn('linked_id', $linkedInventoryDocumentIds);
                })->orWhere(function ($voucherQuery) use ($voucher) {
                    $voucherQuery->whereIn('linked_type', ['vas_voucher', 'voucher'])
                        ->where('linked_id', (int) $voucher->id);
                });
            });
        } else {
            $query->whereIn('linked_type', ['vas_voucher', 'voucher'])
                ->where('linked_id', (int) $voucher->id);
        }

        return $query->exists();
    }

    protected function draftSourceVoucherDeletionStatus(VasVoucher $voucher, string $sourceType, int $sourceId): array
    {
        if ((string) $voucher->source_type !== $sourceType || (int) $voucher->source_id !== $sourceId) {
            return $this->blockedDeletionStatus('source_mismatch', __('vasaccounting::lang.inventory_document_delete_gl_linked'));
        }

        if ((string) $voucher->status !== 'draft') {
            return $this->blockedDeletionStatus('status_not_draft', __('vasaccounting::lang.inventory_document_delete_gl_linked'));
        }

        if (! empty($voucher->posted_at) || ! empty($voucher->posted_by) || ! empty($voucher->reversed_at) || ! empty($voucher->reversed_by)) {
            return $this->blockedDeletionStatus('already_processed', __('vasaccounting::lang.inventory_document_delete_gl_linked'));
        }

        if ($voucher->journals()->exists()) {
            return $this->blockedDeletionStatus('journal_linked', __('vasaccounting::lang.inventory_document_delete_gl_linked'));
        }

        return [
            'allowed' => true,
            'code' => null,
            'reason' => null,
            'meta' => [],
        ];
    }

    protected function deleteVoucherRecords(VasVoucher $voucher): void
    {
        $businessId = (int) $voucher->business_id;
        $voucherId = (int) $voucher->id;

        VasVoucherLine::query()
            ->where('business_id', $businessId)
            ->where('voucher_id', $voucherId)
            ->delete();

        if (Schema::hasTable('vas_journal_entries')) {
            DB::table('vas_journal_entries')
                ->where('business_id', $businessId)
                ->where('voucher_id', $voucherId)
                ->delete();
        }

        VasDocumentApproval::query()
            ->where('business_id', $businessId)
            ->where('entity_type', VasVoucher::class)
            ->where('entity_id', $voucherId)
            ->delete();

        VasDocumentAuditLog::query()
            ->where('business_id', $businessId)
            ->where('entity_type', VasVoucher::class)
            ->where('entity_id', $voucherId)
            ->delete();

        VasDocumentAttachment::query()
            ->where('business_id', $businessId)
            ->where('entity_type', VasVoucher::class)
            ->where('entity_id', $voucherId)
            ->delete();

        $voucher->delete();
    }

    protected function resolveDeletedSourceBusinessId(array $context, $sourceDocument): int
    {
        $snapshot = (array) ($context['source_snapshot'] ?? []);

        return (int) (($context['business_id'] ?? 0)
            ?: ($snapshot['business_id'] ?? 0)
            ?: ($sourceDocument->business_id ?? 0));
    }

    protected function resolveDeletedSourceUserId(array $context, $sourceDocument): ?int
    {
        $snapshot = (array) ($context['source_snapshot'] ?? []);

        $userId = (int) (($context['created_by'] ?? 0)
            ?: ($snapshot['created_by'] ?? 0)
            ?: ($sourceDocument->created_by ?? 0));

        return $userId > 0 ? $userId : null;
    }

    protected function markPostingFailuresResolved(int $businessId, string $sourceType, int $sourceId, ?int $resolvedBy = null): void
    {
        VasPostingFailure::query()
            ->where('business_id', $businessId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'resolved_by' => $resolvedBy,
            ]);
    }

    protected function makeDeletedSourcePlaceholderVoucher(int $businessId, string $sourceType, int $sourceId): VasVoucher
    {
        $voucher = new VasVoucher();
        $voucher->business_id = $businessId;
        $voucher->source_type = $sourceType;
        $voucher->source_id = $sourceId;
        $voucher->status = 'deleted';

        return $voucher;
    }

    protected function blockedDeletionStatus(string $code, string $reason, array $meta = []): array
    {
        return [
            'allowed' => false,
            'code' => $code,
            'reason' => $reason,
            'meta' => $meta,
        ];
    }

    protected function blockedReversalStatus(string $code, string $reason, array $meta = []): array
    {
        return [
            'allowed' => false,
            'code' => $code,
            'reason' => $reason,
            'meta' => $meta,
        ];
    }

    protected function isReversalSourceType(string $sourceType): bool
    {
        $sourceType = strtolower(trim($sourceType));

        return $sourceType !== '' && str_ends_with($sourceType, '_reversal');
    }

    protected function approvalService(): DocumentApprovalService
    {
        return $this->documentApprovalService ?: app(DocumentApprovalService::class);
    }

    protected function approvalRuleService(): ApprovalRuleService
    {
        return app(ApprovalRuleService::class);
    }

    protected function exchangeRateService(): ExchangeRateService
    {
        return $this->exchangeRateService ?: app(ExchangeRateService::class);
    }
}
