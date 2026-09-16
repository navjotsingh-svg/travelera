<?php

use App\Http\Controllers\Admin\AbandonedPaymentController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');

        Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
        Route::patch('/bookings/{booking}', [AdminBookingController::class, 'update'])->name('bookings.update');

        Route::get('/searches', [AdminSearchController::class, 'index'])->name('searches.index');

        Route::get('/abandoned-payments', [AbandonedPaymentController::class, 'index'])->name('abandoned.index');
        Route::patch('/abandoned-payments/{checkoutAttempt}', [AbandonedPaymentController::class, 'update'])->name('abandoned.update');

        Route::get('/queries', [\App\Http\Controllers\Admin\QueryController::class, 'index'])->name('queries.index');
        Route::get('/queries/{query}', [\App\Http\Controllers\Admin\QueryController::class, 'show'])->name('queries.show');
        Route::patch('/queries/{query}', [\App\Http\Controllers\Admin\QueryController::class, 'update'])->name('queries.update');
        Route::delete('/queries/{query}', [\App\Http\Controllers\Admin\QueryController::class, 'destroy'])->name('queries.destroy');

        Route::resource('packages', AdminPackageController::class)->except(['show']);

        Route::resource('blogs', AdminBlogController::class)->except(['show']);
    });
