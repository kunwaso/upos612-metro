<?php

namespace Modules\VasAccounting\Services\Adapters;

use App\Transaction;

class SellReturnDocumentAdapter extends AbstractSourceDocumentAdapter
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
        $cogs = $this->cogsForTransaction($transaction);

        $lines = [
            $this->line($this->postingMapAccount($settings, 'revenue'), 'Revenue reversal for sales return', $net, 0),
            $this->line($this->postingMapAccount($settings, 'accounts_receivable'), 'Receivable reversal for sales return', 0, $gross),
        ];

        if ($tax > 0) {
            $lines[] = $this->line($this->postingMapAccount($settings, 'vat_output'), 'VAT output reversal', $tax, 0);
        }

        if ($cogs > 0) {
            $lines[] = $this->line($this->postingMapAccount($settings, 'inventory'), 'Inventory restoration from sales return', $cogs, 0);
            $lines[] = $this->line($this->postingMapAccount($settings, 'cogs'), 'COGS reversal from sales return', 0, $cogs);
        }

        return $this->payload([
            'business_id' => (int) $transaction->business_id,
            'voucher_type' => 'sales_return',
            'sequence_key' => 'sales_invoice',
            'source_type' => 'sell_return',
            'source_id' => (int) $transaction->id,
            'transaction_id' => (int) $transaction->id,
            'contact_id' => (int) ($transaction->contact_id ?? 0) ?: null,
            'business_location_id' => (int) ($transaction->location_id ?? 0) ?: null,
            'posting_date' => $transaction->transaction_date,
            'document_date' => $transaction->transaction_date,
            'description' => 'Auto-posted sell return ' . ($transaction->invoice_no ?: $transaction->ref_no ?: $transaction->id),
            'reference' => $transaction->invoice_no ?: $transaction->ref_no,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => (int) ($transaction->created_by ?? 0),
        ], $lines);
    }
}
