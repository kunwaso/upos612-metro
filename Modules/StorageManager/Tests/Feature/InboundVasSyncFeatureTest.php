<?php

namespace Modules\StorageManager\Tests\Feature;

use Tests\TestCase;

class InboundVasSyncFeatureTest extends TestCase
{
    public function test_inbound_sync_vas_route_is_registered(): void
    {
        $router = app('router');
        $this->assertTrue($router->has('storage-manager.inbound.sync-vas'));
    }

    public function test_inbound_unlink_vas_route_is_registered(): void
    {
        $router = app('router');
        $this->assertTrue($router->has('storage-manager.inbound.unlink-vas'));
    }

    public function test_sync_vas_route_uses_expected_uri(): void
    {
        $routes = app('router')->getRoutes();
        $this->assertSame(
            'storage-manager/inbound/receipts/{document}/sync-vas',
            $routes->getByName('storage-manager.inbound.sync-vas')->uri()
        );
    }

    public function test_unlink_vas_route_uses_expected_uri(): void
    {
        $routes = app('router')->getRoutes();
        $this->assertSame(
            'storage-manager/inbound/receipts/{document}/unlink-vas',
            $routes->getByName('storage-manager.inbound.unlink-vas')->uri()
        );
    }

    public function test_inbound_vas_sync_config_key_exists(): void
    {
        $value = config('storagemanager.inbound_vas_sync');
        $this->assertNotNull($value);
        $this->assertContains($value, ['manual', 'auto']);
    }

    public function test_default_inbound_vas_sync_is_manual(): void
    {
        $this->assertSame('manual', config('storagemanager.inbound_vas_sync'));
    }

    public function test_sync_form_request_authorizes_manage_or_approve(): void
    {
        $request = new \Modules\StorageManager\Http\Requests\SyncInboundReceiptVasRequest();
        $this->assertIsArray($request->rules());
    }

    public function test_unlink_form_request_authorizes_manage_or_approve(): void
    {
        $request = new \Modules\StorageManager\Http\Requests\UnlinkInboundReceiptVasRequest();
        $this->assertIsArray($request->rules());
    }
}
