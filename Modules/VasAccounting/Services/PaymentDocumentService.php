<?php

namespace Modules\VasAccounting\Services;

use App\Utils\TransactionUtil;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\VasAccounting\Entities\VasBankAccount;
use Modules\VasAccounting\Entities\VasCashbook;
use Modules\VasAccounting\Entities\VasPayableAllocation;
use Modules\VasAccounting\Entities\VasReceivableAllocation;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Entities\VasVoucherLine;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;
use Modules\VasAccounting\Utils\LedgerPostingUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;

class PaymentDocumentService
{
    public function __construct(
        protected VasPostingService $postingService,
        protected DocumentApprovalService $approvalService,
        protected EnterpriseFinanceReportUtil $enterpriseReportUtil,
        protected VasAccountingUtil $vasUtil,
        protected LedgerPostingUtil $ledgerPostingUtil,
        protected NativeDocumentMetaBuilder $metaBuilder,
        protected TransactionUtil $transactionUtil
    ) {
    }

    public function queryForBusiness(int $businessId): Builder
    {
        return VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('source_type', 'native_payment')
            ->orderByDesc('posting_date')
            ->orderByDesc('id');
    }

    public function paginateNativePayments(int $businessId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->queryForBusiness($businessId)
            ->with(['lines', 'approvals', 'auditLogs'])
            ->paginate($perPage);
    }

    public function findNativePayment(int $businessId, int $voucherId): VasVoucher
    {
        return VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('source_type', 'native_payment')
            ->with(['lines.account', 'approvals', 'auditLogs', 'attachments'])
            ->findOrFail($voucherId);
    }

    public function createDraft(int $businessId, array $data, int $userId): VasVoucher
    {
        return $this->postingService->postVoucherPayload(
            $this->buildPayload($businessId, $data, $userId)
        );
    }

