<?php

use App\Http\Controllers\AirportController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CabController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FlightBookingController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/airports/suggest', [AirportController::class, 'suggest'])->name('airports.suggest');
Route::view('/about', 'about')->name('about');
Route::view('/visa', 'visa')->name('visa');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/blog', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/blog/{blog:slug}', [BlogController::class, 'show'])->name('blogs.show');

Route::get('/flights', [FlightController::class, 'index'])->name('flights.index');
Route::get('/flights/offers/{offer}', [FlightController::class, 'offer'])->name('flights.offer');
Route::middleware('auth')->group(function () {
    Route::get('/flights/offers/{offer}/book', [FlightBookingController::class, 'create'])->name('flights.book');
    Route::post('/flights/offers/{offer}/book', [FlightBookingController::class, 'store'])->name('flights.book.store');
});
Route::get('/flights/{flight}', [FlightController::class, 'show'])->name('flights.show');

Route::post('/paypal/webhook', [\App\Http\Controllers\PaymentController::class, 'webhook'])
    ->name('payments.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/payments/success', [\App\Http\Controllers\PaymentController::class, 'success'])->name('payments.success');
    Route::get('/payments/cancel', [\App\Http\Controllers\PaymentController::class, 'cancel'])->name('payments.cancel');
});

Route::get('/hotels', [HotelController::class, 'index'])->name('hotels.index');
Route::get('/hotels/{hotel}', [HotelController::class, 'show'])->name('hotels.show');

Route::get('/cabs', [CabController::class, 'index'])->name('cabs.index');
Route::get('/cabs/{cab}', [CabController::class, 'show'])->name('cabs.show');

Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
Route::get('/packages/{travel_package:slug}', [PackageController::class, 'show'])->name('packages.show');

Route::get('/dashboard', [BookingController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
