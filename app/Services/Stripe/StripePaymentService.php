<?php

namespace App\Services\Stripe;

use App\Models\Booking;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripePaymentService
{
    public function configured(): bool
    {
        return (bool) config('stripe.enabled')
            && filled(config('stripe.secret'))
            && filled(config('stripe.key'));
    }

    public function publishableKey(): ?string
    {
        return config('stripe.key');
    }

    /**
     * @param  array{name?: string, description?: string, images?: array<int, string>}  $product
     * @param  array<string, string>  $metadata
     */
    public function createCheckoutSession(
        Booking $booking,
        string $successUrl,
        string $cancelUrl,
        array $product = [],
        array $metadata = [],
    ): Session {
        $currency = strtolower((string) ($booking->currency ?: config('stripe.currency', 'inr')));
        $amount = $this->toStripeAmount((float) $booking->total_amount, $currency);

        try {
            return $this->client()->checkout->sessions->create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'customer_email' => $booking->guest_email,
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => $amount,
                        'product_data' => [
                            'name' => $product['name'] ?? $booking->title(),
                            'description' => $product['description'] ?? ('Travelera booking '.$booking->booking_reference),
                        ],
                    ],
                ]],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => (string) $booking->id,
                'metadata' => array_merge([
                    'booking_id' => (string) $booking->id,
                    'booking_reference' => (string) $booking->booking_reference,
                ], $metadata),
            ]);
        } catch (ApiErrorException $exception) {
            throw StripeException::fromStripe($exception);
        }
    }

    public function retrieveCheckoutSession(string $sessionId): Session
    {
        try {
            return $this->client()->checkout->sessions->retrieve($sessionId, [
                'expand' => ['payment_intent'],
            ]);
        } catch (ApiErrorException $exception) {
            throw StripeException::fromStripe($exception);
        }
    }

    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        $secret = (string) config('stripe.webhook_secret');

        if ($secret === '') {
            throw new StripeException('Stripe webhook secret is not configured.');
        }

        try {
            return Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException $exception) {
            throw new StripeException('Invalid Stripe webhook signature.', previous: $exception);
        }
    }

    public function toStripeAmount(float $amount, string $currency): int
    {
        $currency = strtolower($currency);

        if (in_array($currency, $this->zeroDecimalCurrencies(), true)) {
            return (int) round($amount);
        }

        return (int) round($amount * 100);
    }

    public function fromStripeAmount(int $amount, string $currency): string
    {
        $currency = strtolower($currency);

        if (in_array($currency, $this->zeroDecimalCurrencies(), true)) {
            return number_format($amount, 2, '.', '');
        }

        return number_format($amount / 100, 2, '.', '');
    }

    private function client(): StripeClient
    {
        if (! $this->configured()) {
            throw new StripeException('Stripe is not configured. Add STRIPE_KEY, STRIPE_SECRET and set STRIPE_ENABLED=true.');
        }

        return new StripeClient((string) config('stripe.secret'));
    }

    /**
     * @return list<string>
     */
    private function zeroDecimalCurrencies(): array
    {
        return [
            'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg',
            'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
        ];
    }
}
