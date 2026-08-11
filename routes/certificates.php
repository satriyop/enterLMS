<?php

use App\Http\Controllers\CertificateController;
use Illuminate\Support\Facades\Route;

// Public verification (no auth required)
Route::get('certificates/verify', [CertificateController::class, 'verifySearch'])
    ->name('certificates.verify.form');
Route::get('certificates/verify/{code}', [CertificateController::class, 'verify'])
    ->name('certificates.verify');

// Protected certificate routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('certificates', [CertificateController::class, 'index'])
        ->name('certificates.index');

    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])
        ->name('certificates.download');

    Route::get('certificates/{certificate}/view', [CertificateController::class, 'stream'])
        ->name('certificates.stream');

    Route::post('courses/{course}/certificate', [CertificateController::class, 'request'])
        ->name('courses.certificate.request');
});
