<?php

namespace Modules\StorageManager\Tests\Unit;

use App\ProductRack;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\StorageManager\Utils\StorageManagerUtil;
use Tests\TestCase;

class StorageManagerWarehouseMapSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropAllTables();

        Schema::create('storage_slots', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedInteger('category_id')->default(1);
            $table->string('row', 50)->default('1');
            $table->string('position', 50)->default('1');
            $table->string('slot_code', 50)->nullable();
            $table->unsignedInteger('max_capacity')->default(0);
            $table->timestamps();
        });

        Schema::create('product_racks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedInteger('product_id');
            $table->string('rack')->nullable();
            $table->string('row')->nullable();
            $table->string('position')->nullable();
            $table->unsignedInteger('slot_id')->nullable();
            $table->timestamps();
        });

        DB::table('storage_slots')->insert([
            'id' => 1,
            'business_id' => 10,
            'location_id' => 5,
            'category_id' => 1,
            'row' => '1',
            'position' => '1',
            'slot_code' => 'STG-01',
            'max_capacity' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_sync_warehouse_map_slot_assigns_product_rack_slot_id(): void
    {
        $util = new StorageManagerUtil();

        $util->syncWarehouseMapSlotForProduct(10, 100, 5, 1);

        $rack = ProductRack::query()
            ->where('business_id', 10)
            ->where('product_id', 100)
            ->where('location_id', 5)
            ->first();

        $this->assertNotNull($rack);
        $this->assertSame(1, (int) $rack->slot_id);
    }

    public function test_sync_warehouse_map_slot_with_null_clears_slot_id(): void
    {
        DB::table('product_racks')->insert([
            'business_id' => 10,
            'location_id' => 5,
            'product_id' => 200,
            'rack' => null,
            'row' => null,
            'position' => null,
            'slot_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $util = new StorageManagerUtil();
        $util->syncWarehouseMapSlotForProduct(10, 200, 5, null);

        $slotId = ProductRack::query()
            ->where('business_id', 10)
            ->where('product_id', 200)
            ->where('location_id', 5)
            ->value('slot_id');

        $this->assertNull($slotId);
    }

    public function test_sync_warehouse_map_slot_with_zero_clears_slot_id(): void
    {
        DB::table('product_racks')->insert([
            'business_id' => 10,
            'location_id' => 5,
            'product_id' => 300,
            'rack' => null,
            'row' => null,
            'position' => null,
            'slot_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $util = new StorageManagerUtil();
        $util->syncWarehouseMapSlotForProduct(10, 300, 5, 0);

        $slotId = ProductRack::query()
            ->where('business_id', 10)
            ->where('product_id', 300)
            ->where('location_id', 5)
            ->value('slot_id');

        $this->assertNull($slotId);
    }
}
