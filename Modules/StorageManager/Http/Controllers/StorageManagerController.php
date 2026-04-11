<?php

namespace Modules\StorageManager\Http\Controllers;

use App\BusinessLocation;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\StorageManager\Http\Requests\AssignSlotRequest;
use Modules\StorageManager\Utils\StorageManagerToolbarNavUtil;
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
        $permitted_locations = auth()->user()->permitted_locations();

        $zones = [];
        $running_out_items = collect();
        $expiring_items = collect();
        $expiry_window_days = $this->util->resolveExpiryAlertDays($business_id);
        $expiring_filter_date = Carbon::today()->addDays($expiry_window_days)->toDateString();
        if ($location_id) {
            $zones = $this->util->getSlotsForLocation($business_id, $location_id);

            $running_out_items = $this->util->getRunningOutStockItems(
                $business_id,
                $location_id,
                $permitted_locations,
                StorageManagerUtil::DEFAULT_WIDGET_LIMIT
            );
            $expiring_items = $this->util->getExpiringProductsItems(
                $business_id,
                $location_id,
                $expiry_window_days,
                $permitted_locations,
                StorageManagerUtil::DEFAULT_WIDGET_LIMIT
            );
        }

        $selectedLocation = $location_id
            ? BusinessLocation::where('business_id', $business_id)->find($location_id)
            : null;

        $runningOutHeaderUrl = route('storage-manager.running-out', ['location_id' => $location_id]);
        $expiringQuery = [
            'location_id'     => $location_id,
            'exp_date_filter' => $expiring_filter_date,
        ];
        $expiringHeaderUrl = url('/reports/stock-expiry') . '?' . http_build_query($expiringQuery);

        $running_out_items = $running_out_items->map(function (array $item) use ($location_id) {
            $item['link_url'] = route('storage-manager.running-out', [
                'location_id' => $location_id,
                'product_id'  => $item['product_id'],
            ]);

            return $item;
        });

        $expiring_items = $expiring_items->map(function (array $item) use ($expiringHeaderUrl) {
            $item['link_url'] = $expiringHeaderUrl;

            return $item;
        });

        $widget_meta = [
            'limit'               => StorageManagerUtil::DEFAULT_WIDGET_LIMIT,
            'expiry_window_days'  => $expiry_window_days,
            'expiring_filter_date'=> $expiring_filter_date,
            'running_out_url'     => $runningOutHeaderUrl,
            'expiring_url'        => $expiringHeaderUrl,
        ];

        $storageToolbarTitle = __('lang_v1.warehouse_map');
        $storageToolbarBreadcrumbs = StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
            ['label' => __('lang_v1.warehouse_map'), 'url' => null],
        ], $location_id > 0 ? $location_id : null);
        $storageToolbarLocationId = $location_id > 0 ? $location_id : null;

        return view('storagemanager::index', compact(
            'locations',
            'location_id',
            'zones',
            'selectedLocation',
            'running_out_items',
            'expiring_items',
            'widget_meta',
            'storageToolbarTitle',
            'storageToolbarBreadcrumbs',
            'storageToolbarLocationId'
        ));
    }

    /**
     * Detailed page for running-out-of-stock products.
     */
    public function runningOutOfStock()
    {
        if (! auth()->user()->can('storage_manager.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $locations   = $this->util->getLocationsDropdown($business_id);
        $location_id = (int) request('location_id', $locations->keys()->first() ?? 0);
        $product_id  = (int) request('product_id', 0);
        $permitted_locations = auth()->user()->permitted_locations();

        $items = collect();
        if ($location_id) {
            $items = $this->util->getRunningOutStockItems(
                $business_id,
                $location_id,
                $permitted_locations,
                null,
                $product_id > 0 ? $product_id : null
            );
        }

        $selectedLocation = $location_id
            ? BusinessLocation::where('business_id', $business_id)->find($location_id)
            : null;

        $storageToolbarTitle = __('lang_v1.running_out_of_stock');
        $storageToolbarBreadcrumbs = StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
            ['label' => __('lang_v1.running_out_of_stock'), 'url' => null],
        ], $location_id > 0 ? $location_id : null);
        $storageToolbarLocationId = $location_id > 0 ? $location_id : null;

        return view('storagemanager::running_out_of_stock', compact(
            'locations',
            'location_id',
            'items',
            'selectedLocation',
            'product_id',
            'storageToolbarTitle',
            'storageToolbarBreadcrumbs',
            'storageToolbarLocationId'
        ));
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
        $slot_id = (int) request('slot_id');
        if ($slot_id > 0) {
            $slotProducts = $this->util->getSlotAssignedProducts(
                $business_id,
                $slot_id,
                StorageManagerUtil::DEFAULT_WIDGET_LIMIT
            );

            return response()->json([
                'slot_products'           => $slotProducts['items'],
                'slot_products_total'     => $slotProducts['total'],
                'slot_products_truncated' => $slotProducts['truncated'],
            ]);
        }

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
