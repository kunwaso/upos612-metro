<?php

namespace Modules\StorageManager\Tests\Unit;

use App\ProductRack;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\StorageManager\Entities\StorageSlot;
use Tests\TestCase;

class StorageSlotModelContractTest extends TestCase
{
    public function test_storage_slot_uses_expected_table_and_guarded_contract(): void
    {
        $slot = new StorageSlot();

        $this->assertSame('storage_slots', $slot->getTable());
        $this->assertSame(['id'], $slot->getGuarded());
    }

    public function test_storage_slot_exposes_expected_relations(): void
    {
        $slot = new StorageSlot();

        $this->assertInstanceOf(BelongsTo::class, $slot->location());
        $this->assertInstanceOf(BelongsTo::class, $slot->category());
        $this->assertInstanceOf(BelongsTo::class, $slot->area());
        $this->assertInstanceOf(HasMany::class, $slot->productRacks());
        $this->assertInstanceOf(HasMany::class, $slot->stocks());
    }

    public function test_storage_slot_declares_capacity_and_full_state_accessors(): void
    {
        $this->assertTrue(method_exists(StorageSlot::class, 'getOccupancyAttribute'));
        $this->assertTrue(method_exists(StorageSlot::class, 'getIsFullAttribute'));
    }
}
