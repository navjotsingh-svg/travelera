<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CheckoutAttempt;
use App\Models\Flight;
use App\Models\SavedPassenger;
use App\Services\BookingFulfillmentService;
use App\Services\Duffel\DuffelException;
use App\Services\Duffel\DuffelFlightService;
use App\Services\PayPal\PayPalException;
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
        private readonly BookingFulfillmentService $fulfillment,
        private readonly PlatformFeeService $platformFee,
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

        $passengers = collect($validated['passengers'])
            ->map(function (array $passenger) {
                $passenger['phone_number'] = $this->duffel->e164($passenger['phone_number']);
                $passenger['given_name'] = $this->onlyLetters($passenger['given_name']);
                $passenger['family_name'] = $this->onlyLetters($passenger['family_name']);

                return $passenger;
            })
            ->all();

        $this->rememberPassengers($request->user()->id, $passengers);

        $services = $validated['services'] ?? [];
        $orderType = $validated['payment_choice'] === 'hold' ? 'hold' : 'instant';

        try {
            $quote = $this->duffel->quote($offer, $services);
        } catch (DuffelException $exception) {
            return back()->withErrors(['offer' => $exception->getMessage()])->withInput();
        }

        $lead = $passengers[0];
        $localFlight = $this->storeLocalFlight($flight);
        $pricing = $this->platformFee->breakdown($quote['total_amount']);
        $checkoutPayload = [
            'passengers' => $passengers,
            'services' => $services,
            'selected_services' => $quote['selected_services'],
            'order_type' => $orderType,
            'payment_choice' => $validated['payment_choice'],
            'pricing' => $pricing,
        ];

        $booking = Booking::query()->create([
            'user_id' => $request->user()->id,
            'bookable_type' => Flight::class,
            'bookable_id' => $localFlight->id,
            'provider' => 'duffel',
            'duffel_offer_id' => $offer,
            'guest_name' => $lead['given_name'].' '.$lead['family_name'],
            'guest_email' => $lead['email'],
            'guest_phone' => $lead['phone_number'],
            'travelers' => $passengerCount,
            'travel_date' => optional($flight['departure_at'])?->toDateString(),
            'cabin_class' => $flight['cabin_class'],
            'base_amount' => $pricing['base_amount'],
            'platform_fee_percent' => $pricing['platform_fee_percent'],
            'platform_fee_amount' => $pricing['platform_fee_amount'],
            'total_amount' => $pricing['total_amount'],
            'currency' => $quote['total_currency'],
            'status' => 'pending',
            'payment_status' => 'pending',
            'snapshot' => array_merge(
                $this->duffel->snapshotFromOffer($flight, $quote['selected_services']),
                ['checkout' => $checkoutPayload]
            ),
        ]);

        $attempt = CheckoutAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('offer_id', $offer)
            ->whereIn('status', ['started', 'awaiting_payment'])
            ->latest('id')
            ->first();

        $attemptData = [
            'booking_id' => $booking->id,
            'airline' => $flight['airline'] ?? null,
            'flight_number' => $flight['flight_number'] ?? null,
            'origin' => $flight['origin'] ?? null,
            'destination' => $flight['destination'] ?? null,
            'amount' => $pricing['total_amount'],
            'currency' => $quote['total_currency'],
            'payload' => $checkoutPayload,
            'status' => 'awaiting_payment',
        ];

        if ($attempt) {
            $attempt->update($attemptData);
        } else {
            $attempt = CheckoutAttempt::query()->create(array_merge([
                'user_id' => $request->user()->id,
                'offer_id' => $offer,
            ], $attemptData));
        }

        // Hold orders skip PayPal and create a Duffel hold immediately.
        if ($orderType === 'hold' || ! $this->paypal->configured()) {
            try {
                $booking = $this->fulfillment->fulfillPaidBooking($booking);
            } catch (DuffelException $exception) {
                $attempt->update(['status' => 'abandoned']);

                return back()->withErrors(['offer' => $exception->getMessage()])->withInput();
            }

            $message = $orderType === 'hold'
                ? 'Seat held successfully. Complete payment before the hold expires.'
                : 'Your Duffel flight is confirmed'.($booking->airline_pnr ? '. Airline PNR: '.$booking->airline_pnr : '.')
                    .($this->paypal->configured() ? '' : ' (PayPal is disabled — booked without charge.)');

            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', $message);
        }

        try {
            $order = $this->paypal->createOrder(
                $booking,
                route('payments.success', absolute: true),
                route('payments.cancel', absolute: true),
                [
                    'name' => $flight['airline'].' '.$flight['flight_number'],
                    'description' => $flight['origin'].' → '.$flight['destination'],
                ],
                [
                    'offer_id' => $offer,
                    'provider' => 'duffel',
                ],
            );
        } catch (PayPalException $exception) {
            $booking->update(['payment_status' => 'failed', 'status' => 'cancelled']);
            $attempt->update(['status' => 'abandoned']);

            return back()->withErrors(['offer' => $exception->getMessage()])->withInput();
        }

        $booking->update(['paypal_order_id' => $order['id']]);
        $attempt->update([
            'paypal_order_id' => $order['id'],
            'booking_id' => $booking->id,
            'status' => 'awaiting_payment',
        ]);

        return redirect()->away($order['approve_url']);
    }

    private function storeLocalFlight(array $offer): Flight
    {
        return Flight::query()->create([
            'airline' => $offer['airline'],
            'flight_number' => $offer['flight_number'] ?: 'DUFFEL',
            'origin' => $offer['origin_name'] ?: $offer['origin'],
            'origin_code' => $offer['origin'] ?: 'XXX',
            'destination' => $offer['destination_name'] ?: $offer['destination'],
            'destination_code' => $offer['destination'] ?: 'XXX',
            'departure_at' => $offer['departure_at'] ?? now()->addDay(),
            'arrival_at' => $offer['arrival_at'] ?? now()->addDay()->addHours(2),
            'duration_minutes' => 0,
            'cabin_class' => is_string($offer['cabin_class']) ? $offer['cabin_class'] : 'economy',
            'price' => $offer['total_amount'],
            'seats_available' => 0,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $passengers
     */
    private function rememberPassengers(int $userId, array $passengers): void
    {
        foreach ($passengers as $passenger) {
            $given = $this->onlyLetters((string) ($passenger['given_name'] ?? ''));
            $family = $this->onlyLetters((string) ($passenger['family_name'] ?? ''));
            $bornOn = $passenger['born_on'] ?? null;

            if ($given === '' || $family === '' || ! filled($bornOn)) {
                continue;
            }

            SavedPassenger::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'given_name' => $given,
                    'family_name' => $family,
                    'born_on' => $bornOn,
                ],
                [
                    'title' => $passenger['title'] ?? 'mr',
                    'gender' => $passenger['gender'] ?? 'm',
                    'email' => $passenger['email'] ?? null,
                    'phone_number' => $passenger['phone_number'] ?? null,
                    'passport_country' => $passenger['passport_country'] ?? null,
                    'passport_number' => $passenger['passport_number'] ?? null,
                    'passport_expiry' => $passenger['passport_expiry'] ?? null,
                ]
            );
        }
    }

    private function onlyLetters(string $value): string
    {
        return trim((string) preg_replace('/[^A-Za-z \-]/', '', $value));
    }
}
