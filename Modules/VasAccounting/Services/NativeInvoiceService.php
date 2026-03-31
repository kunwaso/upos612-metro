<?php

namespace Modules\VasAccounting\Services;

use App\BusinessLocation;
use App\Utils\ProductUtil;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Entities\VasPayableAllocation;
use Modules\VasAccounting\Entities\VasReceivableAllocation;
use Modules\VasAccounting\Entities\VasTaxCode;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Entities\VasVoucherLine;
use Modules\VasAccounting\Utils\LedgerPostingUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;

class NativeInvoiceService
{
    public function __construct(
        protected LedgerPostingUtil $ledgerPostingUtil,
        protected VasAccountingUtil $vasUtil,
        protected ApprovalRuleService $approvalRuleService,
        protected DocumentApprovalService $documentApprovalService,
        protected VasPostingService $postingService,
        protected NativeDocumentMetaBuilder $metaBuilder,
        protected ProductUtil $productUtil
    ) {
    }

    public function paginateNativeInvoices(int $businessId, int $perPage = 20): LengthAwarePaginator
    {
        return VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('source_type', 'native_invoice')
            ->with(['lines', 'approvals'])
            ->latest('posting_date')
            ->latest('id')
            ->paginate($perPage);
    }

    public function findNativeInvoice(int $businessId, int $voucherId): VasVoucher
    {
        return VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('source_type', 'native_invoice')
            ->with(['lines.account', 'approvals', 'auditLogs', 'attachments'])
            ->findOrFail($voucherId);
    }

