<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CheckoutAttempt;
use App\Models\Flight;
use App\Services\BookingFulfillmentService;
use App\Services\Duffel\DuffelException;
use App\Services\Duffel\DuffelFlightService;
use App\Services\Stripe\StripeException;
use App\Services\Stripe\StripePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlightBookingController extends Controller
{
    public function __construct(
        private readonly DuffelFlightService $duffel,
        private readonly StripePaymentService $stripe,
        private readonly BookingFulfillmentService $fulfillment,
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
            'stripeEnabled' => $this->stripe->configured(),
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
            'services' => ['nullable', 'array'],
            'services.*.id' => ['required_with:services', 'string', 'max:120'],
            'services.*.quantity' => ['required_with:services', 'integer', 'min:1', 'max:9'],
        ]);

        $passengers = collect($validated['passengers'])
            ->map(function (array $passenger) {
                $passenger['phone_number'] = $this->duffel->e164($passenger['phone_number']);
                $passenger['given_name'] = $this->onlyLetters($passenger['given_name']);
                $passenger['family_name'] = $this->onlyLetters($passenger['family_name']);

                return $passenger;
            })
            ->all();

        $services = $validated['services'] ?? [];

        try {
            $quote = $this->duffel->quote($offer, $services);
        } catch (DuffelException $exception) {
            return back()->withErrors(['offer' => $exception->getMessage()])->withInput();
        }

        $lead = $passengers[0];
        $localFlight = $this->storeLocalFlight($flight);
        $checkoutPayload = [
            'passengers' => $passengers,
            'services' => $services,
            'selected_services' => $quote['selected_services'],
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
            'total_amount' => $quote['total_amount'],
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

        if ($attempt) {
            $attempt->update([
                'booking_id' => $booking->id,
                'airline' => $flight['airline'] ?? null,
                'flight_number' => $flight['flight_number'] ?? null,
                'origin' => $flight['origin'] ?? null,
                'destination' => $flight['destination'] ?? null,
                'amount' => $quote['total_amount'],
                'currency' => $quote['total_currency'],
                'payload' => $checkoutPayload,
                'status' => 'awaiting_payment',
            ]);
        } else {
            $attempt = CheckoutAttempt::query()->create([
                'user_id' => $request->user()->id,
                'offer_id' => $offer,
                'booking_id' => $booking->id,
                'airline' => $flight['airline'] ?? null,
                'flight_number' => $flight['flight_number'] ?? null,
                'origin' => $flight['origin'] ?? null,
                'destination' => $flight['destination'] ?? null,
                'amount' => $quote['total_amount'],
                'currency' => $quote['total_currency'],
                'payload' => $checkoutPayload,
                'status' => 'awaiting_payment',
            ]);
        }

        if (! $this->stripe->configured()) {
            try {
                $booking = $this->fulfillment->fulfillPaidBooking($booking);
            } catch (DuffelException $exception) {
                $attempt->update(['status' => 'abandoned']);

                return back()->withErrors(['offer' => $exception->getMessage()])->withInput();
            }

            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', 'Your Duffel flight is confirmed'
                    .($booking->airline_pnr ? '. Airline PNR: '.$booking->airline_pnr : '.')
                    .' (Stripe is disabled — booked without card charge.)');
        }

        try {
            $session = $this->stripe->createCheckoutSession(
                $booking,
                route('payments.success', absolute: true).'?session_id={CHECKOUT_SESSION_ID}',
                route('payments.cancel', absolute: true).'?session_id={CHECKOUT_SESSION_ID}',
                [
                    'name' => $flight['airline'].' '.$flight['flight_number'],
                    'description' => $flight['origin'].' → '.$flight['destination'],
                ],
                [
                    'offer_id' => $offer,
                    'provider' => 'duffel',
                ],
            );
        } catch (StripeException $exception) {
            $booking->update(['payment_status' => 'failed', 'status' => 'cancelled']);
            $attempt->update(['status' => 'abandoned']);

            return back()->withErrors(['offer' => $exception->getMessage()])->withInput();
        }

        $booking->update(['stripe_checkout_session_id' => $session->id]);
        $attempt->update([
            'stripe_checkout_session_id' => $session->id,
            'booking_id' => $booking->id,
            'status' => 'awaiting_payment',
        ]);

        return redirect()->away($session->url);
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

    private function onlyLetters(string $value): string
    {
        return trim((string) preg_replace('/[^A-Za-z \-]/', '', $value));
    }
}
