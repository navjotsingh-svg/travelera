<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        $previousStatus = $booking->status;
        $booking->update($validated);
        $booking = $booking->fresh(['user']);

        if ($previousStatus !== $booking->status) {
            $to = $booking->guest_email ?: $booking->user?->email;

            if (filled($to)) {
                try {
                    if ($booking->status === 'confirmed') {
                        Mail::to($to)->send(new BookingConfirmedMail($booking));
                    } elseif ($booking->status === 'cancelled') {
                        Mail::to($to)->send(new BookingCancelledMail($booking));
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Failed to send admin booking status email', [
                        'booking_id' => $booking->id,
                        'status' => $booking->status,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return back()->with('status', 'Booking updated.');
    }
}
