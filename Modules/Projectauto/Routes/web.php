<?php

use Illuminate\Support\Facades\Route;
use Modules\Projectauto\Http\Controllers\InstallController;
use Modules\Projectauto\Http\Controllers\ProjectautoSettingsController;
use Modules\Projectauto\Http\Controllers\ProjectautoTaskController;
use Modules\Projectauto\Http\Controllers\WorkflowApiController;
use Modules\Projectauto\Http\Controllers\WorkflowController;
use Modules\Projectauto\Http\Controllers\WorkflowRegistryApiController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'two_factor.verified'])
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

        Route::middleware('can:projectauto.settings.manage')->prefix('workflows')->name('workflows.')->group(function () {
            Route::get('/', [WorkflowController::class, 'index'])->name('index');
            Route::post('/', [WorkflowController::class, 'store'])->name('store');
            Route::get('/{id}/build', [WorkflowController::class, 'build'])->whereNumber('id')->name('build');
        });

        Route::middleware('can:projectauto.settings.manage')->prefix('api')->name('api.')->group(function () {
            Route::get('/workflow-definitions', [WorkflowRegistryApiController::class, 'index'])->name('workflow_definitions');
            Route::post('/workflows/from-wizard', [WorkflowApiController::class, 'storeFromWizard'])->name('workflows.from_wizard');
            Route::put('/workflows/{id}/draft', [WorkflowApiController::class, 'updateDraft'])->whereNumber('id')->name('workflows.update_draft');
            Route::post('/workflows/{id}/validate-draft', [WorkflowApiController::class, 'validateDraft'])->whereNumber('id')->name('workflows.validate_draft');
            Route::post('/workflows/{id}/publish', [WorkflowApiController::class, 'publish'])->whereNumber('id')->name('workflows.publish');
        });
    });
