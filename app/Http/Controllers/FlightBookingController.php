<?php

namespace App\Http\Controllers;

use App\Models\CheckoutAttempt;
use App\Models\SavedPassenger;
use App\Services\Duffel\DuffelException;
use App\Services\Duffel\DuffelFlightService;
use App\Services\FlightCheckoutService;
use App\Services\PayPal\PayPalPaymentService;
use App\Services\PlatformFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlightBookingController extends Controller
{
    public function __construct(
        private readonly DuffelFlightService $duffel,
        private readonly PayPalPaymentService $paypal,
        private readonly PlatformFeeService $platformFee,
        private readonly FlightCheckoutService $checkout,
    ) {}

    public function create(string $offer): View|RedirectResponse
    {
        abort_unless($this->duffel->configured(), 404);

        try {
            $flight = $this->duffel->offer($offer);
            $seatMaps = $this->duffel->seatMaps($offer);
        } catch (DuffelException $exception) {
            return redirect()
                ->route('flights.index')
                ->with('status', $exception->getMessage());
        }

        CheckoutAttempt::query()->updateOrCreate(
            [
                'user_id' => request()->user()?->id,
                'offer_id' => $offer,
                'status' => 'started',
            ],
            [
                'airline' => $flight['airline'] ?? null,
                'flight_number' => $flight['flight_number'] ?? null,
                'origin' => $flight['origin'] ?? null,
                'destination' => $flight['destination'] ?? null,
                'amount' => $flight['total_amount'] ?? null,
                'currency' => $flight['total_currency'] ?? 'INR',
            ]
        );

        return view('flights.book', [
            'flight' => $flight,
            'seatMaps' => $seatMaps,
            'paypalEnabled' => $this->paypal->configured(),
            'platformFeePercent' => $this->platformFee->percent(),
            'defaultPhone' => filled(auth()->user()?->phone)
                ? $this->duffel->e164(auth()->user()->phone)
                : '',
            'savedPassengers' => auth()->user()
                ?->savedPassengers()
                ->get()
                ->map(fn (SavedPassenger $passenger) => $passenger->toFormArray())
                ->values()
                ->all() ?? [],
        ]);
    }

    public function store(Request $request, string $offer): RedirectResponse
    {
        abort_unless($this->duffel->configured(), 404);

        try {
            $flight = $this->duffel->offer($offer);
        } catch (DuffelException $exception) {
            return back()->withErrors(['offer' => $exception->getMessage()])->withInput();
        }

        $passengerCount = max(1, count($flight['passengers']));

        $validated = $request->validate([
            'passengers' => ['required', 'array', 'size:'.$passengerCount],
            'passengers.*.title' => ['required', 'in:mr,ms,mrs,miss,dr'],
            'passengers.*.given_name' => ['required', 'string', 'max:80'],
            'passengers.*.family_name' => ['required', 'string', 'max:80'],
            'passengers.*.gender' => ['required', 'in:m,f'],
            'passengers.*.born_on' => ['required', 'date', 'before:today'],
            'passengers.*.email' => ['required', 'email'],
            'passengers.*.phone_number' => ['required', 'string', 'max:30'],
            'passengers.*.passport_country' => ['nullable', 'string', 'max:80'],
            'passengers.*.passport_number' => ['nullable', 'string', 'max:40'],
            'passengers.*.passport_expiry' => ['nullable', 'date', 'after:today'],
            'payment_choice' => ['required', 'in:pay_now,hold'],
            'services' => ['nullable', 'array'],
            'services.*.id' => ['required_with:services', 'string', 'max:120'],
            'services.*.quantity' => ['required_with:services', 'integer', 'min:1', 'max:9'],
        ]);

        if ($validated['payment_choice'] === 'hold' && empty($flight['supports_hold'])) {
            return back()->withErrors(['offer' => 'This fare cannot be held. Please pay now to confirm.'])->withInput();
        }

        $result = $this->checkout->start($request->user(), $offer, $validated);

        if (! ($result['ok'] ?? false)) {
            return back()->withErrors(['offer' => $result['error'] ?? 'Checkout failed.'])->withInput();
        }

        if (($result['kind'] ?? '') === 'paypal' && filled($result['approve_url'] ?? null)) {
            return redirect()->away($result['approve_url']);
        }

        return redirect()
            ->route('bookings.show', $result['booking_id'])
            ->with('status', $result['message'] ?? 'Booking updated.');
    }
}