    public function buildDraftPayload(int $businessId, array $data, ?VasVoucher $existingVoucher = null): array
    {
        $invoiceKind = (string) ($data['invoice_kind'] ?? 'purchase_invoice');
        if (! in_array($invoiceKind, ['purchase_invoice', 'purchase_debit_note', 'sales_invoice', 'sales_credit_note'], true)) {
            throw new RuntimeException('Unsupported native invoice kind.');
        }
        $isPurchaseInvoice = $invoiceKind === 'purchase_invoice';
        $isPurchaseDebitNote = $invoiceKind === 'purchase_debit_note';
        $isSalesInvoice = $invoiceKind === 'sales_invoice';
        $isSalesCreditNote = $invoiceKind === 'sales_credit_note';
        $isPurchaseDirection = $isPurchaseInvoice || $isPurchaseDebitNote;
        $isSalesDirection = $isSalesInvoice || $isSalesCreditNote;

        $lineItems = $this->normalizeInvoiceLines((array) ($data['line_items'] ?? []));
        if ($lineItems->isEmpty()) {
            throw new RuntimeException('At least one invoice line is required.');
        }

        $currencyCode = strtoupper((string) ($data['currency_code'] ?? config('vasaccounting.book_currency', 'VND')));
        $documentDate = Carbon::parse((string) ($data['document_date'] ?? now()->toDateString()));
        $postingDate = Carbon::parse((string) ($data['posting_date'] ?? $documentDate->toDateString()));
        $this->assertPeriodOpen($businessId, $postingDate);

        $contactId = $this->nullableInt($data['contact_id'] ?? null);
        if ($contactId <= 0) {
            throw new RuntimeException($isSalesDirection
                ? 'Native sales invoices require a customer.'
                : 'Native purchase invoices require a supplier.');
        }
        $locationId = $this->nullableInt($data['business_location_id'] ?? null);
        $locationDefaults = $this->resolveLocationInvoiceDefaults($locationId);

        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $postingMap = (array) $settings->posting_map;
        $accountsPayableId = (int) ($postingMap['accounts_payable'] ?? 0);
        $accountsReceivableId = (int) ($postingMap['accounts_receivable'] ?? 0);
        $vatInputId = (int) ($postingMap['vat_input'] ?? 0);
        $vatOutputId = (int) ($postingMap['vat_output'] ?? 0);
        if ($isPurchaseDirection && $accountsPayableId <= 0) {
            throw new RuntimeException('VAS posting map is missing the accounts payable control account.');
        }
        if ($isSalesDirection && $accountsReceivableId <= 0) {
            throw new RuntimeException('VAS posting map is missing the accounts receivable control account.');
        }
        if (
            $isSalesDirection
            && (bool) data_get(config('vasaccounting.native_document_families.invoice', []), 'sales_guardrails.block_inventory_impact', true)
            && $this->hasInventoryImpact($lineItems)
        ) {
            throw new RuntimeException('Native sales invoices are financial-only until inventory/COGS mapping validation is completed.');
        }

        $voucherLines = [];
        $netTotal = 0.0;
        $taxTotal = 0.0;

        foreach ($lineItems as $lineItem) {
            $netAmount = round((float) $lineItem['net_amount'], 4);
            $taxAmount = round((float) $lineItem['tax_amount'], 4);
            $netTotal += $netAmount;
            $taxTotal += $taxAmount;

            $lineDebit = 0.0;
            $lineCredit = 0.0;
            if ($isPurchaseDirection) {
                $lineDebit = $isPurchaseDebitNote ? 0.0 : $netAmount;
                $lineCredit = $isPurchaseDebitNote ? $netAmount : 0.0;
            } else {
                $lineDebit = $isSalesCreditNote ? $netAmount : 0.0;
                $lineCredit = $isSalesCreditNote ? 0.0 : $netAmount;
            }

            $voucherLines[] = [
                'account_id' => (int) $lineItem['account_id'],
                'description' => (string) ($lineItem['description'] ?? $this->defaultRevenueExpenseLineDescription($invoiceKind)),
                'debit' => $lineDebit,
                'credit' => $lineCredit,
                'contact_id' => $contactId,
                'department_id' => $lineItem['department_id'] ?? null,
                'cost_center_id' => $lineItem['cost_center_id'] ?? null,
                'project_id' => $lineItem['project_id'] ?? null,
                'product_id' => $lineItem['product_id'] ?? null,
                'warehouse_id' => $lineItem['warehouse_id'] ?? null,
                'budget_id' => $lineItem['budget_id'] ?? null,
                'tax_code_id' => $lineItem['tax_code_id'] ?? null,
                'meta' => [
                    'native_invoice_line' => [
                        'net_amount' => $netAmount,
                        'tax_amount' => $taxAmount,
                    ],
                ],
            ];

            if ($taxAmount > 0) {
                if ($isPurchaseDirection && $vatInputId <= 0) {
                    throw new RuntimeException('VAS posting map is missing the input VAT account required for taxed purchase invoices.');
                }
                if ($isSalesDirection && $vatOutputId <= 0) {
                    throw new RuntimeException('VAS posting map is missing the output VAT account required for taxed sales invoices.');
                }

                $taxDebit = 0.0;
                $taxCredit = 0.0;
                if ($isPurchaseDirection) {
                    $taxDebit = $isPurchaseDebitNote ? 0.0 : $taxAmount;
                    $taxCredit = $isPurchaseDebitNote ? $taxAmount : 0.0;
                } else {
                    $taxDebit = $isSalesCreditNote ? $taxAmount : 0.0;
                    $taxCredit = $isSalesCreditNote ? 0.0 : $taxAmount;
                }

                $voucherLines[] = [
                    'account_id' => $isPurchaseDirection ? $vatInputId : $vatOutputId,
                    'description' => $isPurchaseDirection ? 'Input VAT' : 'Output VAT',
                    'debit' => $taxDebit,
                    'credit' => $taxCredit,
                    'contact_id' => $contactId,
                    'tax_code_id' => $lineItem['tax_code_id'] ?? null,
                    'meta' => [
                        'native_invoice_line' => [
                            'net_amount' => 0,
                            'tax_amount' => $taxAmount,
                        ],
                    ],
                ];
            }
        }

        $grossTotal = round($netTotal + $taxTotal, 4);
        $controlDebit = 0.0;
        $controlCredit = 0.0;
        $controlAccountId = 0;
        $controlDescription = '';
        if ($isPurchaseDirection) {
            $controlDebit = $isPurchaseDebitNote ? $grossTotal : 0.0;
            $controlCredit = $isPurchaseDebitNote ? 0.0 : $grossTotal;
            $controlAccountId = $accountsPayableId;
            $controlDescription = $isPurchaseDebitNote ? 'Supplier debit note settlement' : 'Supplier invoice settlement';
        } else {
            $controlDebit = $isSalesCreditNote ? 0.0 : $grossTotal;
            $controlCredit = $isSalesCreditNote ? $grossTotal : 0.0;
            $controlAccountId = $accountsReceivableId;
            $controlDescription = $isSalesCreditNote ? 'Customer credit note settlement' : 'Customer invoice settlement';
        }

        $voucherLines[] = [
            'account_id' => $controlAccountId,
            'description' => $controlDescription,
            'debit' => $controlDebit,
            'credit' => $controlCredit,
            'contact_id' => $contactId,
        ];

        $sequenceKey = (string) data_get(config('vasaccounting.native_document_families.invoice', []), "sequence_keys.{$invoiceKind}", $invoiceKind);
        $reference = trim((string) ($data['reference'] ?? ''));
        if ($reference === '') {
            $reference = $existingVoucher?->reference ?: $this->nextLegacyInvoiceReference($businessId, $invoiceKind);
        }
        $invoiceSchemeId = $this->nullableInt($data['invoice_scheme_id'] ?? data_get($existingVoucher?->meta, 'invoice.scheme_id') ?? ($isSalesDirection ? ($locationDefaults['scheme_id'] ?? null) : null));
        $invoiceLayoutId = $this->nullableInt($data['invoice_layout_id'] ?? data_get($existingVoucher?->meta, 'invoice.layout_id') ?? ($isSalesDirection ? ($locationDefaults['layout_id'] ?? null) : null));
        $publicToken = null;
        if ($isSalesDirection) {
            $publicToken = trim((string) ($data['public_token'] ?? data_get($existingVoucher?->meta, 'invoice.public_token', '')));
            if ($publicToken === '') {
                $publicToken = Str::random(64);
            }
        }

        $approvalContext = [
            'source_type' => 'native_invoice',
            'document_family' => 'invoice',
            'module_area' => 'invoices',
            'document_type' => $invoiceKind,
            'business_location_id' => $locationId,
            'currency_code' => $currencyCode,
            'amount' => $grossTotal,
            'requires_approval' => $data['requires_approval'] ?? null,
        ];
        $defaultStatus = $this->approvalRuleService->defaultStatus($businessId, 'invoice', $approvalContext);
        $requiresApproval = $this->approvalRuleService->requiresApproval($businessId, 'invoice', $approvalContext);
        $status = $existingVoucher?->status ?: $defaultStatus;
        $dueDate = trim((string) ($data['due_date'] ?? '')) !== '' ? Carbon::parse((string) $data['due_date'])->toDateString() : $documentDate->toDateString();
        $legacyLinks = $isPurchaseDirection
            ? [
                'purchase_ref_no' => $reference,
                'supplier_invoice_no' => $data['external_reference'] ?? null,
            ]
            : [
                'invoice_no' => $reference,
                'invoice_token' => $publicToken,
            ];

        $meta = $this->metaBuilder->buildInvoiceMeta([
            'direction' => $isSalesDirection ? 'sales' : 'purchase',
            'invoice_kind' => $invoiceKind,
            'counterparty_type' => $isSalesDirection ? 'customer' : 'vendor',
            'contact_id' => $contactId,
            'document_date' => $documentDate->toDateString(),
            'due_date' => $dueDate,
            'payment_terms' => [
                'due_date' => $dueDate,
                'pay_term_number' => $this->nullableInt($data['pay_term_number'] ?? null),
                'pay_term_type' => $data['pay_term_type'] ?? null,
            ],
            'scheme_id' => $invoiceSchemeId,
            'layout_id' => $invoiceLayoutId,
            'public_token' => $publicToken,
            'reference' => $reference,
            'default_status' => $defaultStatus,
            'requires_approval' => $requiresApproval,
            'legacy_links' => $legacyLinks,
            'tax_summary' => [
                'gross_amount' => round($grossTotal, 4),
                'net_amount' => round($netTotal, 4),
                'tax_amount' => round($taxTotal, 4),
            ],
            'lines' => $this->buildInvoiceSnapshotLines($lineItems, $invoiceKind),
            'coexistence_mode' => (string) data_get(config('vasaccounting.native_document_families.invoice', []), 'coexistence_mode', 'parallel'),
        ]);
        if ($isSalesDirection) {
            $meta['coexistence']['inventory_guard'] = [
                'mode' => (string) data_get(config('vasaccounting.native_document_families.invoice', []), 'sales_guardrails.mode', 'financial_only'),
                'inventory_mapping_required' => true,
                'inventory_mapping_validated' => false,
            ];
        }

        $payload = [
            'business_id' => $businessId,
            'accounting_period_id' => (int) $this->vasUtil->resolvePeriodForDate($businessId, $postingDate)->id,
            'voucher_type' => $invoiceKind,
            'module_area' => 'invoices',
            'document_type' => $invoiceKind,
            'sequence_key' => $sequenceKey,
            'source_type' => 'native_invoice',
            'source_id' => $existingVoucher?->source_id,
            'contact_id' => $contactId,
            'business_location_id' => $locationId,
            'posting_date' => $postingDate->toDateString(),
            'document_date' => $documentDate->toDateString(),
            'description' => trim((string) ($data['description'] ?? '')) ?: $this->defaultDocumentDescription($invoiceKind),
            'reference' => $reference,
            'external_reference' => trim((string) ($data['external_reference'] ?? '')) ?: $reference,
            'status' => $status,
            'currency_code' => $currencyCode,
            'exchange_rate' => (float) ($data['exchange_rate'] ?? 1),
            'total_debit' => round((float) collect($voucherLines)->sum('debit'), 4),
            'total_credit' => round((float) collect($voucherLines)->sum('credit'), 4),
            'is_system_generated' => false,
            'meta' => $meta,
            'lines' => $voucherLines,
        ];

        $payload['source_hash'] = $this->ledgerPostingUtil->buildSourceHash($payload, collect($voucherLines));

        return $payload;
    }

