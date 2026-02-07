<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\XapiStatementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('health')->group(function () {
    Route::get('/ping', [HealthController::class, 'ping'])->name('api.health.ping');
    Route::get('/check', [HealthController::class, 'check'])->name('api.health.check');
    Route::get('/ready', [HealthController::class, 'ready'])->name('api.health.ready');
});

/*
|--------------------------------------------------------------------------
| xAPI LRS Endpoints
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('xapi')->name('api.xapi.')->group(function () {
    Route::get('/statements', [XapiStatementController::class, 'index'])->name('statements.index');
    Route::post('/statements', [XapiStatementController::class, 'store'])->name('statements.store');
});
