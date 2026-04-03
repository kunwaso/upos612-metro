<?php

namespace Modules\StorageManager\Tests\Feature;

use App\Category;
use Illuminate\Support\Collection;
use stdClass;
use Tests\TestCase;

class WarehouseMapViewContractTest extends TestCase
{
    public function test_warehouse_map_renders_slot_and_staging_cell_kinds(): void
    {
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

        $mapCards = [
            [
                'card_type' => 'slot_grid',
                'area' => ['id' => 1, 'name' => 'Reserve-A', 'type' => 'reserve', 'sort_order' => 1],
                'category' => $category,
                'slots' => collect([$slot]),
                'occupied' => 3,
                'capacity' => 10,
                'products' => [],
                'product_count' => 0,
            ],
            [
                'card_type' => 'staging_inbound',
                'area' => ['id' => 2, 'name' => 'Staging In-A', 'type' => 'staging_in', 'sort_order' => 2],
                'category' => null,
                'slots' => collect(),
                'occupied' => 0,
                'capacity' => 0,
                'products' => [
                    [
                        'line_id' => 90,
                        'product_label' => 'Product Alpha With Long Name',
                        'hover_name' => 'Product Alpha With Long Name',
                        'inbound_url' => '/storage-manager/inbound/receipts/purchase/10',
                        'disabled' => false,
                    ],
                ],
                'product_count' => 1,
            ],
        ];

        $selectedLocation = new stdClass();
        $selectedLocation->name = 'Main Warehouse';

        $html = view('storagemanager::index', [
            'locations' => collect([1 => 'Main Warehouse']),
            'location_id' => 1,
            'map_cards' => $mapCards,
            'selectedLocation' => $selectedLocation,
            'running_out_items' => new Collection(),
            'expiring_items' => new Collection(),
            'widget_meta' => [
                'running_out_url' => '/storage-manager/running-out-of-stock?location_id=1',
                'expiring_url' => '/reports/stock-expiry?location_id=1',
                'expiry_window_days' => 30,
            ],
        ])->render();

        $this->assertStringContainsString('data-cell-kind="slot"', $html);
        $this->assertStringContainsString('data-cell-kind="staging-product"', $html);
        $this->assertStringContainsString('data-inbound-url="/storage-manager/inbound/receipts/purchase/10"', $html);
        $this->assertStringContainsString('Open inbound items:', $html);
    }
}

