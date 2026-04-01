<?php

namespace Modules\VasAccounting\Services\Inventory;

use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Contracts\InventoryCostServiceInterface;
use Modules\VasAccounting\Domain\AuditCompliance\Models\FinanceAuditEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceAccountingEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocumentLine;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceInventoryCostLayer;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceInventoryCostSettlement;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceInventoryMovement;
use RuntimeException;

class InventoryCostService implements InventoryCostServiceInterface
{
    protected function costingMethod(): string
    {
        return (string) config('vasaccounting.inventory_ledger_defaults.costing_method', 'weighted_average');
    }

    protected function layerType(): string
    {
        return 'weighted_average_pool';
    }

    public function resolveDocumentProfile(FinanceDocument $document): ?array
    {
        return match ($document->document_type) {
            'goods_receipt' => ['movement_type' => 'receipt', 'direction' => 'in'],
            'delivery' => ['movement_type' => 'issue', 'direction' => 'out'],
            default => null,
        };
    }

    public function buildMovementPlans(FinanceDocument $document, array $poolSnapshots = []): array
    {
        $profile = $this->resolveDocumentProfile($document);
        if (! $profile) {
            return ['movements' => [], 'layers' => []];
        }

        $snapshots = $poolSnapshots;
        $movements = [];
        $layers = [];

        foreach ($this->inventoryLines($document) as $line) {
            $quantity = $this->normalizeAmount((string) $line->quantity);
            if (! $line->product_id || bccomp($quantity, '0.0000', 4) !== 1) {
                continue;
            }

            $locationId = $this->resolveLocationId($document, $line);
            $layerKey = $this->layerKey(
                (int) $document->business_id,
                (int) $line->product_id,
                $locationId,
                (string) ($document->currency_code ?: config('vasaccounting.book_currency', 'VND'))
            );

            $snapshot = $snapshots[$layerKey] ?? $this->emptyLayerSnapshot(
                (int) $document->business_id,
                (int) $line->product_id,
                $locationId,
                (string) ($document->currency_code ?: config('vasaccounting.book_currency', 'VND'))
            );

            $beforeQty = $snapshot['quantity_on_hand'];
            $beforeValue = $snapshot['total_value_on_hand'];
            $movement = [
                'layer_key' => $layerKey,
                'document_line_id' => $line->id,
                'product_id' => (int) $line->product_id,
                'business_location_id' => $locationId,
                'movement_type' => $profile['movement_type'],
                'direction' => $profile['direction'],
                'quantity' => $quantity,
                'currency_code' => $snapshot['currency_code'],
                'movement_date' => $document->posting_date ?: $document->document_date,
                'meta' => [
                    'before_quantity_on_hand' => $beforeQty,
                    'before_total_value_on_hand' => $beforeValue,
                ],
            ];

            if ($profile['direction'] === 'in') {
                $unitCost = $this->unitCostFromInboundLine($line, $quantity);
                $totalCost = $this->normalizeAmount(bcmul($quantity, $unitCost, 4));
                $snapshot['quantity_in'] = $this->normalizeAmount(bcadd($snapshot['quantity_in'], $quantity, 4));
                $snapshot['total_value_in'] = $this->normalizeAmount(bcadd($snapshot['total_value_in'], $totalCost, 4));
                $snapshot['quantity_on_hand'] = $this->normalizeAmount(bcadd($snapshot['quantity_on_hand'], $quantity, 4));
                $snapshot['total_value_on_hand'] = $this->normalizeAmount(bcadd($snapshot['total_value_on_hand'], $totalCost, 4));
                $snapshot['average_unit_cost'] = $this->averageCost($snapshot['quantity_on_hand'], $snapshot['total_value_on_hand']);

                $movement['unit_cost'] = $unitCost;
                $movement['total_cost'] = $totalCost;
            } else {
                $allowNegative = (bool) config('vasaccounting.inventory_ledger_defaults.allow_negative_stock', false);
                if (! $allowNegative && bccomp($beforeQty, $quantity, 4) === -1) {
                    throw new RuntimeException(sprintf(
                        'Inventory on hand is insufficient for product [%d] at location [%s].',
                        (int) $line->product_id,
                        $locationId === null ? 'n/a' : (string) $locationId
                    ));
                }

                $unitCost = $snapshot['average_unit_cost'];
                $totalCost = $this->normalizeAmount(bcmul($quantity, $unitCost, 4));
                $snapshot['quantity_out'] = $this->normalizeAmount(bcadd($snapshot['quantity_out'], $quantity, 4));
                $snapshot['total_value_out'] = $this->normalizeAmount(bcadd($snapshot['total_value_out'], $totalCost, 4));
                $snapshot['quantity_on_hand'] = $this->normalizeAmount(bcsub($snapshot['quantity_on_hand'], $quantity, 4));
                $snapshot['total_value_on_hand'] = $this->normalizeAmount(bcsub($snapshot['total_value_on_hand'], $totalCost, 4));
                $snapshot['average_unit_cost'] = $this->averageCost($snapshot['quantity_on_hand'], $snapshot['total_value_on_hand']);

                $movement['unit_cost'] = $unitCost;
                $movement['total_cost'] = $totalCost;
                $movement['meta']['cost_source'] = $this->layerType();
            }

            $movement['meta']['after_quantity_on_hand'] = $snapshot['quantity_on_hand'];
            $movement['meta']['after_total_value_on_hand'] = $snapshot['total_value_on_hand'];

            $movements[] = $movement;
            $layers[$layerKey] = $snapshot;
            $snapshots[$layerKey] = $snapshot;
        }

        return [
            'movements' => $movements,
            'layers' => array_values($layers),
        ];
    }

