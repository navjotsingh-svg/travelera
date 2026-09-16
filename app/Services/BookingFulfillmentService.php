<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CheckoutAttempt;
use App\Models\Flight;
use App\Services\Duffel\DuffelException;
use App\Services\Duffel\DuffelFlightService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingFulfillmentService
{
    public function __construct(private readonly DuffelFlightService $duffel) {}

    public function fulfillPaidBooking(Booking $booking): Booking
    {
        if ($booking->payment_status === 'paid' && $booking->status === 'confirmed') {
            return $booking;
        }

        return DB::transaction(function () use ($booking) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->payment_status === 'paid' && $booking->status === 'confirmed') {
                return $booking;
            }

            if ($booking->provider === 'duffel') {
                return $this->fulfillDuffelBooking($booking);
            }

            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);

            $this->completeCheckoutAttempts($booking);

            return $booking->fresh();
        });
    }

    private function fulfillDuffelBooking(Booking $booking): Booking
    {
        $attempt = CheckoutAttempt::query()
            ->where('booking_id', $booking->id)
            ->latest('id')
            ->first();

        $payload = $attempt?->payload ?? ($booking->snapshot['checkout'] ?? []);
        $passengers = $payload['passengers'] ?? [];
        $services = $payload['services'] ?? [];
        $offerId = $booking->duffel_offer_id;

        if (! $offerId || $passengers === []) {
            $booking->update(['payment_status' => 'failed', 'status' => 'pending']);
            throw new DuffelException('Missing passenger data to complete this Duffel booking.');
        }

        if ($booking->duffel_order_id) {
            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);
            $this->completeCheckoutAttempts($booking);

            return $booking->fresh();
        }

        try {
            $order = $this->duffel->book($offerId, $passengers, $services);
        } catch (DuffelException $exception) {
            Log::error('Duffel fulfillment failed after Stripe payment', [
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
            ]);
            $booking->update([
                'payment_status' => 'paid',
                'status' => 'pending',
                'notes' => trim(($booking->notes ? $booking->notes."\n" : '').'Payment received; airline booking failed: '.$exception->getMessage()),
            ]);

            throw $exception;
        }

        $flight = $this->duffel->offer($offerId);
        $selectedServices = $order['_selected_services'] ?? [];

        $booking->update([
            'duffel_order_id' => $order['id'] ?? null,
            'airline_pnr' => $order['booking_reference'] ?? null,
            'total_amount' => $order['total_amount'] ?? $booking->total_amount,
            'currency' => $order['total_currency'] ?? $booking->currency,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'snapshot' => array_merge(
                $this->duffel->snapshotFromOffer($flight, $selectedServices),
                ['checkout' => $payload]
            ),
        ]);

        $this->completeCheckoutAttempts($booking);

        return $booking->fresh();
    }

    private function completeCheckoutAttempts(Booking $booking): void
    {
        CheckoutAttempt::query()
            ->where(function ($query) use ($booking) {
                $query->where('booking_id', $booking->id);

                if ($booking->duffel_offer_id) {
                    $query->orWhere(function ($inner) use ($booking) {
                        $inner->where('offer_id', $booking->duffel_offer_id)
                            ->where('user_id', $booking->user_id)
                            ->whereIn('status', ['started', 'awaiting_payment']);
                    });
                }
            })
            ->update([
                'status' => 'completed',
                'booking_id' => $booking->id,
                'completed_at' => now(),
            ]);
    }
}
