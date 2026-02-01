<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearnerDashboardController;
use App\Http\Controllers\MyLearningController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Learner Dashboard
Route::get('learner/dashboard', LearnerDashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('learner.dashboard');

// My Learning
Route::get('my-learning', MyLearningController::class)
    ->middleware(['auth', 'verified'])
    ->name('my-learning');

// Notifications
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
});

require __DIR__.'/settings.php';
require __DIR__.'/courses.php';
require __DIR__.'/learning_paths.php';
