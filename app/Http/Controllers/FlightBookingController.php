<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Flight;
use App\Services\Duffel\DuffelException;
use App\Services\Duffel\DuffelFlightService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlightBookingController extends Controller
{
    public function __construct(private readonly DuffelFlightService $duffel) {}

    public function create(string $offer): View
    {
        abort_unless($this->duffel->configured(), 404);

        try {
            $flight = $this->duffel->offer($offer);
        } catch (DuffelException $exception) {
            return redirect()
                ->route('flights.index')
                ->with('status', $exception->getMessage());
        }

        return view('flights.book', compact('flight'));
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
        ]);

        $passengers = collect($validated['passengers'])
            ->map(function (array $passenger) {
                $passenger['phone_number'] = $this->duffel->e164($passenger['phone_number']);
                $passenger['given_name'] = $this->onlyLetters($passenger['given_name']);
                $passenger['family_name'] = $this->onlyLetters($passenger['family_name']);

                return $passenger;
            })
            ->all();

        try {
            $order = $this->duffel->book($offer, $passengers);
        } catch (DuffelException $exception) {
            return back()->withErrors(['offer' => $exception->getMessage()])->withInput();
        }

        $lead = $passengers[0];
        $localFlight = $this->storeLocalFlight($flight);

        $booking = Booking::query()->create([
            'user_id' => $request->user()->id,
            'bookable_type' => Flight::class,
            'bookable_id' => $localFlight->id,
            'provider' => 'duffel',
            'duffel_offer_id' => $offer,
            'duffel_order_id' => $order['id'] ?? null,
            'airline_pnr' => $order['booking_reference'] ?? null,
            'guest_name' => $lead['given_name'].' '.$lead['family_name'],
            'guest_email' => $lead['email'],
            'guest_phone' => $lead['phone_number'],
            'travelers' => $passengerCount,
            'travel_date' => optional($flight['departure_at'])?->toDateString(),
            'cabin_class' => $flight['cabin_class'],
            'total_amount' => $order['total_amount'] ?? $flight['total_amount'],
            'currency' => $order['total_currency'] ?? $flight['total_currency'],
            'status' => 'confirmed',
            'snapshot' => $this->duffel->snapshotFromOffer($flight),
        ]);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Your Duffel flight is confirmed'.(! empty($order['booking_reference']) ? '. Airline PNR: '.$order['booking_reference'] : '.'));
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
