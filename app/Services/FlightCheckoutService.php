<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CheckoutAttempt;
use App\Models\Flight;
use App\Models\SavedPassenger;
use App\Models\User;
use App\Services\Duffel\DuffelException;
use App\Services\Duffel\DuffelFlightService;
use App\Services\PayPal\PayPalException;
use App\Services\PayPal\PayPalPaymentService;

class FlightCheckoutService
{
    public function __construct(
        private readonly DuffelFlightService $duffel,
        private readonly PayPalPaymentService $paypal,
        private readonly BookingFulfillmentService $fulfillment,
        private readonly PlatformFeeService $platformFee,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *     ok: bool,
     *     error?: string,
     *     kind?: string,
     *     message?: string,
     *     approve_url?: string,
     *     booking_id?: int,
     *     booking_url?: string,
     *     pnr?: string|null,
     *     total_amount?: string,
     *     currency?: string
     * }
     */
    public function start(User $user, string $offerId, array $validated): array
    {
        try {
            $flight = $this->duffel->offer($offerId);
        } catch (DuffelException $exception) {
            return ['ok' => false, 'error' => $exception->getMessage()];
        }

        $passengerCount = max(1, count($flight['passengers'] ?? []));
        if (count($validated['passengers'] ?? []) !== $passengerCount) {
            return ['ok' => false, 'error' => 'This fare needs '.$passengerCount.' passenger'.($passengerCount === 1 ? '' : 's').'.'];
        }

        if (($validated['payment_choice'] ?? '') === 'hold' && empty($flight['supports_hold'])) {
            return ['ok' => false, 'error' => 'This fare cannot be held. Choose pay now to continue.'];
        }

        $passengers = collect($validated['passengers'])
            ->map(function (array $passenger) {
                $passenger['phone_number'] = $this->duffel->e164($passenger['phone_number'] ?? '');
                $passenger['given_name'] = $this->onlyLetters($passenger['given_name'] ?? '');
                $passenger['family_name'] = $this->onlyLetters($passenger['family_name'] ?? '');

                return $passenger;
            })
            ->all();

        $this->rememberPassengers($user->id, $passengers);

        $services = $validated['services'] ?? [];
        $orderType = ($validated['payment_choice'] ?? '') === 'hold' ? 'hold' : 'instant';

        try {
            $quote = $this->duffel->quote($offerId, $services);
        } catch (DuffelException $exception) {
            return ['ok' => false, 'error' => $exception->getMessage()];
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
            'source' => 'agent',
        ];

        $booking = Booking::query()->create([
            'user_id' => $user->id,
            'bookable_type' => Flight::class,
            'bookable_id' => $localFlight->id,
            'provider' => 'duffel',
            'duffel_offer_id' => $offerId,
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
            ->where('user_id', $user->id)
            ->where('offer_id', $offerId)
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
            CheckoutAttempt::query()->create(array_merge([
                'user_id' => $user->id,
                'offer_id' => $offerId,
            ], $attemptData));
        }

        if ($orderType === 'hold' || ! $this->paypal->configured()) {
            try {
                $booking = $this->fulfillment->fulfillPaidBooking($booking);
            } catch (DuffelException $exception) {
                return ['ok' => false, 'error' => $exception->getMessage()];
            }

            $message = $orderType === 'hold'
                ? 'Seat held. Complete payment before the hold expires. I did not charge you.'
                : 'Booking confirmed'.($booking->airline_pnr ? '. Airline PNR: '.$booking->airline_pnr : '.')
                    .($this->paypal->configured() ? '' : ' PayPal is off, so this was confirmed without a card charge.');

            return [
                'ok' => true,
                'kind' => $orderType === 'hold' ? 'hold' : 'confirmed',
                'message' => $message,
                'booking_id' => $booking->id,
                'booking_url' => route('bookings.show', $booking),
                'pnr' => $booking->airline_pnr,
                'total_amount' => number_format((float) $pricing['total_amount'], 2, '.', ''),
                'currency' => (string) $quote['total_currency'],
            ];
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
                    'offer_id' => $offerId,
                    'provider' => 'duffel',
                    'source' => 'agent',
                ],
            );
        } catch (PayPalException $exception) {
            $booking->update(['payment_status' => 'failed', 'status' => 'cancelled']);

            return ['ok' => false, 'error' => $exception->getMessage()];
        }

        $booking->update(['paypal_order_id' => $order['id']]);

        return [
            'ok' => true,
            'kind' => 'paypal',
            'message' => 'Review the total, then open PayPal yourself. Nothing is charged until you approve it there.',
            'approve_url' => $order['approve_url'],
            'booking_id' => $booking->id,
            'booking_url' => route('bookings.show', $booking),
            'total_amount' => number_format((float) $pricing['total_amount'], 2, '.', ''),
            'currency' => (string) $quote['total_currency'],
        ];
    }

    /**
     * @param  array<string, mixed>  $offer
     */
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
