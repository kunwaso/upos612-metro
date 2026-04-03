<?php

namespace Modules\StorageManager\Tests\Feature;

use Tests\TestCase;

class StorageManagerRoutesRegistrationTest extends TestCase
{
    public function test_storage_manager_named_routes_are_registered(): void
    {
        $router = app('router');

        $this->assertTrue($router->has('storage-manager.index'));
        $this->assertTrue($router->has('storage-manager.running-out'));
        $this->assertTrue($router->has('storage-manager.settings.index'));
        $this->assertTrue($router->has('storage-manager.settings.update'));
        $this->assertTrue($router->has('storage-manager.areas.index'));
        $this->assertTrue($router->has('storage-manager.areas.create'));
        $this->assertTrue($router->has('storage-manager.areas.store'));
        $this->assertTrue($router->has('storage-manager.areas.edit'));
        $this->assertTrue($router->has('storage-manager.areas.update'));
        $this->assertTrue($router->has('storage-manager.control-tower.index'));
        $this->assertTrue($router->has('storage-manager.documents.show'));
        $this->assertTrue($router->has('storage-manager.planning.index'));
        $this->assertTrue($router->has('storage-manager.planning.show'));
        $this->assertTrue($router->has('storage-manager.planning.store'));
        $this->assertTrue($router->has('storage-manager.planning.store-grouped'));
        $this->assertTrue($router->has('storage-manager.inbound.index'));
        $this->assertTrue($router->has('storage-manager.inbound.show'));
        $this->assertTrue($router->has('storage-manager.inbound.confirm'));
        $this->assertTrue($router->has('storage-manager.inbound.reopen'));
        $this->assertTrue($router->has('storage-manager.inbound.sync-vas'));
        $this->assertTrue($router->has('storage-manager.inbound.unlink-vas'));
        $this->assertTrue($router->has('storage-manager.putaway.index'));
        $this->assertTrue($router->has('storage-manager.putaway.show'));
        $this->assertTrue($router->has('storage-manager.putaway.complete'));
        $this->assertTrue($router->has('storage-manager.putaway.reopen'));
        $this->assertTrue($router->has('storage-manager.transfers.index'));
        $this->assertTrue($router->has('storage-manager.transfers.dispatch.show'));
        $this->assertTrue($router->has('storage-manager.transfers.dispatch.confirm'));
        $this->assertTrue($router->has('storage-manager.transfers.receipts.show'));
        $this->assertTrue($router->has('storage-manager.transfers.receipts.confirm'));
        $this->assertTrue($router->has('storage-manager.replenishment.index'));
        $this->assertTrue($router->has('storage-manager.replenishment.show'));
        $this->assertTrue($router->has('storage-manager.replenishment.complete'));
        $this->assertTrue($router->has('storage-manager.damage.index'));
        $this->assertTrue($router->has('storage-manager.damage.store'));
        $this->assertTrue($router->has('storage-manager.damage.show'));
        $this->assertTrue($router->has('storage-manager.damage.resolve'));
        $this->assertTrue($router->has('storage-manager.counts.index'));
        $this->assertTrue($router->has('storage-manager.counts.store'));
        $this->assertTrue($router->has('storage-manager.counts.show'));
        $this->assertTrue($router->has('storage-manager.counts.submit'));
        $this->assertTrue($router->has('storage-manager.counts.approve-shortages'));
        $this->assertTrue($router->has('storage-manager.outbound.index'));
        $this->assertTrue($router->has('storage-manager.outbound.pick.show'));
        $this->assertTrue($router->has('storage-manager.outbound.pick.confirm'));
        $this->assertTrue($router->has('storage-manager.outbound.pack.show'));
        $this->assertTrue($router->has('storage-manager.outbound.pack.confirm'));
        $this->assertTrue($router->has('storage-manager.outbound.ship.show'));
        $this->assertTrue($router->has('storage-manager.outbound.ship.confirm'));
        $this->assertTrue($router->has('storage-manager.available-slots'));
        $this->assertTrue($router->has('storage-manager.assign-slot'));
        $this->assertTrue($router->has('storage-manager.api.reconcile-location'));
        $this->assertTrue($router->has('storage-manager.api.vas-retry'));
        $this->assertTrue($router->has('storage-manager.slots.index'));
        $this->assertTrue($router->has('storage-manager.slots.create'));
        $this->assertTrue($router->has('storage-manager.slots.store'));
        $this->assertTrue($router->has('storage-manager.slots.edit'));
        $this->assertTrue($router->has('storage-manager.slots.update'));
        $this->assertTrue($router->has('storage-manager.slots.destroy'));
        $this->assertTrue($router->has('storage-manager.install.index'));
        $this->assertTrue($router->has('storage-manager.install.update'));
        $this->assertTrue($router->has('storage-manager.install.uninstall'));
    }

