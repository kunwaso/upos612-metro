<?php

namespace Modules\VasAccounting\Services\Adapters;

use App\Transaction;
use RuntimeException;

class PurchaseDocumentAdapter extends AbstractSourceDocumentAdapter
{
    public function loadSourceDocument(int $sourceId, array $context = [])
    {
        $transaction = Transaction::find($sourceId);
        if ($transaction) {
            return $transaction;
        }

        $snapshot = (array) ($context['source_snapshot'] ?? []);
        if (! empty($context['is_deleted']) && (int) ($snapshot['id'] ?? $sourceId) > 0) {
            return (object) array_merge(['id' => $sourceId], $snapshot);
        }

        throw new RuntimeException("Purchase [{$sourceId}] could not be loaded for VAS posting.");
    }

    public function toVoucherPayload($sourceDocument, array $context = []): array
    {
        $transaction = $sourceDocument;
        $settings = $this->settings((int) $transaction->business_id);
        $gross = $this->money($transaction->final_total);
        $tax = $this->money($transaction->tax_amount);
        $net = max($this->money($gross - $tax), 0);
        $isDeleted = (bool) ($context['is_deleted'] ?? false);
        $reference = $transaction->ref_no ?? $transaction->invoice_no ?? null;
        $documentLabel = $transaction->ref_no ?? $transaction->invoice_no ?? $transaction->id;

        if ($isDeleted) {
            $lines = [
                $this->line($this->postingMapAccount($settings, 'accounts_payable'), 'Purchase rollback payable', $gross, 0),
                $this->line($this->postingMapAccount($settings, 'inventory'), 'Inventory acquisition rollback', 0, $net),
            ];
        } else {
            $lines = [
                $this->line($this->postingMapAccount($settings, 'inventory'), 'Inventory acquisition', $net, 0),
                $this->line($this->postingMapAccount($settings, 'accounts_payable'), 'Trade payable for purchase', 0, $gross),
            ];
        }

        if ($tax > 0) {
            $lines[] = $this->line(
                $this->postingMapAccount($settings, 'vat_input'),
                $isDeleted ? 'VAT input rollback' : 'VAT input deductible',
                $isDeleted ? 0 : $tax,
                $isDeleted ? $tax : 0,
                ['tax_code_id' => $this->taxCodeId((int) $transaction->business_id, round(($tax / max($net, 1)) * 100, 2), 'input')]
            );
        }

        return $this->payload([
            'business_id' => (int) $transaction->business_id,
            'voucher_type' => $isDeleted ? 'purchase_invoice_reversal' : 'purchase_invoice',
            'sequence_key' => 'purchase_invoice',
            'source_type' => 'purchase',
            'source_id' => (int) $transaction->id,
            'transaction_id' => $isDeleted ? null : (int) $transaction->id,
            'contact_id' => (int) ($transaction->contact_id ?? 0) ?: null,
            'business_location_id' => (int) ($transaction->location_id ?? 0) ?: null,
            'posting_date' => $transaction->transaction_date,
            'document_date' => $transaction->transaction_date,
            'description' => ($isDeleted ? 'Reversed purchase ' : 'Auto-posted purchase ') . $documentLabel,
            'reference' => $reference,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => (int) ($transaction->created_by ?? 0),
            'meta' => $this->metaBuilder()->buildInvoiceMeta([
                'direction' => 'purchase',
                'invoice_kind' => 'purchase_invoice',
                'counterparty_type' => 'vendor',
                'contact_id' => (int) ($transaction->contact_id ?? 0) ?: null,
                'document_date' => $transaction->transaction_date,
                'due_date' => $transaction->transaction_date,
                'reference' => $reference,
                'requires_approval' => false,
                'legacy_source_type' => 'purchase',
                'legacy_source_id' => (int) $transaction->id,
                'business_event_uid' => 'legacy:purchase:' . (int) $transaction->id,
                'coexistence_mode' => 'parallel',
                'legacy_links' => [
                    'transaction_id' => (int) $transaction->id,
                    'invoice_no' => $transaction->invoice_no ?? null,
                    'ref_no' => $transaction->ref_no ?? null,
                ],
                'tax_summary' => [
                    'gross_amount' => $gross,
                    'net_amount' => $net,
                    'tax_amount' => $tax,
                ],
                'lines' => $lines,
            ]),
        ], $lines);
    }
}