    public function syncPostedDocument(FinanceDocument $document, FinanceAccountingEvent $event, ActionContext $context): void
    {
        $profile = $this->resolveDocumentProfile($document);
        if (! $profile) {
            return;
        }

        if (FinanceInventoryMovement::query()->where('accounting_event_id', $event->id)->exists()) {
            return;
        }

        $document->loadMissing('lines');
        $poolLayers = $this->loadPoolLayers($document);
        $plans = $this->buildMovementPlans($document, $poolLayers);
        if (empty($plans['movements'])) {
            return;
        }

        $layersByKey = [];
        foreach ($plans['layers'] as $layerPlan) {
            $layer = $this->persistLayerPlan($layerPlan, $document);
            $layersByKey[$layerPlan['business_id'] . '|' . $layerPlan['product_id'] . '|' . ($layerPlan['business_location_id'] ?? 'null') . '|' . $layerPlan['currency_code']] = $layer;
        }

        foreach ($plans['movements'] as $movementPlan) {
            $layer = $layersByKey[$movementPlan['layer_key']] ?? null;
            if (! $layer) {
                throw new RuntimeException('Unable to resolve inventory cost layer for movement plan.');
            }

            $movement = FinanceInventoryMovement::query()->create([
                'business_id' => $document->business_id,
                'document_id' => $document->id,
                'document_line_id' => $movementPlan['document_line_id'],
                'accounting_event_id' => $event->id,
                'product_id' => $movementPlan['product_id'],
                'business_location_id' => $movementPlan['business_location_id'],
                'movement_type' => $movementPlan['movement_type'],
                'direction' => $movementPlan['direction'],
                'status' => 'active',
                'quantity' => $movementPlan['quantity'],
                'unit_cost' => $movementPlan['unit_cost'],
                'total_cost' => $movementPlan['total_cost'],
                'currency_code' => $movementPlan['currency_code'],
                'movement_date' => $movementPlan['movement_date'],
                'meta' => $movementPlan['meta'],
            ]);

            if ($movement->direction === 'out') {
                FinanceInventoryCostSettlement::query()->create([
                    'business_id' => $document->business_id,
                    'issue_movement_id' => $movement->id,
                    'cost_layer_id' => $layer->id,
                    'settled_quantity' => $movement->quantity,
                    'settled_value' => $movement->total_cost,
                    'unit_cost' => $movement->unit_cost,
                    'meta' => ['costing_method' => $layer->costing_method],
                ]);
            }
        }

        $this->recordAudit($document, 'inventory.movements_synced', $context, null, [
            'movement_count' => count($plans['movements']),
            'layer_count' => count($plans['layers']),
        ]);
    }

