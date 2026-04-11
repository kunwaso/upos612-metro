<?php

use Modules\StorageManager\Http\Controllers\StorageManagerController;
use Modules\StorageManager\Http\Controllers\StorageSlotController;
use Modules\StorageManager\Http\Controllers\InstallController;
use Modules\StorageManager\Http\Controllers\StorageAreaController;
use Modules\StorageManager\Http\Controllers\StorageDocumentController;
use Modules\StorageManager\Http\Controllers\StorageLocationSettingsController;
use Modules\StorageManager\Http\Controllers\ControlTowerController;
use Modules\StorageManager\Http\Controllers\CycleCountController;
use Modules\StorageManager\Http\Controllers\DamageQuarantineController;
use Modules\StorageManager\Http\Controllers\InboundController;
use Modules\StorageManager\Http\Controllers\OutboundExecutionController;
use Modules\StorageManager\Http\Controllers\PurchasingAdvisoryController;
use Modules\StorageManager\Http\Controllers\PutawayController;
use Modules\StorageManager\Http\Controllers\ReplenishmentController;
use Modules\StorageManager\Http\Controllers\TransferExecutionController;

Route::middleware(['web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])
    ->prefix('storage-manager')
    ->name('storage-manager.')
    ->group(function () {
        Route::middleware('superadmin')->prefix('install')->name('install.')->group(function () {
            Route::get('/', [InstallController::class, 'index'])->name('index');
            Route::get('/update', [InstallController::class, 'update'])->name('update');
            Route::get('/uninstall', [InstallController::class, 'uninstall'])->name('uninstall');
        });

        // Warehouse grid view
        Route::get('/', [StorageManagerController::class, 'index'])->name('index');

        // Detailed low-stock list (used by Running Out of Stock widget links)
        Route::get('/running-out-of-stock', [StorageManagerController::class, 'runningOutOfStock'])->name('running-out');

        Route::get('/settings', [StorageLocationSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/{location}', [StorageLocationSettingsController::class, 'update'])->name('settings.update');

        Route::prefix('areas')->name('areas.')->group(function () {
            Route::get('/', [StorageAreaController::class, 'index'])->name('index');
            Route::get('/create', [StorageAreaController::class, 'create'])->name('create');
            Route::post('/', [StorageAreaController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [StorageAreaController::class, 'edit'])->name('edit');
            Route::put('/{id}', [StorageAreaController::class, 'update'])->name('update');
        });

        Route::get('/control-tower', [ControlTowerController::class, 'index'])->name('control-tower.index');
        Route::get('/documents/{document}', [StorageDocumentController::class, 'show'])->name('documents.show');

        Route::prefix('planning')->name('planning.')->group(function () {
            Route::get('/purchasing', [PurchasingAdvisoryController::class, 'index'])->name('index');
            Route::get('/advisories/{document}', [PurchasingAdvisoryController::class, 'show'])->name('show');
            Route::post('/purchasing/{rule}/purchase-requisition', [PurchasingAdvisoryController::class, 'store'])->name('store');
            Route::post('/purchasing/location/{location}/purchase-requisition', [PurchasingAdvisoryController::class, 'storeGrouped'])->name('store-grouped');
        });

        Route::prefix('inbound')->name('inbound.')->group(function () {
            Route::get('/expected-receipts', [InboundController::class, 'index'])->name('index');
            Route::post('/purchase-orders/{purchaseOrder}/receive-goods', [InboundController::class, 'startPurchaseOrderReceiving'])->name('purchase-orders.start-receiving');
            // Register GRN before the generic receipt workbench route so `/receipts/{id}/grn` is not captured as `{sourceType}/{sourceId}`.
            Route::get('/receipts/{document}/grn', [InboundController::class, 'showGrn'])->name('grn.show');
            Route::get('/receipts/{sourceType}/{sourceId}', [InboundController::class, 'show'])->name('show');
            Route::post('/receipts/{document}/confirm', [InboundController::class, 'confirm'])->name('confirm');
            Route::post('/receipts/{document}/reopen', [InboundController::class, 'reopen'])->name('reopen');
            Route::post('/receipts/{document}/sync-vas', [InboundController::class, 'syncVas'])->name('sync-vas');
            Route::post('/receipts/{document}/unlink-vas', [InboundController::class, 'unlinkVas'])->name('unlink-vas');
        });

        Route::prefix('putaway')->name('putaway.')->group(function () {
            Route::get('/', [PutawayController::class, 'index'])->name('index');
            Route::get('/{document}', [PutawayController::class, 'show'])->name('show');
            Route::post('/{document}/complete', [PutawayController::class, 'complete'])->name('complete');
            Route::post('/{document}/reopen', [PutawayController::class, 'reopen'])->name('reopen');
        });

        Route::prefix('transfers')->name('transfers.')->group(function () {
            Route::get('/', [TransferExecutionController::class, 'index'])->name('index');
            Route::get('/dispatch/{transfer}', [TransferExecutionController::class, 'showDispatch'])->name('dispatch.show');
            Route::post('/dispatch/{document}/confirm', [TransferExecutionController::class, 'confirmDispatch'])->name('dispatch.confirm');
            Route::get('/receipts/{transfer}', [TransferExecutionController::class, 'showReceipt'])->name('receipts.show');
            Route::post('/receipts/{document}/confirm', [TransferExecutionController::class, 'confirmReceipt'])->name('receipts.confirm');
        });

        Route::prefix('replenishment')->name('replenishment.')->group(function () {
            Route::get('/', [ReplenishmentController::class, 'index'])->name('index');
            Route::get('/{rule}', [ReplenishmentController::class, 'show'])->name('show');
            Route::post('/{rule}/complete', [ReplenishmentController::class, 'complete'])->name('complete');
        });

        Route::prefix('damage')->name('damage.')->group(function () {
            Route::get('/', [DamageQuarantineController::class, 'index'])->name('index');
            Route::post('/report', [DamageQuarantineController::class, 'store'])->name('store');
            Route::get('/{document}', [DamageQuarantineController::class, 'show'])->name('show');
            Route::post('/{document}/resolve', [DamageQuarantineController::class, 'resolve'])->name('resolve');
        });

        Route::prefix('counts')->name('counts.')->group(function () {
            Route::get('/', [CycleCountController::class, 'index'])->name('index');
            Route::post('/', [CycleCountController::class, 'store'])->name('store');
            Route::get('/{session}', [CycleCountController::class, 'show'])->name('show');
            Route::post('/{session}/submit', [CycleCountController::class, 'submit'])->name('submit');
            Route::post('/{session}/approve-shortages', [CycleCountController::class, 'approveShortages'])->name('approve-shortages');
        });

        Route::prefix('outbound')->name('outbound.')->group(function () {
            Route::get('/', [OutboundExecutionController::class, 'index'])->name('index');
            Route::get('/pick/{salesOrder}', [OutboundExecutionController::class, 'showPick'])->name('pick.show');
            Route::post('/pick/{document}/confirm', [OutboundExecutionController::class, 'confirmPick'])->name('pick.confirm');
            Route::get('/pack/{salesOrder}', [OutboundExecutionController::class, 'showPack'])->name('pack.show');
            Route::post('/pack/{document}/confirm', [OutboundExecutionController::class, 'confirmPack'])->name('pack.confirm');
            Route::get('/ship/{salesOrder}', [OutboundExecutionController::class, 'showShip'])->name('ship.show');
            Route::post('/ship/{document}/confirm', [OutboundExecutionController::class, 'confirmShip'])->name('ship.confirm');
        });

        // AJAX: available slots for a location
        Route::get('/available-slots', [StorageManagerController::class, 'availableSlots'])->name('available-slots');

        // AJAX: assign product to slot
        Route::post('/assign-slot', [StorageManagerController::class, 'assignSlot'])->name('assign-slot');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/reconcile/location', [ControlTowerController::class, 'reconcileLocation'])->name('reconcile-location');
            Route::post('/sync/vas/retry', [ControlTowerController::class, 'retryVasSync'])->name('vas-retry');
        });

        // Slot CRUD
        Route::prefix('slots')->name('slots.')->group(function () {
            Route::get('/', [StorageSlotController::class, 'index'])->name('index');
            Route::get('/create', [StorageSlotController::class, 'create'])->name('create');
            Route::post('/', [StorageSlotController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [StorageSlotController::class, 'edit'])->name('edit');
            Route::put('/{id}', [StorageSlotController::class, 'update'])->name('update');
            Route::delete('/{id}', [StorageSlotController::class, 'destroy'])->name('destroy');
        });
    });
