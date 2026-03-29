<?php

namespace Modules\VasAccounting\Services\Adapters;

use Modules\VasAccounting\Entities\VasInventoryDocument;
use Modules\VasAccounting\Entities\VasInventoryDocumentLine;

class InventoryDocumentAdapter extends AbstractSourceDocumentAdapter
{
    public function loadSourceDocument(int $sourceId, array $context = [])
    {
        return VasInventoryDocument::query()->with('lines')->findOrFail($sourceId);
    }

    public function toVoucherPayload($sourceDocument, array $context = []): array
    {
        /** @var VasInventoryDocument $document */
        $document = $sourceDocument;
        $settings = $this->settings((int) $document->business_id);

        $sequenceKey = match ($document->document_type) {
            'receipt' => 'inventory_receipt',
            'issue' => 'inventory_issue',
            'transfer' => 'inventory_transfer',
            default => 'inventory_adjustment',
        };

        $lines = [];
        foreach ($document->lines as $line) {
            $lines = array_merge($lines, $this->linesForDocumentLine($document, $line, $settings));
        }

        return $this->payload([
            'business_id' => (int) $document->business_id,
            'voucher_type' => $sequenceKey,
            'sequence_key' => $sequenceKey,
            'source_type' => 'inventory_document',
            'source_id' => (int) $document->id,
            'module_area' => 'inventory',
            'document_type' => 'warehouse_' . $document->document_type,
            'business_location_id' => $document->business_location_id,
            'posting_date' => $document->posting_date,
            'document_date' => $document->document_date,
            'description' => $document->description ?: ('Warehouse ' . $document->document_type . ' ' . $document->document_no),
            'reference' => $document->reference ?: $document->document_no,
            'external_reference' => $document->external_reference,
            'status' => 'posted',
            'currency_code' => 'VND',
            'created_by' => (int) ($context['created_by'] ?? $document->created_by ?? 0),
            'is_system_generated' => false,
        ], $lines);
    }

    protected function linesForDocumentLine($document, VasInventoryDocumentLine $line, $settings): array
    {
        $amount = $this->money($line->amount ?: ((float) $line->quantity * (float) $line->unit_cost));
        $inventoryAccountId = $this->postingMapAccount($settings, 'inventory');
        $transferAccountId = $this->postingMapAccount($settings, 'stock_transfer');
        $adjustmentAccountId = (int) ($document->offset_account_id ?: $this->postingMapAccount($settings, 'stock_adjustment'));
        $offsetAccountId = (int) ($document->offset_account_id ?: $adjustmentAccountId);
        $productMeta = array_filter([
            'product_id' => (int) $line->product_id ?: null,
            'warehouse_id' => $document->warehouse_id,
            'business_location_id' => $document->business_location_id,
            'meta' => array_filter([
                'variation_id' => $line->variation_id,
                'inventory_document_line_id' => (int) $line->id,
            ]),
        ]);

        return match ($document->document_type) {
            'receipt' => [
                $this->line($inventoryAccountId, 'Warehouse receipt ' . $document->document_no, $amount, 0, $productMeta),
                $this->line($offsetAccountId, 'Warehouse receipt offset ' . $document->document_no, 0, $amount, [
                    'business_location_id' => $document->business_location_id,
                ]),
            ],
            'issue' => [
                $this->line($offsetAccountId, 'Warehouse issue offset ' . $document->document_no, $amount, 0, [
                    'business_location_id' => $document->business_location_id,
                ]),
                $this->line($inventoryAccountId, 'Warehouse issue ' . $document->document_no, 0, $amount, $productMeta),
            ],
            'transfer' => [
                $this->line($transferAccountId, 'Warehouse transfer in ' . $document->document_no, $amount, 0, array_merge($productMeta, [
                    'warehouse_id' => $document->destination_warehouse_id,
                ])),
                $this->line($inventoryAccountId, 'Warehouse transfer out ' . $document->document_no, 0, $amount, $productMeta),
            ],
            default => $this->adjustmentLines($document, $line, $inventoryAccountId, $adjustmentAccountId, $amount, $productMeta),
        };
    }

    protected function adjustmentLines($document, VasInventoryDocumentLine $line, int $inventoryAccountId, int $adjustmentAccountId, float $amount, array $productMeta): array
    {
        $direction = $line->direction ?: 'decrease';

        if ($direction === 'increase') {
            return [
                $this->line($inventoryAccountId, 'Warehouse adjustment increase ' . $document->document_no, $amount, 0, $productMeta),
                $this->line($adjustmentAccountId, 'Warehouse adjustment offset ' . $document->document_no, 0, $amount, [
                    'business_location_id' => $document->business_location_id,
                ]),
            ];
        }

        return [
            $this->line($adjustmentAccountId, 'Warehouse adjustment offset ' . $document->document_no, $amount, 0, [
                'business_location_id' => $document->business_location_id,
            ]),
            $this->line($inventoryAccountId, 'Warehouse adjustment decrease ' . $document->document_no, 0, $amount, $productMeta),
        ];
    }
}
