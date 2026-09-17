<?php

namespace App\Services\PayPal;

use App\Models\Booking;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayPalPaymentService
{
    public function configured(): bool
    {
        return (bool) config('paypal.enabled')
            && filled(config('paypal.client_id'))
            && filled(config('paypal.client_secret'));
    }

    /**
     * @param  array{name?: string, description?: string}  $product
     * @param  array<string, string>  $metadata
     * @return array{id: string, status: string, approve_url: string, raw: array<string, mixed>}
     */
    public function createOrder(
        Booking $booking,
        string $returnUrl,
        string $cancelUrl,
        array $product = [],
        array $metadata = [],
    ): array {
        $currency = $this->normalizeCurrency((string) ($booking->currency ?: config('paypal.currency', 'USD')));
        $amount = $this->formatAmount((float) $booking->total_amount, $currency);

        if ((float) $amount <= 0) {
            throw PayPalException::fromResponse('PayPal order amount must be greater than zero.');
        }

        $description = $this->sanitizeText(
            $product['description'] ?? ('Travelera booking '.$booking->booking_reference),
            127
        );

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => Str::limit(preg_replace('/[^A-Za-z0-9._\-]/', '', (string) $booking->booking_reference) ?: 'booking-'.$booking->id, 256, ''),
                'custom_id' => (string) $booking->id,
                'description' => $description,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => $amount,
                ],
            ]],
            'application_context' => [
                'brand_name' => 'Travelera',
                'landing_page' => 'LOGIN',
                'user_action' => 'PAY_NOW',
                'shipping_preference' => 'NO_SHIPPING',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ];

        // Keep line items out of the payload. They require a matching breakdown and
        // DIGITAL_GOODS often triggers schema/merchant-category rejections.

        $order = $this->request('post', '/v2/checkout/orders', $payload, 'order-'.$booking->id.'-'.Str::random(8));
        $approveUrl = collect($order['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        if (! filled($approveUrl) || ! filled($order['id'] ?? null)) {
            throw PayPalException::fromResponse('PayPal did not return an approval link.');
        }

        return [
            'id' => (string) $order['id'],
            'status' => (string) ($order['status'] ?? 'CREATED'),
            'approve_url' => (string) $approveUrl,
            'raw' => $order,
        ];
    }

    /**
     * @return array{id: string, status: string, capture_id: ?string, paid: bool, booking_id: ?string, raw: array<string, mixed>}
     */
    public function captureOrder(string $orderId): array
    {
        try {
            // PayPal requires an empty JSON object `{}`, not `[]`.
            $order = $this->request(
                'post',
                '/v2/checkout/orders/'.rawurlencode($orderId).'/capture',
                new \stdClass,
                'capture-'.$orderId
            );

            return $this->presentOrder($order);
        } catch (PayPalException $exception) {
            // Idempotent: payment already captured (refresh / double submit).
            if (str_contains(strtolower($exception->getMessage()), 'already captured')
                || str_contains(strtolower($exception->getMessage()), 'order_already_captured')) {
                return $this->retrieveOrder($orderId);
            }

            throw $exception;
        }
    }

    /**
     * @return array{id: string, status: string, capture_id: ?string, paid: bool, booking_id: ?string, raw: array<string, mixed>}
     */
    public function retrieveOrder(string $orderId): array
    {
        $order = $this->request('get', '/v2/checkout/orders/'.rawurlencode($orderId));

        return $this->presentOrder($order);
    }

    public function verifyWebhook(string $payload, array $headers): array
    {
        $webhookId = (string) config('paypal.webhook_id');

        if (! filled($webhookId)) {
            $decoded = json_decode($payload, true);

            if (! is_array($decoded)) {
                throw PayPalException::fromResponse('Invalid PayPal webhook payload.');
            }

            return $decoded;
        }

        $verification = $this->request('post', '/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $this->headerValue($headers, 'PAYPAL-AUTH-ALGO'),
            'cert_url' => $this->headerValue($headers, 'PAYPAL-CERT-URL'),
            'transmission_id' => $this->headerValue($headers, 'PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $this->headerValue($headers, 'PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $this->headerValue($headers, 'PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => $webhookId,
            'webhook_event' => json_decode($payload, true),
        ]);

        if (($verification['verification_status'] ?? '') !== 'SUCCESS') {
            throw PayPalException::fromResponse('Invalid PayPal webhook signature.');
        }

        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            throw PayPalException::fromResponse('Invalid PayPal webhook payload.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    private function headerValue(array $headers, string $name): string
    {
        foreach ([$name, strtolower($name), strtoupper($name)] as $key) {
            if (! array_key_exists($key, $headers)) {
                continue;
            }

            $value = $headers[$key];

            return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
        }

        return '';
    }

    public function formatAmount(float $amount, string $currency): string
    {
        $currency = strtoupper($currency);
        $zeroDecimal = ['HUF', 'JPY', 'TWD'];

        if (in_array($currency, $zeroDecimal, true)) {
            return (string) (int) round($amount);
        }

        return number_format($amount, 2, '.', '');
    }

    public function normalizeCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        if ($currency === '') {
            return strtoupper((string) config('paypal.currency', 'USD'));
        }

        return $currency;
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array{id: string, status: string, capture_id: ?string, paid: bool, booking_id: ?string, raw: array<string, mixed>}
     */
    private function presentOrder(array $order): array
    {
        $purchase = $order['purchase_units'][0] ?? [];
        $captures = $purchase['payments']['captures'][0] ?? null;
        $status = strtoupper((string) ($order['status'] ?? ''));
        $captureStatus = strtoupper((string) ($captures['status'] ?? ''));

        $paid = $captureStatus === 'COMPLETED' || $status === 'COMPLETED';

        return [
            'id' => (string) ($order['id'] ?? ''),
            'status' => $status,
            'capture_id' => isset($captures['id']) ? (string) $captures['id'] : null,
            'paid' => $paid,
            'booking_id' => isset($purchase['custom_id']) ? (string) $purchase['custom_id'] : null,
            'raw' => $order,
        ];
    }

    /**
     * @param  array<string, mixed>|\stdClass|null  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array|\stdClass|null $payload = null, ?string $requestId = null): array
    {
        $url = $this->baseUrl().$path;
        $client = $this->http($requestId);
        $method = strtolower($method);

        $response = match ($method) {
            'get' => $client->get($url),
            'delete' => $client->delete($url),
            'post' => $payload === null
                ? $client->withBody('{}', 'application/json')->post($url)
                : $client->post($url, $payload),
            'patch' => $client->patch($url, $payload ?? []),
            default => $client->{$method}($url, $payload ?? []),
        };

        if ($response->failed()) {
            $json = $response->json();
            $details = collect(data_get($json, 'details', []))
                ->map(function ($detail) {
                    if (! is_array($detail)) {
                        return null;
                    }

                    return trim(implode(' ', array_filter([
                        $detail['issue'] ?? null,
                        $detail['description'] ?? null,
                        isset($detail['field']) ? '('.$detail['field'].')' : null,
                    ])));
                })
                ->filter()
                ->implode('; ');

            $message = $details
                ?: (string) (data_get($json, 'message')
                    ?? data_get($json, 'error_description')
                    ?? ('PayPal request failed (HTTP '.$response->status().').'));

            Log::warning('PayPal API error', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'debug_id' => data_get($json, 'debug_id'),
                'body' => $json,
            ]);

            throw PayPalException::fromResponse($message, $response->status());
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function http(?string $requestId = null): PendingRequest
    {
        $headers = [
            'Prefer' => 'return=representation',
        ];

        if (filled($requestId)) {
            $headers['PayPal-Request-Id'] = Str::limit($requestId, 36, '');
        }

        return Http::withToken($this->accessToken())
            ->withHeaders($headers)
            ->acceptJson()
            ->asJson()
            ->timeout(45);
    }

    private function accessToken(): string
    {
        $cacheKey = 'paypal.access_token.'.sha1((string) config('paypal.client_id').'|'.(string) config('paypal.mode'));

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $response = Http::asForm()
                ->withBasicAuth((string) config('paypal.client_id'), (string) config('paypal.client_secret'))
                ->timeout(20)
                ->post($this->baseUrl().'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->failed() || ! filled($response->json('access_token'))) {
                throw PayPalException::fromResponse(
                    (string) (data_get($response->json(), 'error_description') ?: 'Unable to authenticate with PayPal.'),
                    $response->status()
                );
            }

            return (string) $response->json('access_token');
        });
    }

    private function baseUrl(): string
    {
        if (filled(config('paypal.base_url'))) {
            return rtrim((string) config('paypal.base_url'), '/');
        }

        return config('paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function sanitizeText(string $value, int $max): string
    {
        $value = trim(preg_replace('/\s+/', ' ', str_replace(['→', '←', '·'], ['to', 'from', '-'], $value)) ?? '');
        $value = preg_replace('/[^\P{C}\n]+/u', '', $value) ?? $value;

        return Str::limit($value !== '' ? $value : 'Travelera booking', $max, '');
    }
}
