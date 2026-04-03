<?php

namespace Modules\StorageManager\Services;

use App\BusinessLocation;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\StorageManager\Entities\StorageCountLine;
use Modules\StorageManager\Entities\StorageCountSession;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageInventoryMovement;
use Modules\StorageManager\Entities\StorageLocationSetting;
use Modules\StorageManager\Entities\StorageSlot;
use Modules\StorageManager\Entities\StorageSlotStock;

class WarehouseKpiService
{
    public function __construct(
        protected ReconciliationService $reconciliationService,
        protected ReplenishmentService $replenishmentService
    ) {
    }

    public function buildDashboard(int $businessId, ?int $locationId = null): array
    {
        $settings = StorageLocationSetting::query()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->get()
            ->keyBy('location_id');

        $locationIds = $settings->keys()->map(fn ($id) => (int) $id)->values();
        $reconciliationRows = $locationIds
            ->map(fn ($id) => $this->reconciliationService->reconcileLocation($businessId, (int) $id))
            ->values();

        $readinessRows = $locationIds
            ->mapWithKeys(fn ($id) => [(int) $id => $this->reconciliationService->lotExpiryReadinessAudit($businessId, (int) $id)])
            ->all();

        $rolloutRows = $reconciliationRows->map(function (array $row) use ($settings, $readinessRows) {
            $setting = $settings->get($row['location_id']);
            $readiness = $readinessRows[(int) $row['location_id']] ?? ['ready' => true, 'lot_missing_count' => 0, 'expiry_missing_count' => 0];
            $closedCounts = StorageCountSession::query()
                ->where('business_id', $row['business_id'])
                ->where('location_id', $row['location_id'])
                ->whereIn('status', ['closed', 'approved', 'adjusted'])
                ->count();
            $syncSummary = $row['sync_summary'] ?? [];
            $strictReady = ! $row['has_blockers']
                && (bool) ($readiness['ready'] ?? false)
                && $closedCounts >= 2
                && (($syncSummary['sync_errors'] ?? 0) + ($syncSummary['reconcile_errors'] ?? 0) === 0);

            return [
                'location_id' => (int) $row['location_id'],
                'location_name' => (string) ($row['location_name'] ?: ('#' . $row['location_id'])),
                'execution_mode' => (string) ($setting?->execution_mode ?: 'off'),
                'bypass_policy' => (string) ($setting?->bypass_policy ?: 'allow'),
                'mismatch_count' => (int) ($row['mismatch_count'] ?? 0),
                'has_blockers' => (bool) ($row['has_blockers'] ?? false),
                'lot_ready' => (bool) ($readiness['ready'] ?? false),
                'lot_missing_count' => (int) ($readiness['lot_missing_count'] ?? 0),
                'expiry_missing_count' => (int) ($readiness['expiry_missing_count'] ?? 0),
                'closed_count_sessions' => $closedCounts,
                'strict_ready' => $strictReady,
                'strict_ready_reason' => $strictReady ? 'ready' : $this->strictReadinessReason($row, $readiness, $closedCounts, $syncSummary),
            ];
        })->values();

        $enabledLocationIds = $rolloutRows->pluck('location_id')->all();

        return [
            'headlineMetrics' => [
                'configured_locations' => $rolloutRows->count(),
                'strict_ready_locations' => (int) $rolloutRows->where('strict_ready', true)->count(),
                'mismatch_locations' => (int) $rolloutRows->where('has_blockers', true)->count(),
                'bypass_events' => (int) $this->bypassEvents($businessId, $enabledLocationIds)->count(),
            ],
            'kpis' => [
                'occupancy_rate' => $this->occupancyRate($businessId, $enabledLocationIds),
                'count_accuracy_rate' => $this->countAccuracyRate($businessId, $enabledLocationIds),
                'damage_rate' => $this->damageRate($businessId, $enabledLocationIds),
                'transfer_cycle_hours' => $this->transferCycleHours($businessId, $enabledLocationIds),
                'dock_to_stock_hours' => $this->dockToStockHours($businessId, $enabledLocationIds),
            ],
            'rolloutRows' => $rolloutRows,
            'bypassRows' => $this->bypassEvents($businessId, $enabledLocationIds),
            'planningRows' => $this->planningRows($businessId, $enabledLocationIds),
        ];
    }

    protected function occupancyRate(int $businessId, array $locationIds): array
    {
        if (empty($locationIds)) {
            return $this->ratePayload(0, 0, 0, 0, 'bins');
        }

        $totalBins = StorageSlot::query()
            ->where('business_id', $businessId)
            ->when(! empty($locationIds), fn ($query) => $query->whereIn('location_id', $locationIds))
            ->active()
            ->count();

        $occupiedBins = StorageSlotStock::query()
            ->where('business_id', $businessId)
            ->when(! empty($locationIds), fn ($query) => $query->whereIn('location_id', $locationIds))
            ->where('qty_on_hand', '>', 0)
            ->distinct('slot_id')
            ->count('slot_id');

        return $this->ratePayload($occupiedBins, $totalBins, $occupiedBins, $totalBins, 'bins');
    }

