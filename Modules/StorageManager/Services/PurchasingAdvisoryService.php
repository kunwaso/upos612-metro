<?php

namespace Modules\StorageManager\Services;

use App\BusinessLocation;
use App\Transaction;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLink;
use Modules\StorageManager\Entities\StorageReplenishmentRule;
use Modules\StorageManager\Entities\StorageSyncLog;
use RuntimeException;

class PurchasingAdvisoryService
{
    public function __construct(
        protected ReplenishmentService $replenishmentService,
        protected Util $commonUtil
    ) {
    }

    public function queueForLocation(int $businessId, ?int $locationId = null): array
    {
        $queue = $this->replenishmentService->queueForLocation($businessId, $locationId);
        $locationNames = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->pluck('name', 'id');

        $rows = collect($queue['rows'] ?? [])
            ->map(function (array $row) use ($locationNames) {
                $recommendedQty = round((float) ($row['recommended_qty'] ?? 0), 4);
                $sourceQty = round((float) ($row['source_qty'] ?? 0), 4);
                $externalShortageQty = round(max($recommendedQty - $sourceQty, 0), 4);

                return [
                    'rule_id' => (int) ($row['rule_id'] ?? 0),
                    'location_id' => (int) ($row['location_id'] ?? 0),
                    'location_name' => (string) ($locationNames[(int) ($row['location_id'] ?? 0)] ?? ('#' . (int) ($row['location_id'] ?? 0))),
                    'product_label' => (string) ($row['product_label'] ?? '—'),
                    'sku' => (string) ($row['sku'] ?? '—'),
                    'source_label' => (string) ($row['source_label'] ?? '—'),
                    'destination_label' => (string) ($row['destination_label'] ?? '—'),
                    'source_qty' => $sourceQty,
                    'destination_qty' => round((float) ($row['destination_qty'] ?? 0), 4),
                    'recommended_qty' => $recommendedQty,
                    'external_shortage_qty' => $externalShortageQty,
                ];
            })
            ->filter(fn (array $row) => $row['external_shortage_qty'] > 0)
            ->values();

        $advisoryDocuments = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'purchase_requisition_advisory')
            ->where('source_type', 'replenishment_rule')
            ->whereIn('source_id', $rows->pluck('rule_id')->all())
            ->orderByDesc('id')
            ->get()
            ->unique('source_id')
            ->keyBy('source_id');

        $links = StorageDocumentLink::query()
            ->where('business_id', $businessId)
            ->where('linked_system', 'app')
            ->where('linked_type', 'purchase_requisition')
            ->whereIn('document_id', $advisoryDocuments->pluck('id')->all())
            ->get()
            ->groupBy('document_id')
            ->map(fn (Collection $group) => $group->sortByDesc('id')->first());

        $requisitions = Transaction::query()
            ->where('business_id', $businessId)
            ->where('type', 'purchase_requisition')
            ->whereIn('id', $links->pluck('linked_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $rows = $rows->map(function (array $row) use ($advisoryDocuments, $links, $requisitions) {
            $document = $advisoryDocuments->get($row['rule_id']);
            $link = $document ? $links->get($document->id) : null;
            $requisition = $link ? $requisitions->get((int) $link->linked_id) : null;
            $hasOpenRequisition = $requisition && ! in_array((string) $requisition->status, ['completed', 'cancelled'], true);

            $row['advisory_document_id'] = $document?->id;
            $row['advisory_document_no'] = $document?->document_no;
            $row['purchase_requisition_id'] = $requisition?->id;
            $row['purchase_requisition_ref'] = $requisition?->ref_no;
            $row['purchase_requisition_status'] = $requisition?->status;
            $row['purchase_requisition_date'] = ! empty($requisition?->transaction_date)
                ? Carbon::parse($requisition->transaction_date)->format('Y-m-d H:i')
                : null;
            $row['can_create_requisition'] = ! $hasOpenRequisition;

            return $row;
        })->values();

