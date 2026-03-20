<?php

use Modules\StorageManager\Http\Controllers\StorageManagerController;
use Modules\StorageManager\Http\Controllers\StorageSlotController;
use Modules\StorageManager\Http\Controllers\InstallController;

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

        // AJAX: available slots for a location
        Route::get('/available-slots', [StorageManagerController::class, 'availableSlots'])->name('available-slots');

        // AJAX: assign product to slot
        Route::post('/assign-slot', [StorageManagerController::class, 'assignSlot'])->name('assign-slot');

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
