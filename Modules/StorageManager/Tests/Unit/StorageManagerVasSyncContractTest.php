<?php

namespace Modules\StorageManager\Tests\Unit;

use Modules\StorageManager\Services\Adapters\VasWarehousePostingAdapter;
use Modules\VasAccounting\Services\Adapters\InventoryDocumentAdapter;
use Tests\TestCase;

class StorageManagerVasSyncContractTest extends TestCase
{
    public function test_inventory_document_adapter_is_registered_for_warehouse_execution_sync(): void
    {
        $this->assertSame(
            InventoryDocumentAdapter::class,
            (string) config('vasaccounting.source_document_adapters.inventory_document')
        );
    }

    public function test_inventory_document_sync_is_modelled_as_warehouse_posting(): void
    {
        $inventoryDomain = (array) config('vasaccounting.enterprise_domains.inventory', []);

        $this->assertSame('vas_warehouses', $inventoryDomain['record_table'] ?? null);
        $this->assertSame('vasaccounting.inventory.index', $inventoryDomain['route'] ?? null);
        $this->assertSame('vas_accounting.inventory.manage', $inventoryDomain['permission'] ?? null);
        $this->assertSame(VasWarehousePostingAdapter::class, (string) config('storagemanager.posting_adapters.vas'));
    }
}