    public function createDraft(int $businessId, array $data, int $userId): VasVoucher
    {
        $payload = $this->buildDraftPayload($businessId, $data);

        return DB::transaction(function () use ($payload, $userId) {
            $voucher = VasVoucher::create([
                'business_id' => (int) $payload['business_id'],
                'accounting_period_id' => (int) $payload['accounting_period_id'],
                'voucher_no' => $this->ledgerPostingUtil->nextVoucherNumber((int) $payload['business_id'], (string) $payload['sequence_key']),
                'voucher_type' => (string) $payload['voucher_type'],
                'module_area' => (string) $payload['module_area'],
                'document_type' => (string) $payload['document_type'],
                'sequence_key' => (string) $payload['sequence_key'],
                'source_type' => 'native_invoice',
                'source_id' => null,
                'source_hash' => (string) $payload['source_hash'],
                'contact_id' => $payload['contact_id'],
                'business_location_id' => $payload['business_location_id'],
                'posting_date' => $payload['posting_date'],
                'document_date' => $payload['document_date'],
                'description' => $payload['description'],
                'reference' => $payload['reference'],
                'external_reference' => $payload['external_reference'],
                'status' => $payload['status'],
                'currency_code' => $payload['currency_code'],
                'exchange_rate' => $payload['exchange_rate'],
                'total_debit' => $payload['total_debit'],
                'total_credit' => $payload['total_credit'],
                'is_system_generated' => false,
                'created_by' => $userId,
                'meta' => $payload['meta'],
            ]);

            $voucher->update(['source_id' => (int) $voucher->id]);
            $this->syncVoucherLines($voucher, (array) $payload['lines']);
            $this->documentApprovalService->recordAudit(
                (int) $voucher->business_id,
                VasVoucher::class,
                (int) $voucher->id,
                'draft_saved',
                $userId,
                [],
                $voucher->fresh(['lines'])->only(['status', 'reference', 'external_reference', 'total_debit', 'total_credit', 'meta'])
            );

            return $voucher->fresh(['lines.account', 'approvals', 'auditLogs']);
        });
    }

