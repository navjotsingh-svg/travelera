<?php

namespace App\Http\Controllers;

use App\Mail\BookingCancelledMail;
use App\Models\Booking;
use App\Models\Cab;
use App\Models\CheckoutAttempt;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\TravelPackage;
use App\Services\BookingFulfillmentService;
use App\Services\Duffel\DuffelException;
use App\Services\Duffel\DuffelFlightService;
use App\Services\PayPal\PayPalException;
use App\Services\PayPal\PayPalPaymentService;
use App\Services\PlatformFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly PayPalPaymentService $paypal,
        private readonly BookingFulfillmentService $fulfillment,
        private readonly PlatformFeeService $platformFee,
    ) {}

    public function index(Request $request): View
    {
        $bookings = $request->user()
            ->bookings()
            ->with('bookable')
            ->latest()
            ->paginate(10);

        return view('bookings.index', compact('bookings'));
    }

    public function create(Request $request): View
    {
        $bookable = $this->resolveBookable($request->string('type')->toString(), $request->integer('id'));

        abort_unless($bookable, 404);

        return view('bookings.create', [
            'bookable' => $bookable,
            'type' => $request->string('type')->toString(),
            'paypalEnabled' => $this->paypal->configured(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->string('type')->toString();

        $validated = $request->validate([
            'type' => ['required', 'in:flight,hotel,cab,package'],
            'id' => ['required', 'integer'],
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_email' => ['required', 'email'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
            'travelers' => ['required', 'integer', 'min:1', 'max:12'],
            'travel_date' => [$type === 'cab' || $type === 'package' ? 'required' : 'nullable', 'date'],
            'check_in' => [$type === 'hotel' ? 'required' : 'nullable', 'date'],
            'check_out' => [$type === 'hotel' ? 'required' : 'nullable', 'date', 'after:check_in'],
            'pickup_location' => [$type === 'cab' ? 'required' : 'nullable', 'string', 'max:160'],
            'drop_location' => [$type === 'cab' ? 'required' : 'nullable', 'string', 'max:160'],
            'distance_km' => [$type === 'cab' ? 'required' : 'nullable', 'integer', 'min:1', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $bookable = $this->resolveBookable($validated['type'], $validated['id']);
        abort_unless($bookable, 404);

        $total = $this->calculateTotal($bookable, $validated);
        $currency = strtoupper((string) config('paypal.currency', 'USD'));
        $pricing = $this->platformFee->breakdown($total);

        $booking = Booking::query()->create([
            'user_id' => $request->user()->id,
            'bookable_type' => $bookable::class,
            'bookable_id' => $bookable->id,
            'guest_name' => $validated['guest_name'],
            'guest_email' => $validated['guest_email'],
            'guest_phone' => $validated['guest_phone'] ?? $request->user()->phone,
            'travelers' => $validated['travelers'],
            'travel_date' => $validated['travel_date'] ?? $validated['check_in'] ?? now()->toDateString(),
            'check_in' => $validated['check_in'] ?? null,
            'check_out' => $validated['check_out'] ?? null,
            'pickup_location' => $validated['pickup_location'] ?? null,
            'drop_location' => $validated['drop_location'] ?? null,
            'distance_km' => $validated['distance_km'] ?? null,
            'cabin_class' => $bookable instanceof Flight ? $bookable->cabin_class : null,
            'base_amount' => $pricing['base_amount'],
            'platform_fee_percent' => $pricing['platform_fee_percent'],
            'platform_fee_amount' => $pricing['platform_fee_amount'],
            'total_amount' => $pricing['total_amount'],
            'currency' => $currency,
            'status' => 'pending',
            'payment_status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        if (! $this->paypal->configured()) {
            $booking = $this->fulfillment->fulfillPaidBooking($booking);

            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', 'Your trip is confirmed. Safe travels!');
        }

        try {
            $order = $this->paypal->createOrder(
                $booking,
                route('payments.success', absolute: true),
                route('payments.cancel', absolute: true),
                [
                    'name' => $booking->title(),
                    'description' => 'Travelera '.$booking->typeLabel().' booking',
                ],
                ['provider' => 'local'],
            );
        } catch (PayPalException $exception) {
            $booking->update(['payment_status' => 'failed', 'status' => 'cancelled']);

            return back()->withErrors(['payment' => $exception->getMessage()])->withInput();
        }

        $booking->update(['paypal_order_id' => $order['id']]);

        CheckoutAttempt::query()->create([
            'user_id' => $request->user()->id,
            'booking_id' => $booking->id,
            'amount' => $booking->total_amount,
            'currency' => $booking->currency,
            'paypal_order_id' => $order['id'],
            'status' => 'awaiting_payment',
            'payload' => ['type' => $validated['type'], 'id' => $validated['id']],
        ]);

        return redirect()->away($order['approve_url']);
    }

    public function show(Request $request, Booking $booking): View
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        $booking->load('bookable');

        return view('bookings.show', compact('booking'));
    }

    public function cancel(Request $request, Booking $booking, DuffelFlightService $duffel): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if ($booking->status === 'cancelled') {
            return back()->with('status', 'Booking is already cancelled.');
        }

        if ($booking->provider === 'duffel' && $booking->duffel_order_id && $duffel->configured()) {
            try {
                $duffel->cancelOrder($booking->duffel_order_id);
            } catch (DuffelException $exception) {
                return back()->with('status', 'Could not cancel with the airline yet: '.$exception->getMessage());
            }
        }

        $booking->update(['status' => 'cancelled']);
        $booking = $booking->fresh(['user']);

        $to = $booking->guest_email ?: $booking->user?->email;
        if (filled($to)) {
            try {
                Mail::to($to)->send(new BookingCancelledMail($booking));
            } catch (\Throwable $exception) {
                Log::warning('Failed to send booking cancellation email', [
                    'booking_id' => $booking->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with('status', 'Booking cancelled.');
    }

    private function resolveBookable(string $type, int $id): Flight|Hotel|Cab|TravelPackage|null
    {
        return match ($type) {
            'flight' => Flight::query()->find($id),
            'hotel' => Hotel::query()->find($id),
            'cab' => Cab::query()->find($id),
            'package' => TravelPackage::query()->find($id),
            default => null,
        };
    }

    private function calculateTotal(Flight|Hotel|Cab|TravelPackage $bookable, array $data): float
    {
        $travelers = (int) $data['travelers'];

        if ($bookable instanceof Flight) {
            return (float) $bookable->price * $travelers;
        }

        if ($bookable instanceof Hotel) {
            $nights = 1;
            if (! empty($data['check_in']) && ! empty($data['check_out'])) {
                $nights = max(1, (int) now()->parse($data['check_in'])->startOfDay()->diffInDays(now()->parse($data['check_out'])->startOfDay()));
            }

            $rooms = max(1, (int) ceil($travelers / 2));

            return (float) $bookable->price_per_night * $nights * $rooms;
        }

        if ($bookable instanceof Cab) {
            $distance = (int) ($data['distance_km'] ?? 10);

            return (float) $bookable->base_fare + ((float) $bookable->price_per_km * $distance);
        }

        return (float) $bookable->price * $travelers;
    }
}
