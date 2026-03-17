<?php

use Illuminate\Support\Facades\Route;
use Modules\Projectauto\Http\Controllers\InstallController;
use Modules\Projectauto\Http\Controllers\ProjectautoSettingsController;
use Modules\Projectauto\Http\Controllers\ProjectautoTaskController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu'])
    ->prefix('projectauto')
    ->name('projectauto.')
    ->group(function () {
        Route::middleware('superadmin')->prefix('install')->name('install.')->group(function () {
            Route::get('/', [InstallController::class, 'index'])->name('index');
            Route::get('/update', [InstallController::class, 'update'])->name('update');
            Route::get('/uninstall', [InstallController::class, 'uninstall'])->name('uninstall');
        });

        Route::middleware('can:projectauto.tasks.view')->prefix('tasks')->name('tasks.')->group(function () {
            Route::get('/', [ProjectautoTaskController::class, 'index'])->name('index');
            Route::get('/{id}', [ProjectautoTaskController::class, 'show'])->whereNumber('id')->name('show');
        });

        Route::middleware('can:projectauto.tasks.approve')->prefix('tasks')->name('tasks.')->group(function () {
            Route::post('/{id}/accept', [ProjectautoTaskController::class, 'accept'])->whereNumber('id')->name('accept');
            Route::post('/{id}/reject', [ProjectautoTaskController::class, 'reject'])->whereNumber('id')->name('reject');
            Route::post('/{id}/modify-accept', [ProjectautoTaskController::class, 'modifyAccept'])->whereNumber('id')->name('modify_accept');
        });

        Route::middleware('can:projectauto.settings.manage')->prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [ProjectautoSettingsController::class, 'index'])->name('index');
            Route::get('/create', [ProjectautoSettingsController::class, 'create'])->name('create');
            Route::post('/', [ProjectautoSettingsController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ProjectautoSettingsController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [ProjectautoSettingsController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [ProjectautoSettingsController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });
    });
