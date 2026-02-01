<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('tags', TagController::class)->except(['show']);
});