    public function test_storage_manager_routes_use_expected_uris(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertSame('storage-manager', $routes->getByName('storage-manager.index')->uri());
        $this->assertSame('storage-manager/running-out-of-stock', $routes->getByName('storage-manager.running-out')->uri());
        $this->assertSame('storage-manager/settings', $routes->getByName('storage-manager.settings.index')->uri());
        $this->assertSame('storage-manager/settings/{location}', $routes->getByName('storage-manager.settings.update')->uri());
        $this->assertSame('storage-manager/areas', $routes->getByName('storage-manager.areas.index')->uri());
        $this->assertSame('storage-manager/areas/create', $routes->getByName('storage-manager.areas.create')->uri());
        $this->assertSame('storage-manager/areas/{id}/edit', $routes->getByName('storage-manager.areas.edit')->uri());
        $this->assertSame('storage-manager/control-tower', $routes->getByName('storage-manager.control-tower.index')->uri());
        $this->assertSame('storage-manager/documents/{document}', $routes->getByName('storage-manager.documents.show')->uri());
        $this->assertSame('storage-manager/planning/purchasing', $routes->getByName('storage-manager.planning.index')->uri());
        $this->assertSame('storage-manager/planning/advisories/{document}', $routes->getByName('storage-manager.planning.show')->uri());
        $this->assertSame('storage-manager/planning/purchasing/{rule}/purchase-requisition', $routes->getByName('storage-manager.planning.store')->uri());
        $this->assertSame('storage-manager/planning/purchasing/location/{location}/purchase-requisition', $routes->getByName('storage-manager.planning.store-grouped')->uri());
        $this->assertSame('storage-manager/inbound/expected-receipts', $routes->getByName('storage-manager.inbound.index')->uri());
        $this->assertSame('storage-manager/inbound/receipts/{sourceType}/{sourceId}', $routes->getByName('storage-manager.inbound.show')->uri());
        $this->assertSame('storage-manager/inbound/receipts/{document}/confirm', $routes->getByName('storage-manager.inbound.confirm')->uri());
        $this->assertSame('storage-manager/inbound/receipts/{document}/reopen', $routes->getByName('storage-manager.inbound.reopen')->uri());
        $this->assertSame('storage-manager/inbound/receipts/{document}/sync-vas', $routes->getByName('storage-manager.inbound.sync-vas')->uri());
        $this->assertSame('storage-manager/inbound/receipts/{document}/unlink-vas', $routes->getByName('storage-manager.inbound.unlink-vas')->uri());
        $this->assertSame('storage-manager/putaway', $routes->getByName('storage-manager.putaway.index')->uri());
        $this->assertSame('storage-manager/putaway/{document}', $routes->getByName('storage-manager.putaway.show')->uri());
        $this->assertSame('storage-manager/putaway/{document}/complete', $routes->getByName('storage-manager.putaway.complete')->uri());
        $this->assertSame('storage-manager/putaway/{document}/reopen', $routes->getByName('storage-manager.putaway.reopen')->uri());
        $this->assertSame('storage-manager/transfers', $routes->getByName('storage-manager.transfers.index')->uri());
        $this->assertSame('storage-manager/transfers/dispatch/{transfer}', $routes->getByName('storage-manager.transfers.dispatch.show')->uri());
        $this->assertSame('storage-manager/transfers/dispatch/{document}/confirm', $routes->getByName('storage-manager.transfers.dispatch.confirm')->uri());
        $this->assertSame('storage-manager/transfers/receipts/{transfer}', $routes->getByName('storage-manager.transfers.receipts.show')->uri());
        $this->assertSame('storage-manager/transfers/receipts/{document}/confirm', $routes->getByName('storage-manager.transfers.receipts.confirm')->uri());
        $this->assertSame('storage-manager/replenishment', $routes->getByName('storage-manager.replenishment.index')->uri());
        $this->assertSame('storage-manager/replenishment/{rule}', $routes->getByName('storage-manager.replenishment.show')->uri());
        $this->assertSame('storage-manager/replenishment/{rule}/complete', $routes->getByName('storage-manager.replenishment.complete')->uri());
        $this->assertSame('storage-manager/damage', $routes->getByName('storage-manager.damage.index')->uri());
        $this->assertSame('storage-manager/damage/report', $routes->getByName('storage-manager.damage.store')->uri());
        $this->assertSame('storage-manager/damage/{document}', $routes->getByName('storage-manager.damage.show')->uri());
        $this->assertSame('storage-manager/damage/{document}/resolve', $routes->getByName('storage-manager.damage.resolve')->uri());
        $this->assertSame('storage-manager/counts', $routes->getByName('storage-manager.counts.index')->uri());
        $this->assertSame('storage-manager/counts', $routes->getByName('storage-manager.counts.store')->uri());
        $this->assertSame('storage-manager/counts/{session}', $routes->getByName('storage-manager.counts.show')->uri());
        $this->assertSame('storage-manager/counts/{session}/submit', $routes->getByName('storage-manager.counts.submit')->uri());
        $this->assertSame('storage-manager/counts/{session}/approve-shortages', $routes->getByName('storage-manager.counts.approve-shortages')->uri());
        $this->assertSame('storage-manager/outbound', $routes->getByName('storage-manager.outbound.index')->uri());
        $this->assertSame('storage-manager/outbound/pick/{salesOrder}', $routes->getByName('storage-manager.outbound.pick.show')->uri());
        $this->assertSame('storage-manager/outbound/pick/{document}/confirm', $routes->getByName('storage-manager.outbound.pick.confirm')->uri());
        $this->assertSame('storage-manager/outbound/pack/{salesOrder}', $routes->getByName('storage-manager.outbound.pack.show')->uri());
        $this->assertSame('storage-manager/outbound/pack/{document}/confirm', $routes->getByName('storage-manager.outbound.pack.confirm')->uri());
        $this->assertSame('storage-manager/outbound/ship/{salesOrder}', $routes->getByName('storage-manager.outbound.ship.show')->uri());
        $this->assertSame('storage-manager/outbound/ship/{document}/confirm', $routes->getByName('storage-manager.outbound.ship.confirm')->uri());
        $this->assertSame('storage-manager/available-slots', $routes->getByName('storage-manager.available-slots')->uri());
        $this->assertSame('storage-manager/assign-slot', $routes->getByName('storage-manager.assign-slot')->uri());
        $this->assertSame('storage-manager/api/reconcile/location', $routes->getByName('storage-manager.api.reconcile-location')->uri());
        $this->assertSame('storage-manager/api/sync/vas/retry', $routes->getByName('storage-manager.api.vas-retry')->uri());
        $this->assertSame('storage-manager/slots', $routes->getByName('storage-manager.slots.index')->uri());
        $this->assertSame('storage-manager/slots/create', $routes->getByName('storage-manager.slots.create')->uri());
        $this->assertSame('storage-manager/slots/{id}/edit', $routes->getByName('storage-manager.slots.edit')->uri());
        $this->assertSame('storage-manager/install', $routes->getByName('storage-manager.install.index')->uri());
        $this->assertSame('storage-manager/install/update', $routes->getByName('storage-manager.install.update')->uri());
        $this->assertSame('storage-manager/install/uninstall', $routes->getByName('storage-manager.install.uninstall')->uri());
    }
}