    public function updateDraft(VasVoucher $voucher, array $data, int $userId): VasVoucher
    {
        $this->assertEditable($voucher);

        $payload = $this->buildPayload((int) $voucher->business_id, $data, $userId, $voucher);
        $lines = $this->ledgerPostingUtil->normalizeLines((array) ($payload['lines'] ?? []));
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'amount' => 'Provide a payment amount greater than zero.',
            ]);
        }

        $this->ledgerPostingUtil->assertBalanced($lines);

        return DB::transaction(function () use ($voucher, $payload, $lines) {
            $voucher->update([
                'voucher_type' => (string) $payload['voucher_type'],
                'module_area' => (string) $payload['module_area'],
                'document_type' => (string) $payload['document_type'],
                'sequence_key' => (string) $payload['sequence_key'],
                'transaction_id' => $payload['transaction_id'] ?? null,
                'contact_id' => $payload['contact_id'] ?? null,
                'business_location_id' => $payload['business_location_id'] ?? null,
                'posting_date' => $payload['posting_date'],
                'document_date' => $payload['document_date'],
                'description' => $payload['description'] ?? null,
                'reference' => $payload['reference'] ?? null,
                'external_reference' => $payload['external_reference'] ?? null,
                'currency_code' => $payload['currency_code'] ?? 'VND',
                'exchange_rate' => $payload['exchange_rate'] ?? 1,
                'total_debit' => $lines->sum('debit'),
                'total_credit' => $lines->sum('credit'),
                'source_hash' => $this->ledgerPostingUtil->buildSourceHash($payload, $lines),
                'meta' => $payload['meta'] ?? null,
                'updated_at' => now(),
            ]);

            $voucher->lines()->delete();

            $lineNo = 1;
            foreach ($lines as $line) {
                VasVoucherLine::create([
                    'business_id' => (int) $voucher->business_id,
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

            return $voucher->fresh('lines');
        });
    }

    public function submit(VasVoucher $voucher, int $userId): VasVoucher
    {
        return $this->approvalService->submitVoucher($voucher, $userId);
    }

    public function approve(VasVoucher $voucher, int $userId, ?string $comments = null): VasVoucher
    {
        return $this->approvalService->approveVoucher($voucher, $userId, $comments);
    }

    public function reject(VasVoucher $voucher, int $userId, ?string $comments = null): VasVoucher
    {
        return $this->approvalService->rejectVoucher($voucher, $userId, $comments);
    }

    public function cancel(VasVoucher $voucher, int $userId, ?string $comments = null): VasVoucher
    {
        return $this->approvalService->cancelVoucher($voucher, $userId, $comments);
    }

    public function post(VasVoucher $voucher, int $userId): VasVoucher
    {
        $postedVoucher = $this->postingService->postExistingVoucher($voucher, $userId);
        $this->replaceSettlementAllocations($postedVoucher);
        $this->syncLegacyPaymentStatuses($postedVoucher);

        return $postedVoucher->fresh('lines');
    }

    public function reverse(VasVoucher $voucher, int $userId): VasVoucher
    {
        $this->removeSettlementAllocations($voucher);
        $reversal = $this->postingService->reverseVoucher($voucher, $userId);
        $this->syncLegacyPaymentStatuses($voucher);

        return $reversal;
    }

    protected function buildPayload(int $businessId, array $data, int $userId, ?VasVoucher $voucher = null): array
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $paymentKind = (string) ($data['payment_kind'] ?? 'bank_payment');
        $paymentMethod = (string) ($data['payment_instrument'] ?? $data['payment_method'] ?? ($this->usesBankLedger($paymentKind) ? 'bank_transfer' : 'cash'));
        $isPayment = $this->isOutgoing($paymentKind);
        $amount = round((float) ($data['amount'] ?? 0), 4);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $contactId = ! empty($data['contact_id']) ? (int) $data['contact_id'] : null;
        $locationId = ! empty($data['business_location_id']) ? (int) $data['business_location_id'] : null;
        $documentDate = (string) ($data['document_date'] ?? $data['posting_date'] ?? now()->toDateString());
        $postingDate = (string) ($data['posting_date'] ?? $documentDate);
        $legacyTransactionId = ! empty($data['legacy_transaction_id'])
            ? (int) $data['legacy_transaction_id']
            : ((int) ($voucher?->transaction_id ?? 0) ?: null);
        $reference = trim((string) ($data['reference'] ?? ''));
        if ($reference === '') {
            $reference = $this->generateLegacyReference($paymentKind, $businessId);
        }

        $tenderAccountId = $this->resolveTenderAccountId($businessId, $paymentKind, $data, $settings);
        $controlAccountId = (int) ((array) $settings->posting_map)[$isPayment ? 'accounts_payable' : 'accounts_receivable'];
        if ($controlAccountId <= 0 || $tenderAccountId <= 0) {
            throw new RuntimeException('VAS posting map is incomplete for native payment documents.');
        }

        $lines = $isPayment
            ? [
                $this->buildLine($controlAccountId, 'Settlement against payable', $amount, 0, $locationId, $contactId),
                $this->buildLine($tenderAccountId, 'Cash or bank outflow', 0, $amount, $locationId, $contactId),
            ]
            : [
                $this->buildLine($tenderAccountId, 'Cash or bank inflow', $amount, 0, $locationId, $contactId),
                $this->buildLine($controlAccountId, 'Settlement against receivable', 0, $amount, $locationId, $contactId),
            ];

        $legacyLinks = array_replace((array) data_get((array) ($voucher?->meta ?? []), 'legacy_links', []), [
            'transaction_id' => $legacyTransactionId,
            'payment_ref_no' => $reference,
        ]);

        $meta = array_replace_recursive(
            $this->metaBuilder->buildPaymentMeta([
                'direction' => $isPayment ? 'payment' : 'receipt',
                'payment_kind' => $paymentKind,
                'contact_id' => $contactId,
                'cashbook_id' => $data['cashbook_id'] ?? null,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'payment_instrument' => $paymentMethod,
                'external_reference' => $data['external_reference'] ?? null,
                'document_date' => $documentDate,
                'reference' => $reference,
                'requires_approval' => true,
                'legacy_links' => $legacyLinks,
                'legacy_source_type' => ! empty($legacyTransactionId) ? 'transaction' : null,
                'legacy_source_id' => $legacyTransactionId,
                'coexistence_mode' => data_get($this->vasUtil->nativeDocumentFamilies(), 'payment.coexistence_mode', 'parallel'),
                'settlement_targets' => $this->normalizeSettlementTargets((array) ($data['settlement_targets'] ?? []), $isPayment ? 'payable' : 'receivable'),
                'lines' => $lines,
            ]),
            [
                'payment' => [
                    'notes' => $data['notes'] ?? null,
                    'payment_method' => $paymentMethod,
                    'legacy_reference' => $reference,
                ],
            ]
        );

        return [
            'business_id' => $businessId,
            'voucher_type' => $paymentKind,
            'sequence_key' => $paymentKind,
            'source_type' => 'native_payment',
            'source_id' => null,
            'transaction_id' => $legacyTransactionId,
            'contact_id' => $contactId,
            'business_location_id' => $locationId,
            'posting_date' => $postingDate,
            'document_date' => $documentDate,
            'description' => (string) ($data['description'] ?? ucfirst(str_replace('_', ' ', $paymentKind)) . ' ' . $reference),
            'reference' => $reference,
            'external_reference' => $data['external_reference'] ?? null,
            'status' => 'draft',
            'currency_code' => strtoupper((string) ($data['currency_code'] ?? $settings->book_currency ?? config('vasaccounting.book_currency', 'VND'))),
            'exchange_rate' => $data['exchange_rate'] ?? null,
            'created_by' => $userId,
            'is_system_generated' => false,
            'module_area' => 'cash_bank',
            'document_type' => $paymentKind,
            'meta' => $meta,
            'lines' => $lines,
        ];
    }

    protected function resolveTenderAccountId(int $businessId, string $paymentKind, array $data, $settings): int
    {
        if ($this->usesBankLedger($paymentKind)) {
            $bankAccountId = ! empty($data['bank_account_id']) ? (int) $data['bank_account_id'] : 0;
            if ($bankAccountId > 0) {
                $account = VasBankAccount::query()
                    ->where('business_id', $businessId)
                    ->findOrFail($bankAccountId);

                return (int) $account->ledger_account_id;
            }

            return (int) ((array) $settings->posting_map)['bank'];
        }

        $cashbookId = ! empty($data['cashbook_id']) ? (int) $data['cashbook_id'] : 0;
        if ($cashbookId > 0) {
            $cashbook = VasCashbook::query()
                ->where('business_id', $businessId)
                ->findOrFail($cashbookId);

            return (int) $cashbook->cash_account_id;
        }

        return (int) ((array) $settings->posting_map)['cash'];
    }

    protected function replaceSettlementAllocations(VasVoucher $voucher): void
    {
        $targets = collect((array) data_get((array) $voucher->meta, 'payment.settlement_targets', []))
            ->filter(fn ($row) => ! empty($row['target_voucher_id']))
            ->values();

        $isPayment = $this->isOutgoing((string) $voucher->voucher_type);
        $businessId = (int) $voucher->business_id;

        if ($isPayment) {
            VasPayableAllocation::query()
                ->where('business_id', $businessId)
                ->where('payment_voucher_id', (int) $voucher->id)
                ->delete();
        } else {
            VasReceivableAllocation::query()
                ->where('business_id', $businessId)
                ->where('payment_voucher_id', (int) $voucher->id)
                ->delete();
        }

        if ($targets->isEmpty()) {
            return;
        }

        $openItems = $isPayment
            ? $this->enterpriseReportUtil->payableOpenItems($businessId)->keyBy('id')
            : $this->enterpriseReportUtil->receivableOpenItems($businessId)->keyBy('id');
        $paymentItems = $isPayment
            ? $this->enterpriseReportUtil->payablePaymentItems($businessId)->keyBy('id')
            : $this->enterpriseReportUtil->receivableReceiptItems($businessId)->keyBy('id');
        $paymentItem = $paymentItems->get((int) $voucher->id);
        $remaining = round((float) ($paymentItem->available_amount ?? max((float) $voucher->total_debit, (float) $voucher->total_credit)), 4);

        foreach ($targets as $target) {
            if ($remaining <= 0.0001) {
                break;
            }

            $targetVoucherId = (int) $target['target_voucher_id'];
            $openItem = $openItems->get($targetVoucherId);
            if (! $openItem) {
                throw ValidationException::withMessages([
                    'settlement_targets' => 'One or more settlement targets are no longer open.',
                ]);
            }

            $targetAmount = round((float) ($target['amount'] ?? 0), 4);
            if ($targetAmount <= 0) {
                $targetAmount = min((float) ($openItem->outstanding_amount ?? 0), $remaining);
            }

            if ($targetAmount > round((float) ($openItem->outstanding_amount ?? 0), 4) + 0.0001 || $targetAmount > $remaining + 0.0001) {
                throw ValidationException::withMessages([
                    'settlement_targets' => 'Settlement amount exceeds the outstanding balance on one of the target documents.',
                ]);
            }

            if ($isPayment) {
                VasPayableAllocation::create([
                    'business_id' => $businessId,
                    'voucher_id' => (int) $voucher->id,
                    'bill_voucher_id' => $targetVoucherId,
                    'payment_voucher_id' => (int) $voucher->id,
                    'contact_id' => $voucher->contact_id ?: $openItem->contact_id,
                    'allocation_date' => $voucher->posting_date,
                    'amount' => $targetAmount,
                    'meta' => ['source_type' => 'native_payment', 'auto_allocated' => true],
                ]);
            } else {
                VasReceivableAllocation::create([
                    'business_id' => $businessId,
                    'voucher_id' => (int) $voucher->id,
                    'invoice_voucher_id' => $targetVoucherId,
                    'payment_voucher_id' => (int) $voucher->id,
                    'contact_id' => $voucher->contact_id ?: $openItem->contact_id,
                    'allocation_date' => $voucher->posting_date,
                    'amount' => $targetAmount,
                    'meta' => ['source_type' => 'native_payment', 'auto_allocated' => true],
                ]);
            }

            $remaining = round($remaining - $targetAmount, 4);
        }
    }

    protected function removeSettlementAllocations(VasVoucher $voucher): void
    {
        $businessId = (int) $voucher->business_id;

        VasPayableAllocation::query()
            ->where('business_id', $businessId)
            ->where(function ($query) use ($voucher) {
                $query->where('payment_voucher_id', (int) $voucher->id)
                    ->orWhere('bill_voucher_id', (int) $voucher->id);
            })
            ->delete();

        VasReceivableAllocation::query()
            ->where('business_id', $businessId)
            ->where(function ($query) use ($voucher) {
                $query->where('payment_voucher_id', (int) $voucher->id)
                    ->orWhere('invoice_voucher_id', (int) $voucher->id);
            })
            ->delete();
    }

    protected function syncLegacyPaymentStatuses(VasVoucher $voucher): void
    {
        $targetIds = collect([
            $voucher->transaction_id,
            data_get((array) $voucher->meta, 'legacy_links.transaction_id'),
        ])->filter(fn ($id) => (int) $id > 0);

        $targetVoucherIds = collect((array) data_get((array) $voucher->meta, 'payment.settlement_targets', []))
            ->pluck('target_voucher_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($targetVoucherIds->isNotEmpty()) {
            $targetIds = $targetIds->merge(
                VasVoucher::query()
                    ->where('business_id', (int) $voucher->business_id)
                    ->whereIn('id', $targetVoucherIds->all())
                    ->pluck('transaction_id')
                    ->filter(fn ($id) => (int) $id > 0)
                    ->all()
            );
        }

        $targetIds
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->each(fn (int $transactionId) => $this->transactionUtil->updatePaymentStatus($transactionId));
    }

    protected function normalizeSettlementTargets(array $targets, string $targetType): array
    {
        return collect($targets)
            ->map(function (array $target) use ($targetType) {
                $voucherId = (int) ($target['target_voucher_id'] ?? 0);
                if ($voucherId <= 0) {
                    return null;
                }

                return array_filter([
                    'target_type' => $targetType,
                    'target_voucher_id' => $voucherId,
                    'amount' => round((float) ($target['amount'] ?? 0), 4),
                    'legacy_transaction_id' => ! empty($target['legacy_transaction_id']) ? (int) $target['legacy_transaction_id'] : null,
                ], fn ($value) => $value !== null && $value !== '');
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function generateLegacyReference(string $paymentKind, int $businessId): string
    {
        $prefixType = $this->isOutgoing($paymentKind) ? 'purchase_payment' : 'sell_payment';
        $refCount = $this->transactionUtil->setAndGetReferenceCount($prefixType, $businessId);

        return $this->transactionUtil->generateReferenceNumber($prefixType, $refCount, $businessId);
    }

    protected function buildLine(
        int $accountId,
        string $description,
        float $debit,
        float $credit,
        ?int $locationId,
        ?int $contactId
    ): array {
        return [
            'account_id' => $accountId,
            'business_location_id' => $locationId,
            'contact_id' => $contactId,
            'description' => $description,
            'debit' => round($debit, 4),
            'credit' => round($credit, 4),
        ];
    }

    protected function isOutgoing(string $paymentKind): bool
    {
        return in_array($paymentKind, ['cash_payment', 'bank_payment'], true);
    }

    protected function usesBankLedger(string $paymentKind): bool
    {
        return in_array($paymentKind, ['bank_receipt', 'bank_payment'], true);
    }

    protected function assertEditable(VasVoucher $voucher): void
    {
        if ($voucher->source_type !== 'native_payment') {
            throw new RuntimeException('Only native payment documents can be edited from this workflow.');
        }

        if ($voucher->status !== 'draft') {
            throw new RuntimeException("Payment document [{$voucher->voucher_no}] cannot be edited from status [{$voucher->status}].");
        }
    }
}
