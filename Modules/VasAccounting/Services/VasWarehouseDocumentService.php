<?php

namespace Modules\VasAccounting\Services;

use App\Variation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Entities\VasInventoryDocument;
use Modules\VasAccounting\Entities\VasInventoryDocumentLine;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Utils\LedgerPostingUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;

class VasWarehouseDocumentService
{
    public function __construct(
        protected LedgerPostingUtil $ledgerPostingUtil,
        protected VasAccountingUtil $vasUtil,
        protected VasPostingService $postingService
    ) {
    }

    public function createDocument(int $businessId, array $payload, int $userId): VasInventoryDocument
    {
        $documentDate = Carbon::parse($payload['document_date']);
        $period = $this->vasUtil->resolvePeriodForDate($businessId, $documentDate);
        $documentType = (string) $payload['document_type'];
        $status = (string) ($payload['status'] ?? 'draft');

        if (! in_array($status, ['draft', 'pending_approval', 'approved'], true)) {
            throw new RuntimeException("Warehouse document status [{$status}] is not allowed at creation.");
        }

        if ($documentType === 'transfer' && empty($payload['destination_warehouse_id'])) {
            throw new RuntimeException('Warehouse transfer documents require a destination warehouse.');
        }

        return DB::transaction(function () use ($businessId, $payload, $userId, $period, $documentType, $status) {
            $document = VasInventoryDocument::create([
                'business_id' => $businessId,
                'accounting_period_id' => $period->id,
                'document_no' => $this->ledgerPostingUtil->nextVoucherNumber($businessId, $this->sequenceKeyForType($documentType)),
                'document_type' => $documentType,
                'sequence_key' => $this->sequenceKeyForType($documentType),
                'business_location_id' => $payload['business_location_id'] ?? null,
                'warehouse_id' => $payload['warehouse_id'] ?? null,
                'destination_warehouse_id' => $payload['destination_warehouse_id'] ?? null,
                'offset_account_id' => $payload['offset_account_id'] ?? null,
                'posting_date' => Carbon::parse($payload['posting_date'])->toDateString(),
                'document_date' => Carbon::parse($payload['document_date'])->toDateString(),
                'status' => $status,
                'reference' => $payload['reference'] ?? null,
                'external_reference' => $payload['external_reference'] ?? null,
                'description' => $payload['description'] ?? null,
                'created_by' => $userId,
                'meta' => ['created_from' => 'inventory.index'],
            ]);

            foreach (array_values((array) $payload['lines']) as $index => $line) {
                VasInventoryDocumentLine::create([
                    'business_id' => $businessId,
                    'inventory_document_id' => $document->id,
                    'line_no' => $index + 1,
                    'product_id' => (int) $line['product_id'],
                    'variation_id' => $line['variation_id'] ?? $this->defaultVariationId((int) $line['product_id']),
                    'quantity' => round((float) $line['quantity'], 4),
                    'unit_cost' => round((float) $line['unit_cost'], 4),
                    'amount' => round((float) ($line['amount'] ?? ((float) $line['quantity'] * (float) $line['unit_cost'])), 4),
                    'direction' => $line['direction'] ?? null,
                    'meta' => ['source' => 'manual_inventory_document'],
                ]);
            }

            return $document->fresh('lines');
        });
    }

    public function postDocument(VasInventoryDocument $document, int $userId): VasInventoryDocument
    {
        if ($document->status === 'posted' && $document->posted_voucher_id) {
            return $document->fresh(['lines', 'postedVoucher']);
        }

        if (in_array($document->status, ['reversed', 'cancelled'], true)) {
            throw new RuntimeException("Warehouse document [{$document->document_no}] cannot be posted from status [{$document->status}].");
        }

        $voucher = $this->postingService->processSourceDocument('inventory_document', (int) $document->id, [
            'business_id' => (int) $document->business_id,
            'created_by' => $userId,
        ]);

        $document->status = 'posted';
        $document->posted_voucher_id = $voucher->id;
        $document->posted_at = now();
        $document->posted_by = $userId;
        $document->save();

        return $document->fresh(['lines', 'postedVoucher']);
    }

    public function reverseDocument(VasInventoryDocument $document, int $userId): VasInventoryDocument
    {
        if ($document->status !== 'posted' || ! $document->posted_voucher_id) {
            throw new RuntimeException('Only posted warehouse documents can be reversed.');
        }

        $voucher = VasVoucher::query()
            ->where('business_id', $document->business_id)
            ->with('lines')
            ->findOrFail((int) $document->posted_voucher_id);

        if ((string) $voucher->status !== 'posted') {
            throw new RuntimeException(__('vasaccounting::lang.inventory_reverse_requires_posted_voucher'));
        }

        $reversal = $this->postingService->reverseVoucher($voucher, $userId);

        $document->status = 'reversed';
        $document->reversal_voucher_id = $reversal->id;
        $document->reversed_at = now();
        $document->reversed_by = $userId;
        $document->save();

        return $document->fresh(['lines', 'postedVoucher', 'reversalVoucher']);
    }

    public function recentDocuments(int $businessId, int $limit = 15)
    {
        return VasInventoryDocument::query()
            ->with(['warehouse', 'destinationWarehouse', 'postedVoucher'])
            ->where('business_id', $businessId)
            ->latest('document_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    protected function sequenceKeyForType(string $documentType): string
    {
        return match ($documentType) {
            'receipt' => 'inventory_receipt',
            'issue' => 'inventory_issue',
            'transfer' => 'inventory_transfer',
            default => 'inventory_adjustment',
        };
    }

    protected function defaultVariationId(int $productId): ?int
    {
        return Variation::query()
            ->where('product_id', $productId)
            ->orderBy('id')
            ->value('id');
    }
}
