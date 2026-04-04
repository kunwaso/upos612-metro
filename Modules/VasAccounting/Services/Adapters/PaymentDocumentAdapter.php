<?php

namespace Modules\VasAccounting\Services\Adapters;

use App\Transaction;
use App\TransactionPayment;
use RuntimeException;

class PaymentDocumentAdapter extends AbstractSourceDocumentAdapter
{
    public function loadSourceDocument(int $sourceId, array $context = [])
    {
        $payment = TransactionPayment::find($sourceId);
        if ($payment) {
            return $payment;
        }

        $snapshot = (array) ($context['source_snapshot'] ?? []);
        if (! empty($context['is_deleted']) && (int) ($snapshot['id'] ?? $sourceId) > 0) {
            return (object) array_merge(['id' => $sourceId], $snapshot);
        }

        throw new RuntimeException("Transaction payment [{$sourceId}] could not be loaded for VAS posting.");
    }

    public function toVoucherPayload($sourceDocument, array $context = []): array
    {
        $payment = $sourceDocument;
        $transaction = $this->loadTransaction((int) $payment->transaction_id, $context);
        $settings = $this->settings((int) $transaction->business_id);
        $amount = $this->money($payment->amount);
        $usesBankAccount = in_array((string) $payment->method, ['bank_transfer', 'card', 'cheque', 'bank'], true);
        $cashAccountKey = $usesBankAccount ? 'bank' : 'cash';
        $isPayrollPayment = (string) $transaction->type === 'payroll';
        $isPayableType = in_array((string) $transaction->type, ['purchase', 'purchase_return', 'expense', 'payroll'], true);
        $isDeleted = (bool) ($context['is_deleted'] ?? false);
        $sequenceKey = $isPayrollPayment
            ? 'payroll_payment'
            : ($usesBankAccount
                ? ($isPayableType ? 'bank_payment' : 'bank_receipt')
                : ($isPayableType ? 'cash_payment' : 'cash_receipt'));
        $voucherType = $isDeleted
            ? ($isPayrollPayment ? 'payroll_payment_reversal' : ($isPayableType ? 'payment_reversal' : 'receipt_reversal'))
            : $sequenceKey;
        $paymentReference = $payment->payment_ref_no ?? $payment->transaction_no ?? $payment->id;

        if ($isDeleted) {
            $lines = [
                $this->line($this->postingMapAccount($settings, $cashAccountKey), 'Cash or bank rollback', $amount, 0),
                $this->line($this->postingMapAccount($settings, $isPayableType ? 'accounts_payable' : 'accounts_receivable'), $isPayrollPayment ? 'Payroll payment rollback' : 'Payment rollback', 0, $amount),
            ];
        } elseif ($isPayableType) {
            $lines = [
                $this->line($this->postingMapAccount($settings, 'accounts_payable'), $isPayrollPayment ? 'Payroll payable settlement' : 'Supplier payment settlement', $amount, 0),
                $this->line($this->postingMapAccount($settings, $cashAccountKey), 'Cash or bank outflow', 0, $amount),
            ];
        } else {
            $lines = [
                $this->line($this->postingMapAccount($settings, $cashAccountKey), 'Cash or bank receipt', $amount, 0),
                $this->line($this->postingMapAccount($settings, 'accounts_receivable'), 'Customer receipt settlement', 0, $amount),
            ];
        }

        return $this->payload([
            'business_id' => (int) $transaction->business_id,
            'voucher_type' => $voucherType,
            'sequence_key' => $sequenceKey,
            'source_type' => 'transaction_payment',
            'source_id' => (int) $payment->id,
            'transaction_id' => (int) $transaction->id,
            'transaction_payment_id' => (int) $payment->id,
            'contact_id' => (int) ($transaction->contact_id ?? 0) ?: null,
            'business_location_id' => (int) ($transaction->location_id ?? 0) ?: null,
            'posting_date' => $payment->paid_on ?: $transaction->transaction_date,
            'document_date' => $payment->paid_on ?: $transaction->transaction_date,
            'description' => ($isDeleted ? 'Reversed ' : 'Auto-posted ') . str_replace('_', ' ', $sequenceKey) . ' ' . $paymentReference,
            'reference' => $payment->payment_ref_no ?? $payment->transaction_no ?? null,
            'external_reference' => $payment->payment_ref_no ?? $payment->transaction_no ?? null,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => (int) ($payment->created_by ?? $transaction->created_by ?? 0),
            'module_area' => $isPayrollPayment ? 'payroll' : null,
            'document_type' => $isPayrollPayment ? 'payroll_payment' : null,
            'meta' => array_replace(
                $this->metaBuilder()->buildPaymentMeta([
                    'direction' => $isPayableType ? 'payment' : 'receipt',
                    'payment_kind' => $sequenceKey,
                    'contact_id' => (int) ($transaction->contact_id ?? 0) ?: null,
                    'document_date' => $payment->paid_on ?: $transaction->transaction_date,
                    'reference' => $paymentReference,
                    'requires_approval' => false,
                    'legacy_source_type' => 'transaction_payment',
                    'legacy_source_id' => (int) $payment->id,
                    'business_event_uid' => 'legacy:transaction_payment:' . (int) $payment->id,
                    'coexistence_mode' => 'parallel',
                    'external_reference' => $payment->payment_ref_no ?? $payment->transaction_no ?? null,
                    'payment_instrument' => $payment->method,
                    'legacy_links' => [
                        'transaction_id' => (int) $transaction->id,
                        'transaction_payment_id' => (int) $payment->id,
                        'payment_ref_no' => $payment->payment_ref_no ?? null,
                        'transaction_no' => $payment->transaction_no ?? null,
                    ],
                    'settlement_targets' => [[
                        'transaction_id' => (int) $transaction->id,
                        'transaction_type' => $transaction->type,
                    ]],
                    'lines' => $lines,
                ]),
                [
                    'payment_method' => $payment->method,
                    'transaction_type' => $transaction->type,
                ]
            ),
        ], $lines);
    }

    protected function loadTransaction(int $transactionId, array $context = [])
    {
        $transaction = Transaction::find($transactionId);
        if ($transaction) {
            return $transaction;
        }

        $snapshot = (array) ($context['transaction_snapshot'] ?? []);
        if (! empty($context['is_deleted']) && (int) ($snapshot['id'] ?? $transactionId) > 0) {
            return (object) array_merge(['id' => $transactionId], $snapshot);
        }

        throw new RuntimeException("Transaction [{$transactionId}] could not be loaded for payment VAS posting.");
    }
}
