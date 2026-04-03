<?php

namespace Modules\StorageManager\Tests\Unit;

use Modules\StorageManager\Http\Controllers\DataController;
use Tests\TestCase;

class StorageManagerContractTest extends TestCase
{
    public function test_module_config_exposes_expected_identity(): void
    {
        $this->assertSame('StorageManager', (string) config('storagemanager.name'));
        $this->assertSame('1.6.0', (string) config('storagemanager.module_version'));
        $this->assertContains('receipt', (array) config('storagemanager.document_types'));
        $this->assertContains('transfer_dispatch', (array) config('storagemanager.document_types'));
        $this->assertContains('replenishment', (array) config('storagemanager.document_types'));
        $this->assertContains('damage', (array) config('storagemanager.document_types'));
        $this->assertContains('cycle_count', (array) config('storagemanager.document_types'));
        $this->assertContains('posted', (array) config('storagemanager.sync_statuses'));
        $this->assertSame(
            \Modules\StorageManager\Services\Adapters\PurchaseOrderSourceAdapter::class,
            config('storagemanager.source_document_adapters.purchase_order')
        );
        $this->assertSame(
            \Modules\StorageManager\Services\Adapters\StockTransferSourceAdapter::class,
            config('storagemanager.source_document_adapters.stock_transfer')
        );
    }

    public function test_permission_registry_exposes_storage_manager_access_keys(): void
    {
        $permissions = (new DataController())->user_permissions();

        $values = array_map(static fn (array $permission) => $permission['value'] ?? null, $permissions);
        $labels = array_column($permissions, 'label', 'value');

        $this->assertContains('storage_manager.view', $values);
        $this->assertContains('storage_manager.manage', $values);
        $this->assertContains('storage_manager.operate', $values);
        $this->assertContains('storage_manager.approve', $values);
        $this->assertContains('storage_manager.count', $values);
        $this->assertSame(__('lang_v1.permission_storage_manager_view'), $labels['storage_manager.view']);
        $this->assertSame(__('lang_v1.permission_storage_manager_manage'), $labels['storage_manager.manage']);
        $this->assertSame(__('lang_v1.permission_storage_manager_operate'), $labels['storage_manager.operate']);
        $this->assertSame(__('lang_v1.permission_storage_manager_approve'), $labels['storage_manager.approve']);
        $this->assertSame(__('lang_v1.permission_storage_manager_count'), $labels['storage_manager.count']);
    }
}
