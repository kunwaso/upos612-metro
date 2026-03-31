<?php

namespace Modules\VasAccounting\Services;

class NativeDocumentMetaBuilder
{
    public function buildInvoiceMeta(array $context): array
    {
        $direction = (string) ($context['direction'] ?? 'sales');
        $reference = (string) ($context['reference'] ?? $context['invoice_no'] ?? $context['source_id'] ?? 'draft');

        return [
            'document_family' => 'invoice',
            'document_direction' => $direction,
            'lifecycle' => [
                'document_family' => 'invoice',
                'default_status' => (string) ($context['default_status'] ?? 'draft'),
                'requires_approval' => (bool) ($context['requires_approval'] ?? true),
            ],
            'invoice' => [
                'invoice_kind' => (string) ($context['invoice_kind'] ?? ($direction === 'purchase' ? 'purchase_invoice' : 'sales_invoice')),
                'counterparty_type' => (string) ($context['counterparty_type'] ?? ($direction === 'purchase' ? 'vendor' : 'customer')),
                'counterparty_id' => $context['contact_id'] ?? null,
                'due_date' => $context['due_date'] ?? null,
                'payment_terms' => $this->normalizeArray((array) ($context['payment_terms'] ?? [])),
                'scheme_id' => $context['scheme_id'] ?? null,
                'layout_id' => $context['layout_id'] ?? null,
                'public_token' => $context['public_token'] ?? null,
                'tax_summary' => $this->normalizeArray((array) ($context['tax_summary'] ?? [])),
                'line_snapshot' => $this->normalizeLineSnapshot((array) ($context['lines'] ?? [])),
            ],
            'legacy_links' => $this->normalizeArray((array) ($context['legacy_links'] ?? [])),
            'coexistence' => [
                'mode' => (string) ($context['coexistence_mode'] ?? 'parallel'),
                'business_event_uid' => (string) ($context['business_event_uid'] ?? $this->buildBusinessEventUid('invoice', [
                    'reference' => $reference,
                    'contact_id' => $context['contact_id'] ?? null,
                    'document_date' => $context['document_date'] ?? null,
                    'direction' => $direction,
                ])),
                'legacy_source_type' => $context['legacy_source_type'] ?? null,
                'legacy_source_id' => $context['legacy_source_id'] ?? null,
            ],
        ];
    }

    public function buildPaymentMeta(array $context): array
    {
        $direction = (string) ($context['direction'] ?? 'payment');
        $reference = (string) ($context['reference'] ?? $context['payment_ref_no'] ?? $context['source_id'] ?? 'draft');

        return [
            'document_family' => 'payment',
            'document_direction' => $direction,
            'lifecycle' => [
                'document_family' => 'payment',
                'default_status' => (string) ($context['default_status'] ?? 'draft'),
                'requires_approval' => (bool) ($context['requires_approval'] ?? true),
            ],
            'payment' => [
                'payment_kind' => (string) ($context['payment_kind'] ?? $direction),
                'counterparty_id' => $context['contact_id'] ?? null,
                'cashbook_id' => $context['cashbook_id'] ?? null,
                'bank_account_id' => $context['bank_account_id'] ?? null,
                'instrument' => $context['payment_instrument'] ?? null,
                'external_reference' => $context['external_reference'] ?? null,
                'settlement_targets' => $this->normalizeArray((array) ($context['settlement_targets'] ?? [])),
                'line_snapshot' => $this->normalizeLineSnapshot((array) ($context['lines'] ?? [])),
            ],
            'legacy_links' => $this->normalizeArray((array) ($context['legacy_links'] ?? [])),
            'coexistence' => [
                'mode' => (string) ($context['coexistence_mode'] ?? 'parallel'),
                'business_event_uid' => (string) ($context['business_event_uid'] ?? $this->buildBusinessEventUid('payment', [
                    'reference' => $reference,
                    'contact_id' => $context['contact_id'] ?? null,
                    'document_date' => $context['document_date'] ?? null,
                    'direction' => $direction,
                ])),
                'legacy_source_type' => $context['legacy_source_type'] ?? null,
                'legacy_source_id' => $context['legacy_source_id'] ?? null,
            ],
        ];
    }

    public function buildPayrollMeta(array $context): array
    {
        $reference = (string) ($context['reference'] ?? $context['run_reference'] ?? $context['source_id'] ?? 'draft');

        return [
            'document_family' => 'payroll',
            'document_direction' => 'accrual',
            'lifecycle' => [
                'document_family' => 'payroll',
                'default_status' => (string) ($context['default_status'] ?? 'draft'),
                'requires_approval' => (bool) ($context['requires_approval'] ?? true),
            ],
            'payroll' => [
                'period_id' => $context['payroll_period_id'] ?? null,
                'run_id' => $context['payroll_run_id'] ?? null,
                'payment_batch_id' => $context['payment_batch_id'] ?? null,
                'employee_summary' => $this->normalizeArray((array) ($context['employee_summary'] ?? [])),
                'statutory_summary' => $this->normalizeArray((array) ($context['statutory_summary'] ?? [])),
                'line_snapshot' => $this->normalizeLineSnapshot((array) ($context['lines'] ?? [])),
            ],
            'legacy_links' => $this->normalizeArray((array) ($context['legacy_links'] ?? [])),
            'coexistence' => [
                'mode' => (string) ($context['coexistence_mode'] ?? 'parallel'),
                'business_event_uid' => (string) ($context['business_event_uid'] ?? $this->buildBusinessEventUid('payroll', [
                    'reference' => $reference,
                    'period_id' => $context['payroll_period_id'] ?? null,
                    'run_id' => $context['payroll_run_id'] ?? null,
                ])),
                'legacy_source_type' => $context['legacy_source_type'] ?? null,
                'legacy_source_id' => $context['legacy_source_id'] ?? null,
            ],
        ];
    }

    public function normalizeLineSnapshot(array $lines): array
    {
        return collect($lines)
            ->map(function (array $line, int $index) {
                return array_filter([
                    'line_no' => (int) ($line['line_no'] ?? ($index + 1)),
                    'account_id' => $line['account_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'debit' => isset($line['debit']) ? round((float) $line['debit'], 4) : null,
                    'credit' => isset($line['credit']) ? round((float) $line['credit'], 4) : null,
                    'contact_id' => $line['contact_id'] ?? null,
                    'product_id' => $line['product_id'] ?? null,
                    'warehouse_id' => $line['warehouse_id'] ?? null,
                    'meta' => $this->normalizeArray((array) ($line['meta'] ?? [])),
                ], fn ($value) => $value !== null && $value !== []);
            })
            ->values()
            ->all();
    }

    public function buildBusinessEventUid(string $family, array $context): string
    {
        ksort($context);

        return sha1($family . '|' . json_encode($context));
    }

    protected function normalizeArray(array $values): array
    {
        return collect($values)
            ->reject(fn ($value) => $value === null || $value === '' || $value === [])
            ->all();
    }
}
