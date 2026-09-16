<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = Booking::query()
            ->with('user')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($q) {
                    $inner->where('booking_reference', 'like', $q)
                        ->orWhere('guest_name', 'like', $q)
                        ->orWhere('guest_email', 'like', $q);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking): View
    {
        $booking->load(['user', 'bookable']);

        return view('admin.bookings.show', compact('booking'));
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,cancelled,pending'],
            'payment_status' => ['required', 'in:paid,pending,failed,refunded,abandoned'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $booking->update($validated);

        return back()->with('status', 'Booking updated.');
    }
}
