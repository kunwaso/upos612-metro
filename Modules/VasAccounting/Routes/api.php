<?php

use Illuminate\Support\Facades\Route;
use Modules\VasAccounting\Http\Controllers\Api\EnterpriseApiController;

Route::middleware('auth:api')->prefix('vas-accounting')->name('vasaccounting.api.')->group(function () {
    Route::get('/health', [EnterpriseApiController::class, 'health'])->name('health');
    Route::get('/domains', [EnterpriseApiController::class, 'domains'])->name('domains');
    Route::get('/snapshots', [EnterpriseApiController::class, 'snapshots'])->name('snapshots');
    Route::get('/integration-runs', [EnterpriseApiController::class, 'integrationRuns'])->name('integration_runs');
    Route::post('/webhooks/{provider}', [EnterpriseApiController::class, 'webhook'])->name('webhooks.store');
});
