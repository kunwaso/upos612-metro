<?php

namespace Modules\StorageManager\Services;

use App\BusinessLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageLocationSetting;
use Modules\StorageManager\Entities\StorageSlotStock;

class ReconciliationService
{
    public function reconcileLocation(int $businessId, int $locationId): array
    {
        $slotRows = StorageSlotStock::query()
            ->select('variation_id', DB::raw('SUM(qty_on_hand) as qty_on_hand'))
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->groupBy('variation_id')
            ->get()
            ->keyBy(fn ($row) => (string) ($row->variation_id ?? 0));

        $sourceRows = collect();
        if (Schema::hasTable('variation_location_details')) {
            $sourceRows = DB::table('variation_location_details')
                ->select('variation_id', DB::raw('SUM(qty_available) as qty_available'))
                ->where('location_id', $locationId)
                ->groupBy('variation_id')
                ->get()
                ->keyBy(fn ($row) => (string) ($row->variation_id ?? 0));
        }

        $keys = $slotRows->keys()->merge($sourceRows->keys())->unique()->values();
        $mismatches = $keys->map(function ($key) use ($slotRows, $sourceRows) {
            $slotQty = round((float) data_get($slotRows->get($key), 'qty_on_hand', 0), 4);
            $sourceQty = round((float) data_get($sourceRows->get($key), 'qty_available', 0), 4);
            $delta = round($slotQty - $sourceQty, 4);

            return [
                'variation_id' => (int) $key,
                'slot_qty' => $slotQty,
                'source_qty' => $sourceQty,
                'delta' => $delta,
            ];
        })->filter(fn (array $row) => $row['delta'] !== 0.0)->values();

        $settings = StorageLocationSetting::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->first();

        $syncSummary = $this->documentSyncSummary($businessId, $locationId, (bool) ($settings->enforce_vas_sync ?? false));

        return [
            'business_id' => $businessId,
            'location_id' => $locationId,
            'location_name' => BusinessLocation::query()->where('id', $locationId)->value('name'),
            'execution_mode' => $settings->execution_mode ?? 'off',
            'slot_total' => round((float) $slotRows->sum('qty_on_hand'), 4),
            'source_total' => round((float) $sourceRows->sum('qty_available'), 4),
            'mismatch_count' => $mismatches->count(),
            'mismatches' => $mismatches,
            'has_blockers' => $mismatches->isNotEmpty() || (bool) ($syncSummary['has_errors'] ?? false),
            'sync_summary' => $syncSummary,
        ];
    }

    public function lotExpiryReadinessAudit(int $businessId, ?int $locationId = null): array
    {
        $query = DB::table('purchase_lines as pl')
            ->join('transactions as t', 't.id', '=', 'pl.transaction_id')
            ->join('products as p', 'p.id', '=', 'pl.product_id')
            ->where('t.business_id', $businessId)
            ->whereIn('t.type', ['purchase', 'opening_stock', 'purchase_transfer'])
            ->whereRaw('(COALESCE(pl.quantity, 0) - COALESCE(pl.quantity_sold, 0) - COALESCE(pl.quantity_adjusted, 0) - COALESCE(pl.quantity_returned, 0)) > 0');

        if ($locationId) {
            $query->where('t.location_id', $locationId);
        }

        $rows = $query->select(
            't.location_id',
            'pl.product_id',
            'pl.variation_id',
            'pl.lot_number',
            'pl.exp_date',
            'p.expiry_period',
            'p.expiry_period_type'
        )->get();

        $lotMissing = $rows->filter(fn ($row) => empty($row->lot_number))->count();
        $expiryMissing = $rows->filter(function ($row) {
            $expiryTracked = ! empty($row->expiry_period) || ! empty($row->expiry_period_type);
            return $expiryTracked && empty($row->exp_date);
        })->count();

        return [
            'business_id' => $businessId,
            'location_id' => $locationId,
            'lot_missing_count' => $lotMissing,
            'expiry_missing_count' => $expiryMissing,
            'tracked_rows' => $rows->count(),
            'ready' => $lotMissing === 0 && $expiryMissing === 0,
        ];
    }

    public function controlTowerSummary(int $businessId): array
    {
        $settings = StorageLocationSetting::query()
            ->where('business_id', $businessId)
            ->get();

        $locationIds = $settings->pluck('location_id')->unique()->values();
        $reconciliations = $locationIds->map(fn ($locationId) => $this->reconcileLocation($businessId, (int) $locationId));

        return [
            'configured_locations' => $settings->count(),
            'active_locations' => $settings->where('status', 'active')->count(),
            'strict_locations' => $settings->where('execution_mode', 'strict')->count(),
            'mismatch_locations' => $reconciliations->filter(fn (array $row) => $row['has_blockers'])->count(),
            'pending_sync_documents' => StorageDocument::query()
                ->where('business_id', $businessId)
                ->whereIn('sync_status', ['pending_sync', 'sync_error', 'reconcile_error'])
                ->count(),
            'location_rows' => $reconciliations,
        ];
    }

    protected function documentSyncSummary(int $businessId, int $locationId, bool $enforceVasSync): array
    {
        $documents = StorageDocument::query()
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->get(['id', 'status', 'sync_status']);

        $errorCount = $documents->whereIn('sync_status', ['sync_error', 'reconcile_error'])->count();
        $completedNotPosted = $documents
            ->whereIn('status', ['completed', 'closed'])
            ->whereNotIn('sync_status', ['posted', 'not_required'])
            ->count();
        $unsyncedCompleted = $enforceVasSync
            ? $documents->whereIn('status', ['completed', 'closed'])->where('sync_status', 'not_required')->count()
            : 0;

        return [
            'document_count' => $documents->count(),
            'pending_sync' => $documents->where('sync_status', 'pending_sync')->count(),
            'sync_errors' => $documents->where('sync_status', 'sync_error')->count(),
            'reconcile_errors' => $documents->where('sync_status', 'reconcile_error')->count(),
            'posted' => $documents->where('sync_status', 'posted')->count(),
            'completed_not_posted' => $completedNotPosted,
            'unsynced_completed' => $unsyncedCompleted,
            'enforce_vas_sync' => $enforceVasSync,
            'has_errors' => $enforceVasSync && ($errorCount > 0 || $unsyncedCompleted > 0),
        ];
    }
}