        return [
            'summary' => [
                'shortage_count' => (int) $rows->count(),
                'total_external_shortage_qty' => round((float) $rows->sum('external_shortage_qty'), 4),
                'open_requisitions' => (int) $rows->filter(function (array $row) {
                    return ! empty($row['purchase_requisition_id'])
                        && ! empty($row['purchase_requisition_status'])
                        && ! in_array((string) $row['purchase_requisition_status'], ['completed', 'cancelled'], true);
                })->count(),
            ],
            'rows' => $rows,
        ];
    }

    public function createPurchaseRequisition(int $businessId, int $ruleId, array $payload, int $userId): array
    {
        $advisory = $this->findAdvisory($businessId, $ruleId);
        if (! $advisory) {
            throw new RuntimeException('No purchasing shortage is currently active for this replenishment rule.');
        }

        if (! ($advisory['can_create_requisition'] ?? false)) {
            $ref = (string) ($advisory['purchase_requisition_ref'] ?? ('REQ-' . ($advisory['purchase_requisition_id'] ?? '')));
            throw new RuntimeException("Purchase requisition [{$ref}] already exists for this advisory.");
        }

        $rule = StorageReplenishmentRule::query()
            ->where('business_id', $businessId)
            ->findOrFail($ruleId);

        $requestedQty = round((float) $this->commonUtil->num_uf((string) ($payload['quantity'] ?? $advisory['external_shortage_qty'])), 4);
        if ($requestedQty <= 0) {
            throw new RuntimeException('Requested requisition quantity must be greater than zero.');
        }

        if ($requestedQty > (float) $advisory['external_shortage_qty']) {
            throw new RuntimeException('Requested requisition quantity cannot exceed the current external shortage.');
        }

        return DB::transaction(function () use ($businessId, $rule, $advisory, $payload, $userId, $requestedQty) {
            $document = $this->createAdvisoryDocument($rule, $advisory, $payload, $requestedQty, $userId);

            $transactionData = [
                'business_id' => $businessId,
                'location_id' => (int) $rule->location_id,
                'type' => 'purchase_requisition',
                'status' => 'ordered',
                'created_by' => $userId,
                'transaction_date' => Carbon::now()->toDateTimeString(),
            ];

            if (! empty($payload['delivery_date'])) {
                $transactionData['delivery_date'] = $this->commonUtil->uf_date((string) $payload['delivery_date'], true);
            }

            $refCount = $this->commonUtil->setAndGetReferenceCount('purchase_requisition');
            $transactionData['ref_no'] = $this->commonUtil->generateReferenceNumber('purchase_requisition', $refCount);

            $purchaseRequisition = Transaction::query()->create($transactionData);
            $purchaseRequisition->purchase_lines()->create([
                'variation_id' => $rule->variation_id,
                'product_id' => $rule->product_id,
                'quantity' => $requestedQty,
                'purchase_price_inc_tax' => 0,
                'item_tax' => 0,
                'secondary_unit_quantity' => 0,
            ]);

            StorageDocumentLink::query()->create([
                'business_id' => $businessId,
                'document_id' => $document->id,
                'linked_system' => 'app',
                'linked_type' => 'purchase_requisition',
                'linked_id' => $purchaseRequisition->id,
                'linked_ref' => (string) $purchaseRequisition->ref_no,
                'link_role' => 'source_truth',
                'sync_status' => 'not_required',
                'synced_at' => now(),
                'meta' => [
                    'rule_id' => $rule->id,
                    'requested_qty' => $requestedQty,
                ],
            ]);

            StorageSyncLog::query()->create([
                'business_id' => $businessId,
                'document_id' => $document->id,
                'linked_system' => 'app',
                'action' => 'purchase_requisition_created',
                'status' => 'completed',
                'message' => 'Created purchase requisition ' . $purchaseRequisition->ref_no . ' from planning advisory.',
                'payload' => [
                    'purchase_requisition_id' => $purchaseRequisition->id,
                    'purchase_requisition_ref' => $purchaseRequisition->ref_no,
                    'rule_id' => $rule->id,
                    'requested_qty' => $requestedQty,
                ],
                'created_by' => $userId,
            ]);

            $document->forceFill([
                'status' => 'closed',
                'workflow_state' => 'requisition_created',
                'approval_status' => 'approved',
                'completed_at' => now(),
                'closed_at' => now(),
                'closed_by' => $userId,
                'meta' => array_merge((array) $document->meta, [
                    'purchase_requisition_id' => $purchaseRequisition->id,
                    'purchase_requisition_ref' => $purchaseRequisition->ref_no,
                    'requested_qty' => $requestedQty,
                ]),
            ])->save();

            return [
                'document' => $document->fresh('links'),
                'purchase_requisition' => $purchaseRequisition->fresh('purchase_lines'),
                'advisory' => $advisory,
            ];
        });
    }

    protected function findAdvisory(int $businessId, int $ruleId): ?array
    {
        return collect($this->queueForLocation($businessId)['rows'] ?? [])
            ->first(fn (array $row) => (int) $row['rule_id'] === $ruleId);
    }

    protected function createAdvisoryDocument(
        StorageReplenishmentRule $rule,
        array $advisory,
        array $payload,
        float $requestedQty,
        int $userId
    ): StorageDocument {
        $document = new StorageDocument([
            'business_id' => (int) $rule->business_id,
            'location_id' => (int) $rule->location_id,
            'area_id' => $rule->destination_area_id ?: $rule->source_area_id,
            'document_no' => 'TMP-PRA-' . uniqid(),
            'document_type' => 'purchase_requisition_advisory',
            'source_type' => 'replenishment_rule',
            'source_id' => (int) $rule->id,
            'source_ref' => 'RULE-' . $rule->id,
            'status' => 'open',
            'workflow_state' => 'shortage_review',
            'execution_mode' => 'advisory',
            'sync_status' => 'not_required',
            'approval_status' => 'approved',
            'requested_by' => $userId,
            'approved_by' => $userId,
            'created_by' => $userId,
            'notes' => (string) ($payload['notes'] ?? ''),
            'meta' => [
                'product_label' => $advisory['product_label'] ?? null,
                'sku' => $advisory['sku'] ?? null,
                'source_label' => $advisory['source_label'] ?? null,
                'destination_label' => $advisory['destination_label'] ?? null,
                'external_shortage_qty' => $advisory['external_shortage_qty'] ?? 0,
                'requested_qty' => $requestedQty,
                'delivery_date' => $payload['delivery_date'] ?? null,
            ],
        ]);
        $document->save();

        $document->forceFill([
            'document_no' => 'PRA-' . str_pad((string) $document->id, 6, '0', STR_PAD_LEFT),
        ])->save();

        return $document;
    }
}
