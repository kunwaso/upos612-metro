<?php

namespace Modules\VasAccounting\Services\Adapters;

use App\Transaction;
use App\TransactionPayment;

class PaymentDocumentAdapter extends AbstractSourceDocumentAdapter
{
    public function loadSourceDocument(int $sourceId, array $context = [])
    {
        return TransactionPayment::findOrFail($sourceId);
    }

    public function toVoucherPayload($sourceDocument, array $context = []): array
    {
        $payment = $sourceDocument;
        $transaction = $this->loadTransaction((int) $payment->transaction_id);
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

        if ($isDeleted) {
            $lines = [
                $this->line($this->postingMapAccount($settings, $isPayableType ? 'accounts_payable' : 'accounts_receivable'), $isPayrollPayment ? 'Payroll payment rollback' : 'Payment rollback', $amount, 0),
                $this->line($this->postingMapAccount($settings, $cashAccountKey), 'Cash or bank rollback', 0, $amount),
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
            'description' => ($isDeleted ? 'Reversed ' : 'Auto-posted ') . str_replace('_', ' ', $sequenceKey) . ' ' . ($payment->payment_ref_no ?: $payment->transaction_no ?: $payment->id),
            'reference' => $payment->payment_ref_no ?: $payment->transaction_no,
            'external_reference' => $payment->payment_ref_no ?: $payment->transaction_no,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => (int) ($payment->created_by ?? $transaction->created_by ?? 0),
            'module_area' => $isPayrollPayment ? 'payroll' : null,
            'document_type' => $isPayrollPayment ? 'payroll_payment' : null,
            'meta' => [
                'payment_method' => $payment->method,
                'transaction_type' => $transaction->type,
            ],
        ], $lines);
    }

    protected function loadTransaction(int $transactionId)
    {
        return Transaction::findOrFail($transactionId);
    }
}