    public function updateDraft(VasVoucher $voucher, array $data, int $userId): VasVoucher
    {
        $this->assertEditable($voucher);
        $payload = $this->buildDraftPayload((int) $voucher->business_id, $data, $voucher);

        return DB::transaction(function () use ($voucher, $payload, $userId) {
            $before = $voucher->only(['status', 'posting_date', 'document_date', 'reference', 'external_reference', 'total_debit', 'total_credit', 'meta']);
            $voucher->update([
                'accounting_period_id' => (int) $payload['accounting_period_id'],
                'voucher_type' => (string) $payload['voucher_type'],
                'document_type' => (string) $payload['document_type'],
                'sequence_key' => (string) $payload['sequence_key'],
                'source_hash' => (string) $payload['source_hash'],
                'contact_id' => $payload['contact_id'],
                'business_location_id' => $payload['business_location_id'],
                'posting_date' => $payload['posting_date'],
                'document_date' => $payload['document_date'],
                'description' => $payload['description'],
                'reference' => $payload['reference'],
                'external_reference' => $payload['external_reference'],
                'currency_code' => $payload['currency_code'],
                'exchange_rate' => $payload['exchange_rate'],
                'total_debit' => $payload['total_debit'],
                'total_credit' => $payload['total_credit'],
                'meta' => $payload['meta'],
            ]);

            $this->syncVoucherLines($voucher, (array) $payload['lines']);
            $this->documentApprovalService->recordAudit(
                (int) $voucher->business_id,
                VasVoucher::class,
                (int) $voucher->id,
                'draft_updated',
                $userId,
                $before,
                $voucher->fresh(['lines'])->only(['status', 'posting_date', 'document_date', 'reference', 'external_reference', 'total_debit', 'total_credit', 'meta'])
            );

            return $voucher->fresh(['lines.account', 'approvals', 'auditLogs']);
        });
    }

