<?php

namespace Modules\StorageManager\Tests\Feature;

use App\Category;
use Illuminate\Support\Collection;
use Modules\StorageManager\Utils\StorageManagerToolbarNavUtil;
use stdClass;
use Tests\TestCase;

class WarehouseMapViewContractTest extends TestCase
{
    public function test_warehouse_map_renders_reserve_slot_buttons_and_staging_empty_state(): void
    {
        $_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';

        $category = new Category();
        $category->id = 7;
        $category->name = 'Reserve';

        $slot = new stdClass();
        $slot->id = 12;
        $slot->slot_code = 'A-01';
        $slot->row = 1;
        $slot->position = 1;
        $slot->is_full = false;
        $slot->max_capacity = 10;
        $slot->occupancy = 3;

        $zones = [
            [
                'area_id' => 1,
                'area_type' => 'reserve',
                'category' => $category,
                'label' => 'Reserve-A',
                'slots' => collect([$slot]),
                'occupied' => 3,
                'capacity' => 10,
                'placeholder' => false,
            ],
            [
                'area_id' => 2,
                'area_type' => 'staging_in',
                'category' => null,
                'label' => 'Staging In-A',
                'slots' => collect(),
                'occupied' => 0,
                'capacity' => 0,
                'placeholder' => true,
            ],
        ];

        $selectedLocation = new stdClass();
        $selectedLocation->name = 'Main Warehouse';

        $html = view('storagemanager::index', [
            'locations' => collect([1 => 'Main Warehouse']),
            'location_id' => 1,
            'zones' => $zones,
            'selectedLocation' => $selectedLocation,
            'running_out_items' => new Collection(),
            'expiring_items' => new Collection(),
            'widget_meta' => [
                'running_out_url' => '/storage-manager/running-out-of-stock?location_id=1',
                'expiring_url' => '/reports/stock-expiry?location_id=1',
                'expiry_window_days' => 30,
            ],
            'storageToolbarTitle' => __('lang_v1.warehouse_map'),
            'storageToolbarBreadcrumbs' => StorageManagerToolbarNavUtil::breadcrumbsAfterRoot([
                ['label' => __('lang_v1.warehouse_map'), 'url' => null],
            ], 1),
            'storageToolbarLocationId' => 1,
        ])->render();

        $this->assertStringContainsString('slot-cell', $html);
        $this->assertStringContainsString('data-slot-id="12"', $html);
        $this->assertStringContainsString('A-01', $html);
        $this->assertStringContainsString('Reserve-A', $html);
        $this->assertStringContainsString('Staging In-A', $html);
        $this->assertStringContainsString(__('lang_v1.warehouse_map_no_slot_cells'), $html);
    }
}
