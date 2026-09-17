<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CheckoutAttempt;
use App\Services\BookingFulfillmentService;
use App\Services\Duffel\DuffelException;
use App\Services\PayPal\PayPalException;
use App\Services\PayPal\PayPalPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PayPalPaymentService $paypal,
        private readonly BookingFulfillmentService $fulfillment,
    ) {}

    public function success(Request $request): RedirectResponse|View
    {
        $orderId = (string) ($request->query('token') ?: $request->query('order_id', ''));
        abort_unless($orderId !== '', 404);

        $booking = Booking::query()
            ->where('paypal_order_id', $orderId)
            ->first();

        try {
            $order = $this->paypal->retrieveOrder($orderId);

            if ($order['paid']) {
                // Already captured (webhook or prior refresh).
            } elseif (in_array(strtoupper($order['status']), ['APPROVED', 'CREATED', 'SAVED', 'PAYER_ACTION_REQUIRED'], true)) {
                $order = $this->paypal->captureOrder($orderId);
            }
        } catch (PayPalException $exception) {
            Log::warning('PayPal success capture failed', [
                'order_id' => $orderId,
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('bookings.index')
                ->with('status', 'PayPal payment could not be confirmed: '.$exception->getMessage());
        }

        if (! $booking && filled($order['booking_id'])) {
            $booking = Booking::query()->find($order['booking_id']);
        }

        abort_unless($booking && $booking->user_id === $request->user()?->id, 404);

        $booking->update([
            'paypal_order_id' => $order['id'],
            'paypal_capture_id' => $order['capture_id'] ?? $booking->paypal_capture_id,
        ]);

        if (! $order['paid']) {
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
        $orderId = (string) ($request->query('token') ?: $request->query('order_id', ''));

        $booking = null;
        if ($orderId !== '') {
            $booking = Booking::query()
                ->where('paypal_order_id', $orderId)
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
                    $query->where('paypal_order_id', $booking->paypal_order_id);
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
        try {
            $event = $this->paypal->verifyWebhook($request->getContent(), $request->headers->all());
        } catch (PayPalException $exception) {
            Log::warning('PayPal webhook rejected', ['message' => $exception->getMessage()]);

            return response($exception->getMessage(), 400);
        }

        $eventType = (string) ($event['event_type'] ?? '');

        if (in_array($eventType, [
            'CHECKOUT.ORDER.APPROVED',
            'PAYMENT.CAPTURE.COMPLETED',
            'CHECKOUT.ORDER.COMPLETED',
        ], true)) {
            $resource = $event['resource'] ?? [];
            $orderId = null;
            $captureId = null;
            $bookingId = null;

            if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
                $captureId = $resource['id'] ?? null;
                $orderId = data_get($resource, 'supplementary_data.related_ids.order_id')
                    ?? data_get($resource, 'custom_id');
                $bookingId = data_get($resource, 'custom_id');
            } else {
                $orderId = $resource['id'] ?? null;
                $bookingId = data_get($resource, 'purchase_units.0.custom_id');
                $captureId = data_get($resource, 'purchase_units.0.payments.captures.0.id');
            }

            $booking = null;
            if (filled($orderId)) {
                $booking = Booking::query()->where('paypal_order_id', $orderId)->first();
            }
            if (! $booking && filled($bookingId) && is_numeric($bookingId)) {
                $booking = Booking::query()->find($bookingId);
            }

            if ($booking) {
                if ($eventType === 'CHECKOUT.ORDER.APPROVED' && filled($orderId)) {
                    try {
                        $captured = $this->paypal->captureOrder((string) $orderId);
                        $captureId = $captured['capture_id'] ?? $captureId;
                        $orderId = $captured['id'] ?: $orderId;
                    } catch (PayPalException $exception) {
                        Log::warning('PayPal webhook capture skipped', [
                            'booking_id' => $booking->id,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }

                $booking->update([
                    'paypal_order_id' => $orderId ?: $booking->paypal_order_id,
                    'paypal_capture_id' => $captureId ?: $booking->paypal_capture_id,
                ]);

                try {
                    $this->fulfillment->fulfillPaidBooking($booking->fresh());
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
