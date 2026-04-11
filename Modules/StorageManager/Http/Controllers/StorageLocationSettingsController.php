<?php

namespace Modules\StorageManager\Http\Controllers;

use App\BusinessLocation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\StorageManager\Entities\StorageArea;
use Modules\StorageManager\Entities\StorageLocationSetting;
use Modules\StorageManager\Services\ReconciliationService;
use Modules\StorageManager\Utils\StorageManagerToolbarNavUtil;

class StorageLocationSettingsController extends Controller
{
    public function __construct(
        protected ReconciliationService $reconciliationService
    ) {
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('storage_manager.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) $request->session()->get('user.business_id');

        $locations = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $settings = StorageLocationSetting::query()
            ->with([
                'defaultReceivingArea:id,location_id,name,code',
                'defaultStagingArea:id,location_id,name,code',
                'defaultPackingArea:id,location_id,name,code',
                'defaultDispatchArea:id,location_id,name,code',
                'defaultQuarantineArea:id,location_id,name,code',
                'defaultDamagedArea:id,location_id,name,code',
                'defaultCountHoldArea:id,location_id,name,code',
            ])
            ->where('business_id', $businessId)
            ->whereIn('location_id', $locations->pluck('id'))
            ->get()
            ->keyBy('location_id');

        $areasByLocation = StorageArea::query()
            ->forBusiness($businessId)
            ->whereIn('location_id', $locations->pluck('id'))
            ->orderBy('location_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'location_id', 'name', 'code', 'area_type'])
            ->groupBy('location_id')
            ->map(function ($areas) {
                return $areas->map(function (StorageArea $area) {
                    $label = trim(collect([
                        $area->code,
                        $area->name,
                        '[' . $this->humanize($area->area_type) . ']',
                    ])->filter()->implode(' - '));

                    return [
                        'id' => (int) $area->id,
                        'label' => $label,
                    ];
                })->values();
            });

        $readinessByLocation = $locations->mapWithKeys(function ($location) use ($businessId) {
            return [
                (int) $location->id => $this->reconciliationService->lotExpiryReadinessAudit(
                    $businessId,
                    (int) $location->id
                ),
            ];
        });

        $viewRows = $locations->map(function ($location) use ($settings, $readinessByLocation) {
            /** @var \Modules\StorageManager\Entities\StorageLocationSetting|null $setting */
            $setting = $settings->get($location->id);
            $readiness = $readinessByLocation->get((int) $location->id, [
                'ready' => true,
                'lot_missing_count' => 0,
                'expiry_missing_count' => 0,
            ]);

            return [
                'location_id' => (int) $location->id,
                'location_name' => $location->name,
                'execution_mode' => $setting->execution_mode ?? 'off',
                'scanner_mode' => $setting->scanner_mode ?? 'browser_ready',
                'bypass_policy' => $setting->bypass_policy ?? 'report_only',
                'require_lot_tracking' => (bool) ($setting->require_lot_tracking ?? false),
                'require_expiry_tracking' => (bool) ($setting->require_expiry_tracking ?? false),
                'enforce_vas_sync' => (bool) ($setting->enforce_vas_sync ?? false),
                'status' => $setting->status ?? 'active',
                'default_receiving_area_id' => $setting->default_receiving_area_id,
                'default_staging_area_id' => $setting->default_staging_area_id,
                'default_packing_area_id' => $setting->default_packing_area_id,
                'default_dispatch_area_id' => $setting->default_dispatch_area_id,
                'default_quarantine_area_id' => $setting->default_quarantine_area_id,
                'default_damaged_area_id' => $setting->default_damaged_area_id,
                'default_count_hold_area_id' => $setting->default_count_hold_area_id,
                'updated_at' => optional($setting?->updated_at)->format('Y-m-d H:i'),
                'lot_ready' => (bool) ($readiness['ready'] ?? false),
                'lot_missing_count' => (int) ($readiness['lot_missing_count'] ?? 0),
                'expiry_missing_count' => (int) ($readiness['expiry_missing_count'] ?? 0),
            ];
        });

        $metrics = [
            'location_count' => $locations->count(),
            'strict_locations' => $viewRows->where('execution_mode', 'strict')->count(),
            'scanner_ready_locations' => $viewRows->where('scanner_mode', 'desktop_scanner_ready')->count(),
            'lot_enforced_locations' => $viewRows->where('require_lot_tracking', true)->count(),
            'vas_sync_locations' => $viewRows->where('enforce_vas_sync', true)->count(),
        ];

        return view('storagemanager::settings.index', [
            'viewRows' => $viewRows,
            'metrics' => $metrics,
            'areasByLocation' => $areasByLocation,
            'executionModes' => $this->optionMap((array) config('storagemanager.execution_modes', [])),
            'scannerModes' => $this->optionMap((array) config('storagemanager.scanner_modes', [])),
            'bypassPolicies' => [
                'report_only' => __('lang_v1.report_only'),
                'alert' => __('lang_v1.alert'),
                'block' => __('lang_v1.block'),
            ],
            'storageToolbarTitle' => __('lang_v1.warehouse_settings'),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.warehouse_settings'), 'url' => null],
            ]),
        ]);
    }

