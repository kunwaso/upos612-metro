<?php

use Illuminate\Support\Facades\Route;

Route::prefix(strtolower(basename(__DIR__.'/..')))->group(function () {
    // Register module routes here.
});
