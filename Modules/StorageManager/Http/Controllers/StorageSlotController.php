<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\StorageManager\Entities\StorageSlot;
use Modules\StorageManager\Http\Requests\StoreStorageSlotRequest;
use Modules\StorageManager\Http\Requests\UpdateStorageSlotRequest;
use Modules\StorageManager\Utils\StorageManagerUtil;

class StorageSlotController extends Controller
{
    protected StorageManagerUtil $util;

    public function __construct(StorageManagerUtil $util)
    {
        $this->util = $util;
    }

    /**
     * Show the slot list (filtered by location if provided).
     */
    public function index()
    {
        if (! auth()->user()->can('storage_manager.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $locations   = $this->util->getLocationsDropdown($business_id);
        $categories  = $this->util->getCategoriesDropdown($business_id);

        $location_id = (int) request('location_id', 0);

        $query = StorageSlot::forBusiness($business_id)
            ->with('location', 'category')
            ->withCount('productRacks as occupancy');

        if ($location_id) {
            $query->forLocation($location_id);
        }

        $slots = $query->orderBy('location_id')->orderBy('category_id')->orderBy('row')->orderBy('position')->paginate(50);

        return view('storagemanager::slots.index', compact('slots', 'locations', 'categories', 'location_id'));
    }

    /**
     * Show the create form.
     */
    public function create()
    {
        if (! auth()->user()->can('storage_manager.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $locations   = $this->util->getLocationsDropdown($business_id);
        $categories  = $this->util->getCategoriesDropdown($business_id);

        return view('storagemanager::slots.create', compact('locations', 'categories'));
    }

    /**
     * Persist a new storage slot.
     */
    public function store(StoreStorageSlotRequest $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $data        = $request->validated();
        $data['business_id'] = $business_id;

        // Auto-generate slot code if not provided
        if (empty($data['slot_code'])) {
            $category = \App\Category::find($data['category_id']);
            if ($category) {
                $data['slot_code'] = $this->util->generateSlotCode($category, $data['row'], $data['position']);
            }
        }

        StorageSlot::create($data);

        $output = [
            'success' => true,
            'msg'     => __('lang_v1.slot_updated'),
        ];

        return redirect()->route('storage-manager.slots.index')
            ->with('status', $output);
    }

    /**
     * Show edit form.
     */
    public function edit(int $id)
    {
        if (! auth()->user()->can('storage_manager.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $slot        = StorageSlot::forBusiness($business_id)->with('category')->findOrFail($id);
        $locations   = $this->util->getLocationsDropdown($business_id);
        $categories  = $this->util->getCategoriesDropdown($business_id);

        return view('storagemanager::slots.edit', compact('slot', 'locations', 'categories'));
    }

    /**
     * Update a storage slot.
     */
    public function update(UpdateStorageSlotRequest $request, int $id)
    {
        $business_id = $request->session()->get('user.business_id');
        $slot        = StorageSlot::forBusiness($business_id)->findOrFail($id);
        $data        = $request->validated();

        if (empty($data['slot_code'])) {
            $category = \App\Category::find($data['category_id']);
            if ($category) {
                $data['slot_code'] = $this->util->generateSlotCode($category, $data['row'], $data['position']);
            }
        }

        $slot->update($data);

        $output = [
            'success' => true,
            'msg'     => __('lang_v1.slot_updated'),
        ];

        return redirect()->route('storage-manager.slots.index')
            ->with('status', $output);
    }

    /**
     * Delete a storage slot.
     */
    public function destroy(int $id)
    {
        if (! auth()->user()->can('storage_manager.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $slot        = StorageSlot::forBusiness($business_id)->findOrFail($id);
        $slot->delete();

        $output = [
            'success' => true,
            'msg'     => __('lang_v1.slot_deleted'),
        ];

        if (request()->ajax()) {
            return $this->respondSuccess(__('lang_v1.slot_deleted'));
        }

        return redirect()->route('storage-manager.slots.index')
            ->with('status', $output);
    }
}