    public function update(Request $request, int $locationId): RedirectResponse
    {
        if (! auth()->user()->can('storage_manager.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) $request->session()->get('user.business_id');
        $location = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->findOrFail($locationId);

        $payload = $request->validate([
            'execution_mode' => ['required', 'string', Rule::in((array) config('storagemanager.execution_modes', []))],
            'scanner_mode' => ['required', 'string', Rule::in((array) config('storagemanager.scanner_modes', []))],
            'bypass_policy' => ['required', 'string', Rule::in(['report_only', 'alert', 'block'])],
            'default_receiving_area_id' => ['nullable', 'integer'],
            'default_staging_area_id' => ['nullable', 'integer'],
            'default_packing_area_id' => ['nullable', 'integer'],
            'default_dispatch_area_id' => ['nullable', 'integer'],
            'default_quarantine_area_id' => ['nullable', 'integer'],
            'default_damaged_area_id' => ['nullable', 'integer'],
            'default_count_hold_area_id' => ['nullable', 'integer'],
            'require_lot_tracking' => ['nullable', 'boolean'],
            'require_expiry_tracking' => ['nullable', 'boolean'],
            'enforce_vas_sync' => ['nullable', 'boolean'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $validatedAreaIds = collect([
            'default_receiving_area_id',
            'default_staging_area_id',
            'default_packing_area_id',
            'default_dispatch_area_id',
            'default_quarantine_area_id',
            'default_damaged_area_id',
            'default_count_hold_area_id',
        ])->mapWithKeys(function ($field) use ($payload) {
            return [$field => ! empty($payload[$field]) ? (int) $payload[$field] : null];
        })->all();

        $selectedAreaIds = collect($validatedAreaIds)->filter()->unique()->values();
        if ($selectedAreaIds->isNotEmpty()) {
            $matchedAreaIds = StorageArea::query()
                ->forBusiness($businessId)
                ->forLocation((int) $location->id)
                ->whereIn('id', $selectedAreaIds)
                ->pluck('id');

            if ($matchedAreaIds->count() !== $selectedAreaIds->count()) {
                throw ValidationException::withMessages([
                    'default_receiving_area_id' => __('lang_v1.storage_area_location_mismatch'),
                ]);
            }
        }

        StorageLocationSetting::query()->updateOrCreate(
            [
                'business_id' => $businessId,
                'location_id' => (int) $location->id,
            ],
            [
                'execution_mode' => $payload['execution_mode'],
                'scanner_mode' => $payload['scanner_mode'],
                'bypass_policy' => $payload['bypass_policy'],
                'default_receiving_area_id' => $validatedAreaIds['default_receiving_area_id'],
                'default_staging_area_id' => $validatedAreaIds['default_staging_area_id'],
                'default_packing_area_id' => $validatedAreaIds['default_packing_area_id'],
                'default_dispatch_area_id' => $validatedAreaIds['default_dispatch_area_id'],
                'default_quarantine_area_id' => $validatedAreaIds['default_quarantine_area_id'],
                'default_damaged_area_id' => $validatedAreaIds['default_damaged_area_id'],
                'default_count_hold_area_id' => $validatedAreaIds['default_count_hold_area_id'],
                'require_lot_tracking' => $request->boolean('require_lot_tracking'),
                'require_expiry_tracking' => $request->boolean('require_expiry_tracking'),
                'enforce_vas_sync' => $request->boolean('enforce_vas_sync'),
                'status' => $payload['status'],
                'meta' => [
                    'updated_via' => 'storagemanager.settings.index',
                ],
            ]
        );

        return redirect()
            ->route('storage-manager.settings.index')
            ->with('status', ['success' => true, 'msg' => __('lang_v1.settings_updated_successfully')]);
    }

    protected function optionMap(array $values): array
    {
        return collect($values)->mapWithKeys(fn ($value) => [$value => $this->humanize((string) $value)])->all();
    }

    protected function humanize(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
