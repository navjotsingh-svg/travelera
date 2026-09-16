<?php

namespace App\Services;

use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\CheckoutAttempt;
use App\Services\Duffel\DuffelException;
use App\Services\Duffel\DuffelFlightService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BookingFulfillmentService
{
    public function __construct(private readonly DuffelFlightService $duffel) {}

    public function fulfillPaidBooking(Booking $booking): Booking
    {
        $this->ensureConnection();

        if ($this->isFullyConfirmed($booking)) {
            return $booking;
        }

        $wasConfirmed = $booking->status === 'confirmed';

        if ($booking->provider === 'duffel') {
            $fulfilled = $this->fulfillDuffelBooking($booking);
        } else {
            $fulfilled = $this->runWithDatabase(function () use ($booking) {
                return DB::transaction(function () use ($booking) {
                    $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

                    if ($this->isFullyConfirmed($booking)) {
                        return $booking;
                    }

                    $booking->update([
                        'status' => 'confirmed',
                        'payment_status' => 'paid',
                    ]);

                    $this->completeCheckoutAttempts($booking);

                    return $booking->fresh();
                });
            });
        }

        if (! $wasConfirmed && $fulfilled->status === 'confirmed') {
            $this->sendConfirmationEmail($fulfilled);
        }

        return $fulfilled;
    }

    public function sendConfirmationEmail(Booking $booking): void
    {
        $to = $booking->guest_email ?: $booking->user?->email;

        if (! filled($to)) {
            return;
        }

        try {
            Mail::to($to)->send(new BookingConfirmedMail($booking));
        } catch (Throwable $exception) {
            Log::warning('Failed to send booking confirmation email', [
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function fulfillDuffelBooking(Booking $booking): Booking
    {
        $claim = $this->runWithDatabase(function () use ($booking) {
            return DB::transaction(function () use ($booking) {
                $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

                $attempt = CheckoutAttempt::query()
                    ->where('booking_id', $booking->id)
                    ->latest('id')
                    ->first();

                $payload = $attempt?->payload ?? ($booking->snapshot['checkout'] ?? []);
                $passengers = $payload['passengers'] ?? [];
                $services = $payload['services'] ?? [];
                $orderType = ($payload['order_type'] ?? 'instant') === 'hold' ? 'hold' : 'instant';
                $offerId = $booking->duffel_offer_id;

                if ($booking->duffel_order_id) {
                    $booking->update([
                        'status' => 'confirmed',
                        'payment_status' => $orderType === 'hold' ? 'pending' : 'paid',
                    ]);
                    $this->completeCheckoutAttempts($booking);

                    return [
                        'done' => true,
                        'booking' => $booking->fresh(),
                    ];
                }

                if ($this->isFullyConfirmed($booking)) {
                    return [
                        'done' => true,
                        'booking' => $booking,
                    ];
                }

                if (! $offerId || $passengers === []) {
                    $booking->update(['payment_status' => 'failed', 'status' => 'pending']);

                    throw new DuffelException('Missing passenger data to complete this Duffel booking.');
                }

                return [
                    'done' => false,
                    'booking_id' => $booking->id,
                    'offer_id' => $offerId,
                    'passengers' => $passengers,
                    'services' => $services,
                    'order_type' => $orderType,
                    'payload' => $payload,
                ];
            });
        });

        if ($claim['done'] ?? false) {
            return $claim['booking'];
        }

        try {
            $order = $this->duffel->book(
                $claim['offer_id'],
                $claim['passengers'],
                $claim['services'],
                $claim['order_type'],
            );
            $flight = $this->duffel->offer($claim['offer_id']);
        } catch (DuffelException $exception) {
            Log::error('Duffel fulfillment failed after Stripe payment', [
                'booking_id' => $claim['booking_id'],
                'message' => $exception->getMessage(),
            ]);

            $this->runWithDatabase(function () use ($claim, $exception) {
                $booking = Booking::query()->find($claim['booking_id']);

                if (! $booking) {
                    return;
                }

                $booking->update([
                    'payment_status' => $claim['order_type'] === 'hold' ? 'pending' : 'paid',
                    'status' => 'pending',
                    'notes' => trim(($booking->notes ? $booking->notes."\n" : '').'Payment received; airline booking failed: '.$exception->getMessage()),
                ]);
            });

            throw $exception;
        }

        $selectedServices = $order['_selected_services'] ?? [];

        return $this->runWithDatabase(function () use ($claim, $order, $flight, $selectedServices) {
            return DB::transaction(function () use ($claim, $order, $flight, $selectedServices) {
                $booking = Booking::query()->lockForUpdate()->findOrFail($claim['booking_id']);

                // Another request (success page + webhook) may have finished first.
                if ($booking->duffel_order_id) {
                    $booking->update([
                        'status' => 'confirmed',
                        'payment_status' => $claim['order_type'] === 'hold' ? 'pending' : 'paid',
                    ]);
                    $this->completeCheckoutAttempts($booking);

                    return $booking->fresh();
                }

                $booking->update([
                    'duffel_order_id' => $order['id'] ?? null,
                    'airline_pnr' => $order['booking_reference'] ?? null,
                    'total_amount' => $order['total_amount'] ?? $booking->total_amount,
                    'currency' => $order['total_currency'] ?? $booking->currency,
                    'status' => 'confirmed',
                    'payment_status' => $claim['order_type'] === 'hold' ? 'pending' : 'paid',
                    'snapshot' => array_merge(
                        $this->duffel->snapshotFromOffer($flight, $selectedServices),
                        ['checkout' => $claim['payload']]
                    ),
                ]);

                $this->completeCheckoutAttempts($booking);

                return $booking->fresh();
            });
        });
    }

    private function isFullyConfirmed(Booking $booking): bool
    {
        if ($booking->status !== 'confirmed') {
            return false;
        }

        if ($booking->provider === 'duffel') {
            return filled($booking->duffel_order_id)
                && in_array($booking->payment_status, ['paid', 'pending'], true);
        }

        return $booking->payment_status === 'paid';
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

    /**
     * Run a database callback, reconnecting once if MySQL dropped the connection
     * (common after slow external API calls on shared hosts).
     */
    private function runWithDatabase(callable $callback): mixed
    {
        $this->ensureConnection();

        try {
            return $callback();
        } catch (Throwable $exception) {
            if (! $this->isGoneAway($exception)) {
                throw $exception;
            }

            Log::warning('MySQL connection lost during booking fulfillment; reconnecting.', [
                'message' => $exception->getMessage(),
            ]);

            DB::reconnect();

            return $callback();
        }
    }

    private function ensureConnection(): void
    {
        try {
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (Throwable) {
            DB::reconnect();
        }
    }

    private function isGoneAway(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'server has gone away')
            || str_contains($message, 'lost connection')
            || str_contains($message, 'error while sending query packet')
            || (($exception->getCode() === 'HY000' || (int) $exception->getCode() === 2006));
    }
}
