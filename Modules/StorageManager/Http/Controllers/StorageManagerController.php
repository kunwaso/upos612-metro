<?php

namespace Modules\StorageManager\Http\Controllers;

use App\BusinessLocation;
use App\Http\Controllers\Controller;
use Modules\StorageManager\Http\Requests\AssignSlotRequest;
use Modules\StorageManager\Utils\StorageManagerUtil;

class StorageManagerController extends Controller
{
    protected StorageManagerUtil $util;

    public function __construct(StorageManagerUtil $util)
    {
        $this->util = $util;
    }

    /**
     * Warehouse grid view — location selector + category zone cards with slot cells.
     */
    public function index()
    {
        if (! auth()->user()->can('storage_manager.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $locations   = $this->util->getLocationsDropdown($business_id);

        $location_id = (int) request('location_id', $locations->keys()->first() ?? 0);

        $zones = [];
        if ($location_id) {
            $zones = $this->util->getSlotsForLocation($business_id, $location_id);
        }

        $selectedLocation = $location_id
            ? BusinessLocation::where('business_id', $business_id)->find($location_id)
            : null;

        return view('storagemanager::index', compact('locations', 'location_id', 'zones', 'selectedLocation'));
    }

    /**
     * AJAX: assign a product to a slot.
     * Called from the product Stock tab "Change Slot" modal.
     */
    public function assignSlot(AssignSlotRequest $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $this->util->assignProductToSlot(
            $business_id,
            (int) $request->validated()['product_id'],
            (int) $request->validated()['slot_id']
        );

        return $this->respondSuccess(__('lang_v1.slot_assigned'));
    }

    /**
     * AJAX: return available slots for a product location.
     *
     * Returns two formats in one response:
     *  - 'slots'   : {id: label, ...}  — legacy format used by the Stock-tab Change Slot modal
     *  - 'results' : [{id, text, rack, row, position}, ...] — select2 AJAX format for the product form slot picker
     */
    public function availableSlots()
    {
        if (! auth()->user()->can('storage_manager.view')) {
            return $this->respondUnauthorized();
        }

        $business_id = request()->session()->get('user.business_id');
        $location_id = (int) request('location_id');

        $slotsData = $this->util->getAvailableSlotsWithDetails($business_id, $location_id);

        // Legacy key-value format (for the Change Slot modal in detail_stock.blade.php)
        $slots = [];
        foreach ($slotsData as $item) {
            $slots[$item['id']] = $item['text'];
        }

        return response()->json([
            'slots'   => $slots,
            'results' => $slotsData,
        ]);
    }
}