    public function reverseDocument(FinanceDocument $document, FinanceAccountingEvent $reversalEvent, ActionContext $context): void
    {
        $profile = $this->resolveDocumentProfile($document);
        if (! $profile) {
            return;
        }

        $activeMovements = FinanceInventoryMovement::query()
            ->where('document_id', $document->id)
            ->where('status', 'active')
            ->whereNull('reversal_movement_id')
            ->get();

        if ($activeMovements->isEmpty()) {
            return;
        }

        foreach ($activeMovements as $movement) {
            $layer = FinanceInventoryCostLayer::query()
                ->where('business_id', $movement->business_id)
                ->where('product_id', $movement->product_id)
                ->where(function ($query) use ($movement) {
                    if ($movement->business_location_id) {
                        $query->where('business_location_id', $movement->business_location_id);
                    } else {
                        $query->whereNull('business_location_id');
                    }
                })
                ->where('currency_code', $movement->currency_code)
                ->where('costing_method', $this->costingMethod())
                ->where('layer_type', $this->layerType())
                ->first();

            if (! $layer) {
                throw new RuntimeException('Cannot reverse inventory movement because no weighted-average pool layer exists.');
            }

            if ($movement->direction === 'in') {
                $layer->quantity_in = $this->normalizeAmount(bcsub((string) $layer->quantity_in, (string) $movement->quantity, 4));
                $layer->total_value_in = $this->normalizeAmount(bcsub((string) $layer->total_value_in, (string) $movement->total_cost, 4));
                $layer->quantity_on_hand = $this->normalizeAmount(bcsub((string) $layer->quantity_on_hand, (string) $movement->quantity, 4));
                $layer->total_value_on_hand = $this->normalizeAmount(bcsub((string) $layer->total_value_on_hand, (string) $movement->total_cost, 4));
            } else {
                $layer->quantity_out = $this->normalizeAmount(bcsub((string) $layer->quantity_out, (string) $movement->quantity, 4));
                $layer->total_value_out = $this->normalizeAmount(bcsub((string) $layer->total_value_out, (string) $movement->total_cost, 4));
                $layer->quantity_on_hand = $this->normalizeAmount(bcadd((string) $layer->quantity_on_hand, (string) $movement->quantity, 4));
                $layer->total_value_on_hand = $this->normalizeAmount(bcadd((string) $layer->total_value_on_hand, (string) $movement->total_cost, 4));
            }

            $layer->average_unit_cost = $this->averageCost((string) $layer->quantity_on_hand, (string) $layer->total_value_on_hand);
            $layer->status = bccomp((string) $layer->quantity_on_hand, '0.0000', 4) === 1 ? 'active' : 'exhausted';
            $layer->save();

            $reversalMovement = FinanceInventoryMovement::query()->create([
                'business_id' => $movement->business_id,
                'document_id' => $movement->document_id,
                'document_line_id' => $movement->document_line_id,
                'accounting_event_id' => $reversalEvent->id,
                'product_id' => $movement->product_id,
                'business_location_id' => $movement->business_location_id,
                'movement_type' => $movement->movement_type,
                'direction' => $movement->direction === 'in' ? 'out' : 'in',
                'status' => 'active',
                'quantity' => $movement->quantity,
                'unit_cost' => $movement->unit_cost,
                'total_cost' => $movement->total_cost,
                'currency_code' => $movement->currency_code,
                'movement_date' => $reversalEvent->posting_date,
                'meta' => array_merge((array) $movement->meta, ['reversal_of_movement_id' => $movement->id]),
            ]);

            $movement->status = 'reversed';
            $movement->reversal_movement_id = $reversalMovement->id;
            $movement->reversed_at = now();
            $movement->reversed_by = $context->userId();
            $movement->save();
        }

        $this->recordAudit($document, 'inventory.movements_reversed', $context, null, [
            'reversal_event_id' => $reversalEvent->id,
            'movement_count' => $activeMovements->count(),
        ]);
    }

    protected function persistLayerPlan(array $layerPlan, FinanceDocument $document): FinanceInventoryCostLayer
    {
        $layer = FinanceInventoryCostLayer::query()->firstOrNew([
            'business_id' => $layerPlan['business_id'],
            'product_id' => $layerPlan['product_id'],
            'business_location_id' => $layerPlan['business_location_id'],
            'currency_code' => $layerPlan['currency_code'],
            'costing_method' => $layerPlan['costing_method'],
            'layer_type' => $layerPlan['layer_type'],
        ]);

        $layer->source_document_id = $document->id;
        $layer->quantity_in = $layerPlan['quantity_in'];
        $layer->quantity_out = $layerPlan['quantity_out'];
        $layer->quantity_on_hand = $layerPlan['quantity_on_hand'];
        $layer->total_value_in = $layerPlan['total_value_in'];
        $layer->total_value_out = $layerPlan['total_value_out'];
        $layer->total_value_on_hand = $layerPlan['total_value_on_hand'];
        $layer->average_unit_cost = $layerPlan['average_unit_cost'];
        $layer->status = bccomp($layerPlan['quantity_on_hand'], '0.0000', 4) === 1 ? 'active' : 'exhausted';
        $layer->meta = ['inventory_v2' => true];
        $layer->save();

        return $layer;
    }

