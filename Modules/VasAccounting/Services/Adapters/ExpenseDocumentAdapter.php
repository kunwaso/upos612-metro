<?php

namespace Modules\VasAccounting\Services\Adapters;

use App\Transaction;

class ExpenseDocumentAdapter extends AbstractSourceDocumentAdapter
{
    public function loadSourceDocument(int $sourceId, array $context = [])
    {
        return Transaction::findOrFail($sourceId);
    }

    public function toVoucherPayload($sourceDocument, array $context = []): array
    {
        $transaction = $sourceDocument;
        $settings = $this->settings((int) $transaction->business_id);
        $gross = $this->money($transaction->final_total);
        $tax = $this->money($transaction->tax_amount);
        $net = max($this->money($gross - $tax), 0);

        $lines = [
            $this->line($this->postingMapAccount($settings, 'expense'), 'Operating expense recognition', $net, 0),
            $this->line($this->postingMapAccount($settings, 'accounts_payable'), 'Expense payable', 0, $gross),
        ];

        if ($tax > 0) {
            $lines[] = $this->line($this->postingMapAccount($settings, 'vat_input'), 'VAT input on expense', $tax, 0);
        }

        return $this->payload([
            'business_id' => (int) $transaction->business_id,
            'voucher_type' => 'expense',
            'sequence_key' => 'general_journal',
            'source_type' => 'expense',
            'source_id' => (int) $transaction->id,
            'transaction_id' => (int) $transaction->id,
            'contact_id' => (int) ($transaction->contact_id ?? 0) ?: null,
            'business_location_id' => (int) ($transaction->location_id ?? 0) ?: null,
            'posting_date' => $transaction->transaction_date,
            'document_date' => $transaction->transaction_date,
            'description' => 'Auto-posted expense ' . ($transaction->ref_no ?: $transaction->invoice_no ?: $transaction->id),
            'reference' => $transaction->ref_no ?: $transaction->invoice_no,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => (int) ($transaction->created_by ?? 0),
        ], $lines);
    }
}
