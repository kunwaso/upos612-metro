<?php

namespace Modules\StorageManager\Tests\Unit;

use App\Category;
use Modules\StorageManager\Utils\StorageManagerUtil;
use Tests\TestCase;

class StorageManagerUtilContractTest extends TestCase
{
    public function test_default_execution_thresholds_are_explicit(): void
    {
        $this->assertSame(10, StorageManagerUtil::DEFAULT_WIDGET_LIMIT);
        $this->assertSame(30, StorageManagerUtil::DEFAULT_EXPIRY_ALERT_DAYS);
    }

    public function test_generate_slot_code_prefers_category_short_code(): void
    {
        $category = new Category();
        $category->short_code = 'A';
        $category->name = 'Alpha Zone';

        $util = new StorageManagerUtil();

        $this->assertSame('A12', $util->generateSlotCode($category, '1', '2'));
    }

    public function test_generate_slot_code_falls_back_to_category_name_initial(): void
    {
        $category = new Category();
        $category->name = 'Reserve';

        $util = new StorageManagerUtil();

        $this->assertSame('R05', $util->generateSlotCode($category, '0', '5'));
    }

    public function test_area_dropdown_helper_exists_for_execution_layer_forms(): void
    {
        $this->assertTrue(method_exists(StorageManagerUtil::class, 'getAreasDropdown'));
    }
}