    public function submit(VasVoucher $voucher, int $userId): VasVoucher
    {
        return $this->documentApprovalService->submitVoucher($voucher, $userId);
    }

    public function approve(VasVoucher $voucher, int $userId, ?string $comments = null): VasVoucher
    {
        return $this->documentApprovalService->approveVoucher($voucher, $userId, $comments);
    }

    public function reject(VasVoucher $voucher, int $userId, ?string $comments = null): VasVoucher
    {
        return $this->documentApprovalService->rejectVoucher($voucher, $userId, $comments);
    }

    public function cancel(VasVoucher $voucher, int $userId, ?string $comments = null): VasVoucher
    {
        return $this->documentApprovalService->cancelVoucher($voucher, $userId, $comments);
    }

    public function post(VasVoucher $voucher, int $userId): VasVoucher
    {
        return DB::transaction(function () use ($voucher, $userId) {
            $postedVoucher = $this->postingService->postExistingVoucher($voucher->fresh('lines'), $userId);
            $this->documentApprovalService->recordAudit(
                (int) $postedVoucher->business_id,
                VasVoucher::class,
                (int) $postedVoucher->id,
                'posted',
                $userId,
                ['status' => $voucher->status],
                ['status' => $postedVoucher->fresh()->status]
            );

            return $postedVoucher->fresh(['lines.account', 'approvals', 'auditLogs']);
        });
    }

    public function reverse(VasVoucher $voucher, int $userId): VasVoucher
    {
        if (in_array((string) $voucher->voucher_type, ['purchase_invoice', 'purchase_debit_note'], true)
            && VasPayableAllocation::query()->where('bill_voucher_id', (int) $voucher->id)->exists()) {
            throw new RuntimeException('Allocated supplier invoices must be deallocated before reversal.');
        }
        if (in_array((string) $voucher->voucher_type, ['sales_invoice', 'sales_credit_note'], true)
            && VasReceivableAllocation::query()->where('invoice_voucher_id', (int) $voucher->id)->exists()) {
            throw new RuntimeException('Allocated customer invoices must be deallocated before reversal.');
        }

        return DB::transaction(function () use ($voucher, $userId) {
            $reversal = $this->postingService->reverseVoucher($voucher->fresh('lines'), $userId);
            $this->documentApprovalService->recordAudit(
                (int) $voucher->business_id,
                VasVoucher::class,
                (int) $voucher->id,
                'reversed',
                $userId,
                ['status' => $voucher->status],
                ['status' => 'reversed', 'reversal_voucher_id' => (int) $reversal->id]
            );

            return $reversal->fresh('lines');
        });
    }

