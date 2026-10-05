<?php

use App\Http\Controllers\AgentChatController;
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
use App\Http\Controllers\StorageFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/airports/suggest', [AirportController::class, 'suggest'])->name('airports.suggest');
Route::view('/about', 'about')->name('about');
Route::view('/visa', 'visa')->name('visa');
Route::view('/terms-and-conditions', 'legal.terms')->name('legal.terms');
Route::view('/cancellation-refund-policy', 'legal.cancellation')->name('legal.cancellation');
Route::view('/privacy-policy', 'legal.privacy')->name('legal.privacy');
Route::view('/disclaimer', 'legal.disclaimer')->name('legal.disclaimer');
Route::redirect('/privacy', '/privacy-policy');
Route::redirect('/flight-cancellation-policy', '/cancellation-refund-policy');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/blog', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/blog/{blog:slug}', [BlogController::class, 'show'])->name('blogs.show');

/*
 | Fallback when public/storage symlink is broken/blocked (shared hosting 403).
 | If a working symlink exists, the web server serves the file first.
 */
Route::get('/storage/{path}', [StorageFileController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.fallback');

Route::get('/flights', [FlightController::class, 'index'])->name('flights.index');
Route::get('/flights/offers/{offer}', [FlightController::class, 'offer'])->name('flights.offer');
Route::get('/flights/offers/{offer}/book', [FlightBookingController::class, 'create'])->name('flights.book');
Route::post('/flights/offers/{offer}/book', [FlightBookingController::class, 'store'])->name('flights.book.store');
Route::get('/flights/{flight}', [FlightController::class, 'show'])->name('flights.show');

Route::get('/agent', [AgentChatController::class, 'show'])->name('agent.chat');
Route::get('/agent/login', [AgentChatController::class, 'login'])->name('agent.login');
Route::post('/agent/chat', [AgentChatController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('agent.chat.store');
Route::post('/agent/checkout', [AgentChatController::class, 'checkout'])
    ->middleware('throttle:10,1')
    ->name('agent.checkout');

Route::post('/paypal/webhook', [\App\Http\Controllers\PaymentController::class, 'webhook'])
    ->name('payments.webhook');

Route::get('/payments/success', [\App\Http\Controllers\PaymentController::class, 'success'])->name('payments.success');
Route::get('/payments/cancel', [\App\Http\Controllers\PaymentController::class, 'cancel'])->name('payments.cancel');

Route::get('/hotels', [HotelController::class, 'index'])->name('hotels.index');
Route::get('/hotels/{hotel}', [HotelController::class, 'show'])->name('hotels.show');

Route::get('/cabs', [CabController::class, 'index'])->name('cabs.index');
Route::get('/cabs/{cab}', [CabController::class, 'show'])->name('cabs.show');

Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
Route::get('/packages/{travel_package:slug}', [PackageController::class, 'show'])->name('packages.show');

Route::get('/dashboard', [BookingController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

Route::middleware('auth')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