    protected function loadPoolLayers(FinanceDocument $document): array
    {
        $document->loadMissing('lines');
        $productIds = $document->lines->pluck('product_id')->filter()->unique()->values();
        if ($productIds->isEmpty()) {
            return [];
        }

        return FinanceInventoryCostLayer::query()
            ->where('business_id', $document->business_id)
            ->whereIn('product_id', $productIds)
            ->where('costing_method', $this->costingMethod())
            ->where('layer_type', $this->layerType())
            ->get()
            ->mapWithKeys(function (FinanceInventoryCostLayer $layer) {
                return [$this->layerKey(
                    (int) $layer->business_id,
                    (int) $layer->product_id,
                    $layer->business_location_id ? (int) $layer->business_location_id : null,
                    (string) $layer->currency_code
                ) => [
                    'business_id' => (int) $layer->business_id,
                    'product_id' => (int) $layer->product_id,
                    'business_location_id' => $layer->business_location_id ? (int) $layer->business_location_id : null,
                    'currency_code' => (string) $layer->currency_code,
                    'costing_method' => (string) $layer->costing_method,
                    'layer_type' => (string) $layer->layer_type,
                    'quantity_in' => $this->normalizeAmount((string) $layer->quantity_in),
                    'quantity_out' => $this->normalizeAmount((string) $layer->quantity_out),
                    'quantity_on_hand' => $this->normalizeAmount((string) $layer->quantity_on_hand),
                    'total_value_in' => $this->normalizeAmount((string) $layer->total_value_in),
                    'total_value_out' => $this->normalizeAmount((string) $layer->total_value_out),
                    'total_value_on_hand' => $this->normalizeAmount((string) $layer->total_value_on_hand),
                    'average_unit_cost' => $this->normalizeAmount((string) $layer->average_unit_cost),
                ]];
            })
            ->all();
    }

    protected function inventoryLines(FinanceDocument $document)
    {
        return $document->relationLoaded('lines') ? $document->lines : $document->lines()->get();
    }

    protected function resolveLocationId(FinanceDocument $document, FinanceDocumentLine $line): ?int
    {
        $locationId = $line->business_location_id
            ?? data_get($line->dimensions, 'business_location_id')
            ?? $document->business_location_id;

        return $locationId ? (int) $locationId : null;
    }

    protected function unitCostFromInboundLine(FinanceDocumentLine $line, string $quantity): string
    {
        $lineAmount = $this->normalizeAmount((string) $line->line_amount);
        if (bccomp($lineAmount, '0.0000', 4) === 1 && bccomp($quantity, '0.0000', 4) === 1) {
            return $this->normalizeAmount(bcdiv($lineAmount, $quantity, 4));
        }

        $unitPrice = $this->normalizeAmount((string) ($line->unit_price ?? '0'));
        if (bccomp($unitPrice, '0.0000', 4) === 1) {
            return $unitPrice;
        }

        return '0.0000';
    }

    protected function averageCost(string $quantity, string $value): string
    {
        if (bccomp($quantity, '0.0000', 4) !== 1) {
            return '0.0000';
        }

        return $this->normalizeAmount(bcdiv($value, $quantity, 4));
    }

    protected function emptyLayerSnapshot(int $businessId, int $productId, ?int $locationId, string $currencyCode): array
    {
        return [
            'business_id' => $businessId,
            'product_id' => $productId,
            'business_location_id' => $locationId,
            'currency_code' => $currencyCode,
            'costing_method' => $this->costingMethod(),
            'layer_type' => $this->layerType(),
            'quantity_in' => '0.0000',
            'quantity_out' => '0.0000',
            'quantity_on_hand' => '0.0000',
            'total_value_in' => '0.0000',
            'total_value_out' => '0.0000',
            'total_value_on_hand' => '0.0000',
            'average_unit_cost' => '0.0000',
        ];
    }

    protected function layerKey(int $businessId, int $productId, ?int $locationId, string $currencyCode): string
    {
        return implode('|', [$businessId, $productId, $locationId ?? 'null', $currencyCode]);
    }

    protected function normalizeAmount(string $amount): string
    {
        return number_format((float) $amount, 4, '.', '');
    }

    protected function recordAudit(
        FinanceDocument $document,
        string $eventType,
        ActionContext $context,
        $beforeState,
        $afterState
    ): void {
        FinanceAuditEvent::query()->create([
            'business_id' => $document->business_id,
            'document_id' => $document->id,
            'event_type' => $eventType,
            'actor_id' => $context->userId(),
            'reason' => $context->reason(),
            'request_id' => $context->requestId(),
            'ip_address' => $context->ipAddress(),
            'user_agent' => $context->userAgent(),
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'meta' => $context->meta(),
            'acted_at' => now(),
        ]);
    }
}