    protected function syncVoucherLines(VasVoucher $voucher, array $lines): void
    {
        $this->ledgerPostingUtil->assertBalanced(collect($lines));
        $voucher->lines()->delete();

        foreach (array_values($lines) as $index => $line) {
            VasVoucherLine::create([
                'business_id' => (int) $voucher->business_id,
                'voucher_id' => (int) $voucher->id,
                'line_no' => $index + 1,
                'account_id' => (int) $line['account_id'],
                'business_location_id' => $line['business_location_id'] ?? $voucher->business_location_id,
                'contact_id' => $line['contact_id'] ?? $voucher->contact_id,
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
    }

    protected function normalizeInvoiceLines(array $lines): Collection
    {
        return collect($lines)
            ->map(function ($line) {
                if (! is_array($line)) {
                    return null;
                }

                $accountId = $this->nullableInt($line['account_id'] ?? null);
                $netAmount = round((float) ($line['net_amount'] ?? 0), 4);
                $taxAmount = round((float) ($line['tax_amount'] ?? 0), 4);
                if ($accountId <= 0 || $netAmount <= 0) {
                    return null;
                }

                if (! empty($line['tax_code_id']) && $taxAmount <= 0) {
                    $taxAmount = $this->resolveTaxAmount($this->nullableInt($line['tax_code_id'] ?? null), $netAmount);
                }

                return [
                    'account_id' => $accountId,
                    'description' => trim((string) ($line['description'] ?? '')) ?: null,
                    'net_amount' => $netAmount,
                    'tax_amount' => $taxAmount,
                    'tax_code_id' => $this->nullableInt($line['tax_code_id'] ?? null),
                    'department_id' => $this->nullableInt($line['department_id'] ?? null),
                    'cost_center_id' => $this->nullableInt($line['cost_center_id'] ?? null),
                    'project_id' => $this->nullableInt($line['project_id'] ?? null),
                    'product_id' => $this->nullableInt($line['product_id'] ?? null),
                    'warehouse_id' => $this->nullableInt($line['warehouse_id'] ?? null),
                    'budget_id' => $this->nullableInt($line['budget_id'] ?? null),
                ];
            })
            ->filter()
            ->values();
    }

    protected function buildInvoiceSnapshotLines(Collection $lineItems, string $invoiceKind): array
    {
        $isPurchaseDebitNote = $invoiceKind === 'purchase_debit_note';
        $isSalesInvoice = $invoiceKind === 'sales_invoice';
        $isSalesCreditNote = $invoiceKind === 'sales_credit_note';

        return $lineItems
            ->values()
            ->map(function (array $lineItem, int $index) use ($isPurchaseDebitNote, $isSalesInvoice, $isSalesCreditNote) {
                $debit = 0.0;
                $credit = 0.0;
                if ($isSalesInvoice) {
                    $credit = (float) $lineItem['net_amount'];
                } elseif ($isSalesCreditNote) {
                    $debit = (float) $lineItem['net_amount'];
                } elseif ($isPurchaseDebitNote) {
                    $credit = (float) $lineItem['net_amount'];
                } else {
                    $debit = (float) $lineItem['net_amount'];
                }

                return [
                    'line_no' => $index + 1,
                    'account_id' => (int) $lineItem['account_id'],
                    'description' => (string) ($lineItem['description'] ?? ''),
                    'debit' => $debit,
                    'credit' => $credit,
                    'meta' => Arr::whereNotNull([
                        'net_amount' => (float) $lineItem['net_amount'],
                        'tax_amount' => (float) $lineItem['tax_amount'],
                        'tax_code_id' => $lineItem['tax_code_id'] ?? null,
                        'product_id' => $lineItem['product_id'] ?? null,
                    ]),
                ];
            })
            ->all();
    }

    protected function resolveTaxAmount(?int $taxCodeId, float $netAmount): float
    {
        if (! $taxCodeId) {
            return 0.0;
        }

        $taxCode = VasTaxCode::query()->find($taxCodeId);

        return $taxCode ? round($netAmount * (((float) $taxCode->rate) / 100), 4) : 0.0;
    }

    protected function nextLegacyInvoiceReference(int $businessId, string $invoiceKind): string
    {
        $referenceType = match ($invoiceKind) {
            'purchase_debit_note' => 'purchase_return',
            'sales_invoice' => 'invoice',
            'sales_credit_note' => 'sell_return',
            default => 'purchase',
        };
        $refCount = $this->productUtil->setAndGetReferenceCount($referenceType, $businessId);

        return (string) $this->productUtil->generateReferenceNumber($referenceType, $refCount, $businessId);
    }

    public function resolvePublicToken(VasVoucher $voucher): ?string
    {
        $token = trim((string) data_get($voucher->meta, 'invoice.public_token', ''));

        return $token !== '' ? $token : null;
    }

    public function findNativeSalesInvoiceByPublicToken(string $token): ?VasVoucher
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        return VasVoucher::query()
            ->where('source_type', 'native_invoice')
            ->whereIn('voucher_type', ['sales_invoice', 'sales_credit_note'])
            ->where(function ($query) use ($token) {
                $query->where('meta->invoice->public_token', $token)
                    ->orWhere('meta->legacy_links->invoice_token', $token);
            })
            ->with('lines.account')
            ->first();
    }

    public function outstandingReceivableAmount(VasVoucher $voucher): float
    {
        $grossAmount = round((float) max($voucher->total_debit, $voucher->total_credit), 4);
        $allocatedAmount = round((float) VasReceivableAllocation::query()
            ->where('business_id', (int) $voucher->business_id)
            ->where('invoice_voucher_id', (int) $voucher->id)
            ->sum('amount'), 4);

        return max(round($grossAmount - $allocatedAmount, 4), 0.0);
    }

    protected function defaultDocumentDescription(string $invoiceKind): string
    {
        return match ($invoiceKind) {
            'sales_invoice' => 'Native sales invoice',
            'sales_credit_note' => 'Native sales credit note',
            'purchase_debit_note' => 'Native purchase debit note',
            default => 'Native purchase invoice',
        };
    }

    protected function defaultRevenueExpenseLineDescription(string $invoiceKind): string
    {
        return match ($invoiceKind) {
            'sales_invoice' => 'Sales invoice line',
            'sales_credit_note' => 'Sales credit note line',
            'purchase_debit_note' => 'Purchase debit note line',
            default => 'Purchase invoice line',
        };
    }

    protected function hasInventoryImpact(Collection $lineItems): bool
    {
        return $lineItems->contains(function (array $lineItem) {
            return ! empty($lineItem['product_id']) || ! empty($lineItem['warehouse_id']);
        });
    }

    protected function resolveLocationInvoiceDefaults(?int $locationId): array
    {
        if (! $locationId) {
            return [
                'scheme_id' => null,
                'layout_id' => null,
            ];
        }

        $location = BusinessLocation::query()->find($locationId);
        if (! $location) {
            return [
                'scheme_id' => null,
                'layout_id' => null,
            ];
        }

        return [
            'scheme_id' => $this->nullableInt($location->sale_invoice_scheme_id ?? null),
            'layout_id' => $this->nullableInt($location->sale_invoice_layout_id ?? null),
        ];
    }

    protected function assertEditable(VasVoucher $voucher): void
    {
        if ($voucher->status !== 'draft') {
            throw new RuntimeException("Only draft native invoices can be edited. Voucher [{$voucher->voucher_no}] is [{$voucher->status}].");
        }
    }

    protected function assertPeriodOpen(int $businessId, Carbon $postingDate): void
    {
        $period = $this->vasUtil->resolvePeriodForDate($businessId, $postingDate);
        if (in_array((string) $period->status, ['soft_locked', 'closed'], true)) {
            throw new RuntimeException("VAS accounting period [{$period->name}] is locked for document updates.");
        }
    }

    protected function nullableInt($value): ?int
    {
        $intValue = (int) $value;

        return $intValue > 0 ? $intValue : null;
    }
}