    protected function countAccuracyRate(int $businessId, array $locationIds): array
    {
        if (empty($locationIds)) {
            return $this->ratePayload(0, 0, 0, 0, 'count lines');
        }

        $query = StorageCountLine::query()
            ->whereHas('session', function ($sessionQuery) use ($businessId, $locationIds) {
                $sessionQuery->where('business_id', $businessId)
                    ->when(! empty($locationIds), fn ($query) => $query->whereIn('location_id', $locationIds))
                    ->whereIn('status', ['closed', 'approved', 'adjusted'])
                    ->whereDate('created_at', '>=', now()->subDays(60)->toDateString());
            });

        $total = (clone $query)->count();
        $exact = (clone $query)->where('variance_qty', 0)->count();

        return $this->ratePayload($exact, $total, $exact, $total, 'count lines');
    }

    protected function damageRate(int $businessId, array $locationIds): array
    {
        if (empty($locationIds)) {
            return $this->ratePayload(0, 0, 0, 0, 'qty');
        }

        $windowStart = now()->subDays(30);
        $baseQuery = StorageInventoryMovement::query()
            ->where('business_id', $businessId)
            ->when(! empty($locationIds), fn ($query) => $query->whereIn('location_id', $locationIds))
            ->whereDate('created_at', '>=', $windowStart->toDateString());

        $damageQty = (float) (clone $baseQuery)
            ->where('movement_type', 'damage_disposal')
            ->sum('quantity');

        $totalQty = (float) (clone $baseQuery)
            ->whereIn('movement_type', [
                'receipt',
                'putaway',
                'transfer_dispatch',
                'transfer_receipt',
                'replenishment',
                'pick',
                'pack',
                'ship',
                'damage_disposal',
            ])
            ->sum('quantity');

        return $this->ratePayload($damageQty, $totalQty, round($damageQty, 4), round($totalQty, 4), 'qty');
    }

    protected function transferCycleHours(int $businessId, array $locationIds): array
    {
        if (empty($locationIds)) {
            return $this->averagePayload(collect(), 'hours');
        }

        $dispatchDocs = StorageDocument::query()
            ->where('business_id', $businessId)
            ->when(! empty($locationIds), fn ($query) => $query->whereIn('location_id', $locationIds))
            ->where('document_type', 'transfer_dispatch')
            ->whereIn('status', ['closed', 'completed'])
            ->get()
            ->keyBy('source_id');

        $receiptDocs = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'transfer_receipt')
            ->whereIn('status', ['closed', 'completed'])
            ->get();

        $hours = $receiptDocs->map(function (StorageDocument $receipt) use ($dispatchDocs) {
            $dispatch = $dispatchDocs->get($receipt->source_id);
            if (! $dispatch || empty($dispatch->closed_at) || empty($receipt->closed_at)) {
                return null;
            }

            return round(Carbon::parse($dispatch->closed_at)->floatDiffInHours(Carbon::parse($receipt->closed_at)), 2);
        })->filter(fn ($value) => $value !== null)->values();

