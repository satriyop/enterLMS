<?php

use App\Http\Controllers\ScormPackageController;
use App\Http\Controllers\ScormPlayerController;
use App\Http\Controllers\ScormRuntimeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SCORM Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    // Package management (admin/instructor)
    Route::prefix('scorm/packages')->name('scorm.packages.')->group(function () {
        Route::get('/', [ScormPackageController::class, 'index'])->name('index');
        Route::post('/', [ScormPackageController::class, 'store'])->name('store');
        Route::delete('/{scormPackage}', [ScormPackageController::class, 'destroy'])->name('destroy');
    });

    // SCORM player (enrolled learners)
    Route::prefix('scorm/player')->name('scorm.player.')->group(function () {
        Route::get('/{lesson}/launch', [ScormPlayerController::class, 'launch'])->name('launch');
        Route::get('/{scormPackage}/content/{path}', [ScormPlayerController::class, 'serveContent'])
            ->where('path', '.*')
            ->name('content');
    });

    // SCORM runtime API (called by SCORM content iframe)
    Route::prefix('scorm/runtime')->name('scorm.runtime.')->group(function () {
        Route::post('/{lesson}/initialize', [ScormRuntimeController::class, 'initialize'])->name('initialize');
        Route::post('/{lesson}/get-value', [ScormRuntimeController::class, 'getValue'])->name('getValue');
        Route::post('/{lesson}/set-value', [ScormRuntimeController::class, 'setValue'])->name('setValue');
        Route::post('/{lesson}/commit', [ScormRuntimeController::class, 'commit'])->name('commit');
        Route::post('/{lesson}/finish', [ScormRuntimeController::class, 'finish'])->name('finish');
    });
});
