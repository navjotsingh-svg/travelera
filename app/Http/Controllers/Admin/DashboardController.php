<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Booking;
use App\Models\CheckoutAttempt;
use App\Models\FlightSearch;
use App\Models\TravelPackage;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users' => User::query()->count(),
            'bookings' => Booking::query()->count(),
            'confirmed_bookings' => Booking::query()->where('status', 'confirmed')->count(),
            'cancelled_bookings' => Booking::query()->where('status', 'cancelled')->count(),
            'paid' => Booking::query()->where('payment_status', 'paid')->count(),
            'pending_payments' => Booking::query()->where('payment_status', 'pending')->count(),
            'revenue' => (float) Booking::query()->where('payment_status', 'paid')->sum('total_amount'),
            'searches' => FlightSearch::query()->count(),
            'searches_today' => FlightSearch::query()->whereDate('created_at', today())->count(),
            'abandoned' => CheckoutAttempt::abandoned()->count(),
            'packages' => TravelPackage::query()->count(),
            'blogs' => Blog::query()->count(),
            'published_blogs' => Blog::query()->published()->count(),
        ];

        $recentBookings = Booking::query()
            ->with('user')
            ->latest()
            ->limit(8)
            ->get();

        $recentSearches = FlightSearch::query()
            ->with('user')
            ->latest()
            ->limit(8)
            ->get();

        $abandoned = CheckoutAttempt::abandoned()
            ->with('user')
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentBookings', 'recentSearches', 'abandoned'));
    }
}
