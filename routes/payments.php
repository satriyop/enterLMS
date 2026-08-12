<?php

use App\Http\Controllers\PaymentController;
use App\Http\Middleware\EnsurePaymentsEnabled;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsurePaymentsEnabled::class])->group(function () {
    // Payment listing and details (HTTP surface only when payments product-enabled)
    Route::get('payments', [PaymentController::class, 'index'])
        ->name('payments.index');

    Route::get('payments/{payment}', [PaymentController::class, 'show'])
        ->name('payments.show');

    // Create payment for course
    Route::post('courses/{course}/payment', [PaymentController::class, 'createForCourse'])
        ->name('courses.payment.create');

    // Cancel pending payment
    Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])
        ->name('payments.cancel');
});
