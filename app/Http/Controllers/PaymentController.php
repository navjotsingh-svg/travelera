<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CheckoutAttempt;
use App\Services\BookingFulfillmentService;
use App\Services\Duffel\DuffelException;
use App\Services\Stripe\StripeException;
use App\Services\Stripe\StripePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly StripePaymentService $stripe,
        private readonly BookingFulfillmentService $fulfillment,
    ) {}

    public function success(Request $request): RedirectResponse|View
    {
        $sessionId = (string) $request->query('session_id', '');
        abort_unless($sessionId !== '', 404);

        try {
            $session = $this->stripe->retrieveCheckoutSession($sessionId);
        } catch (StripeException $exception) {
            return redirect()
                ->route('bookings.index')
                ->with('status', $exception->getMessage());
        }

        $booking = Booking::query()
            ->where('stripe_checkout_session_id', $session->id)
            ->first();

        if (! $booking && filled($session->client_reference_id)) {
            $booking = Booking::query()->find($session->client_reference_id);
        }

        abort_unless($booking && $booking->user_id === $request->user()?->id, 404);

        $paymentIntentId = is_string($session->payment_intent)
            ? $session->payment_intent
            : ($session->payment_intent->id ?? null);

        $booking->update([
            'stripe_checkout_session_id' => $session->id,
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);

        if (($session->payment_status ?? '') !== 'paid') {
            $booking->update(['payment_status' => 'pending']);

            return view('payments.pending', compact('booking'));
        }

        try {
            $booking = $this->fulfillment->fulfillPaidBooking($booking);
        } catch (DuffelException $exception) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', 'Payment received, but airline confirmation is pending: '.$exception->getMessage());
        } catch (\Throwable $exception) {
            if (! str_contains(strtolower($exception->getMessage()), 'server has gone away')
                && ! str_contains(strtolower($exception->getMessage()), 'lost connection')) {
                throw $exception;
            }

            Log::warning('Payment success retrying after MySQL disconnect', [
                'booking_id' => $booking->id,
                'message' => $exception->getMessage(),
            ]);

            DB::reconnect();
            $booking = $booking->fresh() ?? $booking;

            try {
                $booking = $this->fulfillment->fulfillPaidBooking($booking);
            } catch (DuffelException $duffelException) {
                return redirect()
                    ->route('bookings.show', $booking)
                    ->with('status', 'Payment received, but airline confirmation is pending: '.$duffelException->getMessage());
            }
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Payment successful. Your booking is confirmed'
                .($booking->airline_pnr ? '. Airline PNR: '.$booking->airline_pnr : '.'));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');

        $booking = null;
        if ($sessionId !== '') {
            $booking = Booking::query()
                ->where('stripe_checkout_session_id', $sessionId)
                ->where('user_id', $request->user()->id)
                ->first();
        }

        if ($booking && $booking->payment_status !== 'paid') {
            $booking->update([
                'payment_status' => 'abandoned',
                'status' => 'cancelled',
            ]);

            CheckoutAttempt::query()
                ->where('booking_id', $booking->id)
                ->orWhere(function ($query) use ($booking) {
                    $query->where('stripe_checkout_session_id', $booking->stripe_checkout_session_id);
                })
                ->update(['status' => 'abandoned']);
        }

        if ($booking) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', 'Payment cancelled. You can try again when you are ready.');
        }

        return redirect()
            ->route('bookings.index')
            ->with('status', 'Payment cancelled. You can try again when you are ready.');
    }

    public function webhook(Request $request): Response
    {
        $signature = (string) $request->header('Stripe-Signature', '');

        try {
            $event = $this->stripe->constructWebhookEvent($request->getContent(), $signature);
        } catch (StripeException $exception) {
            Log::warning('Stripe webhook rejected', ['message' => $exception->getMessage()]);

            return response($exception->getMessage(), 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $booking = Booking::query()
                ->where('stripe_checkout_session_id', $session->id)
                ->first();

            if (! $booking && filled($session->client_reference_id ?? null)) {
                $booking = Booking::query()->find($session->client_reference_id);
            }

            if ($booking && ($session->payment_status ?? '') === 'paid') {
                $paymentIntentId = is_string($session->payment_intent ?? null)
                    ? $session->payment_intent
                    : ($session->payment_intent->id ?? null);

                $booking->update([
                    'stripe_checkout_session_id' => $session->id,
                    'stripe_payment_intent_id' => $paymentIntentId ?? $booking->stripe_payment_intent_id,
                ]);

                try {
                    $this->fulfillment->fulfillPaidBooking($booking);
                } catch (DuffelException $exception) {
                    Log::error('Webhook fulfillment failed', [
                        'booking_id' => $booking->id,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return response('ok', 200);
    }
}
