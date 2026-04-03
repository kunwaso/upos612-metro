<?php

namespace Modules\StorageManager\Http\Controllers;

use App\BusinessLocation;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageInventoryMovement;
use Modules\StorageManager\Entities\StorageLocationSetting;
use Modules\StorageManager\Entities\StorageSlotStock;
use Modules\StorageManager\Entities\StorageSyncLog;
use Modules\StorageManager\Entities\StorageTask;
use Modules\StorageManager\Services\ReconciliationService;
use Modules\StorageManager\Services\WarehouseSyncService;

class ControlTowerController extends Controller
{
    public function __construct(
        protected ReconciliationService $reconciliationService,
        protected WarehouseSyncService $warehouseSyncService
    ) {
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('storage_manager.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) $request->session()->get('user.business_id');
        $locationId = (int) $request->input('location_id', 0);

        $locations = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id');

        $configuredSettings = StorageLocationSetting::query()
            ->where('business_id', $businessId)
            ->when($locationId > 0, fn ($query) => $query->where('location_id', $locationId))
            ->get();

        $locationIds = $locationId > 0
            ? collect([$locationId])
            : $locations->keys()->map(fn ($id) => (int) $id)->values();

        $locationRows = $locationIds->map(function ($currentLocationId) use ($businessId) {
            return $this->reconciliationService->reconcileLocation($businessId, (int) $currentLocationId);
        });

        $metrics = [
            'configured_locations' => $configuredSettings->count(),
            'open_documents' => StorageDocument::query()
                ->where('business_id', $businessId)
                ->when($locationId > 0, fn ($query) => $query->where('location_id', $locationId))
                ->whereNotIn('status', ['closed', 'completed', 'cancelled'])
                ->count(),
            'open_tasks' => StorageTask::query()
                ->where('business_id', $businessId)
                ->when($locationId > 0, fn ($query) => $query->where('location_id', $locationId))
                ->whereNotIn('status', ['done', 'cancelled'])
                ->count(),
            'sync_errors' => StorageDocument::query()
                ->where('business_id', $businessId)
                ->when($locationId > 0, fn ($query) => $query->where('location_id', $locationId))
                ->whereIn('sync_status', ['sync_error', 'reconcile_error'])
                ->count(),
            'bypass_alerts' => StorageLocationSetting::query()
                ->where('business_id', $businessId)
                ->when($locationId > 0, fn ($query) => $query->where('location_id', $locationId))
                ->whereIn('bypass_policy', ['alert', 'block'])
                ->count(),
            'available_bins' => StorageSlotStock::query()
                ->where('business_id', $businessId)
                ->when($locationId > 0, fn ($query) => $query->where('location_id', $locationId))
                ->where('inventory_status', 'available')
                ->where('qty_on_hand', '>', 0)
                ->count(),
            'quarantine_bins' => StorageSlotStock::query()
                ->where('business_id', $businessId)
                ->when($locationId > 0, fn ($query) => $query->where('location_id', $locationId))
                ->where('inventory_status', 'quarantine')
                ->where('qty_on_hand', '>', 0)
                ->count(),
            'mismatch_locations' => $locationRows->filter(fn (array $row) => $row['has_blockers'])->count(),
        ];

        $recentDocuments = StorageDocument::query()
            ->where('business_id', $businessId)
            ->when($locationId > 0, fn ($query) => $query->where('location_id', $locationId))
            ->latest('id')
            ->limit(12)
            ->get();

        $movementRows = StorageInventoryMovement::query()
            ->where('business_id', $businessId)
            ->when($locationId > 0, fn ($query) => $query->where('location_id', $locationId))
            ->latest('id')
            ->limit(15)
            ->get();

        $recentSyncLogs = StorageSyncLog::query()
            ->where('business_id', $businessId)
            ->latest('id')
            ->limit(12)
            ->get();

        $readinessRows = $locationIds->mapWithKeys(function ($currentLocationId) use ($businessId) {
            return [
                (int) $currentLocationId => $this->reconciliationService->lotExpiryReadinessAudit(
                    $businessId,
                    (int) $currentLocationId
                ),
            ];
        })->all();

        return view('storagemanager::control_tower.index', compact(
            'locations',
            'locationId',
            'metrics',
            'recentDocuments',
            'movementRows',
            'recentSyncLogs',
            'locationRows',
            'readinessRows'
        ));
    }

    public function reconcileLocation(Request $request): JsonResponse
    {
        if (! auth()->user()->can('storage_manager.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) $request->session()->get('user.business_id');
        $validated = $request->validate([
            'location_id' => ['required', 'integer'],
        ]);

        return response()->json(
            $this->reconciliationService->reconcileLocation($businessId, (int) $validated['location_id'])
        );
    }

    public function retryVasSync(Request $request): JsonResponse|RedirectResponse
    {
        if (! auth()->user()->can('storage_manager.manage') && ! auth()->user()->can('storage_manager.approve')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) $request->session()->get('user.business_id');
        $validated = $request->validate([
            'document_id' => ['required', 'integer'],
        ]);

        $document = StorageDocument::query()
            ->where('business_id', $businessId)
            ->findOrFail((int) $validated['document_id']);

        try {
            $result = $this->warehouseSyncService->syncDocument($document, (int) auth()->id());
        } catch (\Throwable $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'sync_error',
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('storage-manager.control-tower.index', ['location_id' => $document->location_id])
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()
            ->route('storage-manager.control-tower.index', ['location_id' => $document->location_id])
            ->with('status', ['success' => ! ($result['has_blockers'] ?? false), 'msg' => __('lang_v1.vas_sync_retry_completed')]);
    }
}
