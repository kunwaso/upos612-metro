<?php

use Illuminate\Support\Facades\Route;
use Modules\Projectauto\Http\Controllers\ProjectautoApiTaskController;

Route::middleware('auth:api')->prefix('projectauto')->name('projectauto.api.')->group(function () {
    Route::post('/tasks', [ProjectautoApiTaskController::class, 'store'])->name('tasks.store');
});
