<?php

namespace Modules\StorageManager\Http\Controllers;

use App\BusinessLocation;
use App\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\StorageManager\Entities\StorageArea;
use Modules\StorageManager\Http\Requests\StoreStorageAreaRequest;
use Modules\StorageManager\Http\Requests\UpdateStorageAreaRequest;
use Modules\StorageManager\Utils\StorageManagerToolbarNavUtil;

class StorageAreaController extends Controller
{
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

        $capacityByArea = DB::table('storage_slots')
            ->select('area_id', DB::raw('COUNT(*) as slot_count'), DB::raw('SUM(max_capacity) as capacity_sum'))
            ->where('business_id', $businessId)
            ->when($locationId > 0, fn ($query) => $query->where('location_id', $locationId))
            ->whereNotNull('area_id')
            ->groupBy('area_id')
            ->get()
            ->keyBy('area_id');

        $areas = StorageArea::query()
            ->forBusiness($businessId)
            ->with(['location:id,name', 'category:id,name'])
            ->when($locationId > 0, fn ($query) => $query->forLocation($locationId))
            ->orderBy('location_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $rows = $areas->map(function (StorageArea $area) use ($capacityByArea) {
            $capacity = $capacityByArea->get($area->id);
            $notes = data_get($area->meta, 'notes');

            return [
                'id' => (int) $area->id,
                'name' => $area->name,
                'code' => $area->code,
                'area_type' => $area->area_type,
                'category_name' => optional($area->category)->name,
                'location_name' => optional($area->location)->name ?? '—',
                'sort_order' => (int) ($area->sort_order ?? 0),
                'status' => $area->status ?? 'active',
                'slot_count' => (int) data_get($capacity, 'slot_count', 0),
                'capacity_sum' => (int) data_get($capacity, 'capacity_sum', 0),
                'notes' => $notes,
            ];
        });

        $metrics = [
            'area_count' => $rows->count(),
            'receiving_count' => $rows->where('area_type', 'receiving')->count(),
            'quarantine_count' => $rows->where('area_type', 'quarantine')->count(),
        ];

        return view('storagemanager::areas.index', [
            'rows' => $rows,
            'locations' => $locations,
            'locationId' => $locationId,
            'metrics' => $metrics,
            'storageToolbarTitle' => __('lang_v1.warehouse_areas'),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.warehouse_areas'), 'url' => null],
            ], $locationId > 0 ? $locationId : null),
            'storageToolbarLocationId' => $locationId > 0 ? $locationId : null,
        ]);
    }

    public function create(Request $request)
    {
        if (! auth()->user()->can('storage_manager.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) $request->session()->get('user.business_id');

        return view('storagemanager::areas.create', [
            'locations' => $this->locations($businessId),
            'categories' => $this->categories($businessId),
            'area' => null,
            'areaTypes' => $this->areaTypes(),
            'storageToolbarTitle' => __('lang_v1.add_warehouse_area'),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.warehouse_areas'), 'url' => route('storage-manager.areas.index')],
                ['label' => __('lang_v1.add_warehouse_area'), 'url' => null],
            ]),
        ]);
    }

    public function store(StoreStorageAreaRequest $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        StorageArea::query()->create([
            'business_id' => $businessId,
            'location_id' => (int) $request->input('location_id'),
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : null,
            'code' => strtoupper(trim((string) $request->input('code'))),
            'name' => $request->input('name'),
            'area_type' => $request->input('area_type'),
            'status' => $request->input('status', 'active'),
            'barcode' => $request->input('barcode'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'meta' => [
                'notes' => $request->input('notes'),
            ],
        ]);

        return redirect()
            ->route('storage-manager.areas.index')
            ->with('status', ['success' => true, 'msg' => __('lang_v1.storage_area_saved')]);
    }

    public function edit(Request $request, int $id)
    {
        if (! auth()->user()->can('storage_manager.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) $request->session()->get('user.business_id');

        $area = $this->findArea($businessId, $id);
        $locId = (int) $area->location_id;

        return view('storagemanager::areas.edit', [
            'locations' => $this->locations($businessId),
            'categories' => $this->categories($businessId),
            'area' => $area,
            'areaTypes' => $this->areaTypes(),
            'storageToolbarTitle' => __('lang_v1.edit_warehouse_area'),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.warehouse_areas'), 'url' => route('storage-manager.areas.index')],
                ['label' => __('lang_v1.edit_warehouse_area'), 'url' => null],
            ], $locId > 0 ? $locId : null),
            'storageToolbarLocationId' => $locId > 0 ? $locId : null,
        ]);
    }

    public function update(UpdateStorageAreaRequest $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $area = $this->findArea($businessId, $id);

        $area->update([
            'location_id' => (int) $request->input('location_id'),
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : null,
            'code' => strtoupper(trim((string) $request->input('code'))),
            'name' => $request->input('name'),
            'area_type' => $request->input('area_type'),
            'status' => $request->input('status', 'active'),
            'barcode' => $request->input('barcode'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'meta' => array_merge((array) $area->meta, [
                'notes' => $request->input('notes'),
            ]),
        ]);

        return redirect()
            ->route('storage-manager.areas.index')
            ->with('status', ['success' => true, 'msg' => __('lang_v1.storage_area_updated')]);
    }

    protected function areaTypes(): array
    {
        return collect((array) config('storagemanager.area_types', []))
            ->mapWithKeys(fn ($value) => [$value => ucwords(str_replace('_', ' ', $value))])
            ->all();
    }

    protected function findArea(int $businessId, int $id): StorageArea
    {
        return StorageArea::query()
            ->forBusiness($businessId)
            ->findOrFail($id);
    }

    protected function locations(int $businessId)
    {
        return BusinessLocation::query()
            ->where('business_id', $businessId)
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function categories(int $businessId)
    {
        return Category::query()
            ->where('business_id', $businessId)
            ->where('category_type', 'product')
            ->where('parent_id', 0)
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