        return $this->averagePayload($hours, 'hours');
    }

    protected function dockToStockHours(int $businessId, array $locationIds): array
    {
        if (empty($locationIds)) {
            return $this->averagePayload(collect(), 'hours');
        }

        $receiptDocs = StorageDocument::query()
            ->where('business_id', $businessId)
            ->when(! empty($locationIds), fn ($query) => $query->whereIn('location_id', $locationIds))
            ->where('document_type', 'receipt')
            ->whereIn('status', ['closed', 'completed'])
            ->get()
            ->keyBy('id');

        $putawayDocs = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'putaway')
            ->whereIn('status', ['closed', 'completed'])
            ->whereNotNull('parent_document_id')
            ->get();

        $hours = $putawayDocs->map(function (StorageDocument $putaway) use ($receiptDocs) {
            $receipt = $receiptDocs->get($putaway->parent_document_id);
            if (! $receipt || empty($receipt->completed_at) || empty($putaway->closed_at)) {
                return null;
            }

            return round(Carbon::parse($receipt->completed_at)->floatDiffInHours(Carbon::parse($putaway->closed_at)), 2);
        })->filter(fn ($value) => $value !== null)->values();

        return $this->averagePayload($hours, 'hours');
    }

    protected function bypassEvents(int $businessId, array $enabledLocationIds): Collection
    {
        if (empty($enabledLocationIds)) {
            return collect();
        }

        $shipDocs = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('document_type', 'ship')
            ->whereIn('status', ['closed', 'completed'])
            ->get()
            ->keyBy('source_id');

        $locationNames = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->whereIn('id', $enabledLocationIds)
            ->pluck('name', 'id');

        $recentSales = Transaction::query()
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->whereIn('location_id', $enabledLocationIds)
            ->latest('transaction_date')
            ->limit(60)
            ->get();

        $events = collect();
        foreach ($recentSales as $sale) {
            $salesOrderIds = collect((array) ($sale->sales_order_ids ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            if ($salesOrderIds->isEmpty()) {
                $events->push([
                    'event_type' => 'direct_sell_without_execution',
                    'location_name' => (string) ($locationNames[$sale->location_id] ?? ('#' . $sale->location_id)),
                    'reference' => (string) ($sale->invoice_no ?: $sale->ref_no ?: ('SELL-' . $sale->id)),
                    'source_id' => (int) $sale->id,
                    'details' => 'Sell transaction posted in a warehouse-enabled location without a sales-order execution document.',
                    'event_date' => optional($sale->transaction_date)->format('Y-m-d H:i'),
                ]);
                continue;
            }

            foreach ($salesOrderIds as $salesOrderId) {
                if (! $shipDocs->has($salesOrderId)) {
                    $events->push([
                        'event_type' => 'sales_order_invoiced_before_ship',
                        'location_name' => (string) ($locationNames[$sale->location_id] ?? ('#' . $sale->location_id)),
                        'reference' => (string) ($sale->invoice_no ?: $sale->ref_no ?: ('SELL-' . $sale->id)),
                        'source_id' => (int) $sale->id,
                        'details' => 'Sell transaction references a sales order that has no closed warehouse ship document yet.',
                        'event_date' => optional($sale->transaction_date)->format('Y-m-d H:i'),
                    ]);
                }
            }
        }

        return $events->take(20)->values();
    }

    protected function planningRows(int $businessId, array $enabledLocationIds): Collection
    {
        if (empty($enabledLocationIds)) {
            return collect();
        }

        $queue = $this->replenishmentService->queueForLocation($businessId);
        $rows = collect($queue['rows'] ?? []);

        return $rows
            ->filter(function (array $row) use ($enabledLocationIds) {
                return empty($enabledLocationIds) || in_array((int) $row['location_id'], $enabledLocationIds, true);
            })
            ->map(function (array $row) {
                $recommendedQty = (float) ($row['recommended_qty'] ?? 0);
                $sourceQty = (float) ($row['source_qty'] ?? 0);
                $destinationQty = (float) ($row['destination_qty'] ?? 0);
                $externalShortage = max($recommendedQty - $sourceQty, 0);

                return [
                    'rule_id' => (int) ($row['rule_id'] ?? 0),
                    'location_id' => (int) ($row['location_id'] ?? 0),
                    'product_label' => (string) ($row['product_label'] ?? '—'),
                    'sku' => (string) ($row['sku'] ?? '—'),
                    'source_label' => (string) ($row['source_label'] ?? '—'),
                    'destination_label' => (string) ($row['destination_label'] ?? '—'),
                    'destination_qty' => $destinationQty,
                    'source_qty' => $sourceQty,
                    'recommended_qty' => $recommendedQty,
                    'advisory_type' => $externalShortage > 0 ? 'purchasing_review' : 'internal_replenishment',
                    'external_shortage_qty' => round($externalShortage, 4),
                ];
            })
            ->sortByDesc('external_shortage_qty')
            ->take(12)
            ->values();
    }

    protected function strictReadinessReason(array $reconciliationRow, array $readiness, int $closedCounts, array $syncSummary): string
    {
        if ($reconciliationRow['has_blockers']) {
            return 'reconciliation blockers';
        }

        if (! ($readiness['ready'] ?? false)) {
            return 'lot/expiry cleanup pending';
        }

        if ($closedCounts < 2) {
            return 'needs two closed count sessions';
        }

        if ((($syncSummary['sync_errors'] ?? 0) + ($syncSummary['reconcile_errors'] ?? 0)) > 0) {
            return 'sync cleanup pending';
        }

        return 'review required';
    }

    protected function ratePayload(float|int $part, float|int $whole, float|int $displayPart, float|int $displayWhole, string $unit): array
    {
        $value = $whole > 0 ? round(($part / $whole) * 100, 1) : null;

        return [
            'value' => $value,
            'label' => $value === null ? '—' : $value . '%',
            'detail' => $displayWhole > 0 ? ($displayPart . ' / ' . $displayWhole . ' ' . $unit) : 'No data yet',
        ];
    }

    protected function averagePayload(Collection $values, string $unit): array
    {
        $average = $values->isNotEmpty() ? round((float) $values->avg(), 2) : null;

        return [
            'value' => $average,
            'label' => $average === null ? '—' : $average . ' ' . $unit,
            'detail' => $values->count() > 0 ? ($values->count() . ' completed flows') : 'No data yet',
        ];
    }
}
