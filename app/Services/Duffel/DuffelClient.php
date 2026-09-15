<?php

namespace App\Services\Duffel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class DuffelClient
{
    private readonly ?string $accessToken;

    private readonly string $baseUrl;

    private readonly string $version;

    private readonly int $timeout;

    public function __construct(
        ?string $accessToken = null,
        ?string $baseUrl = null,
        ?string $version = null,
        ?int $timeout = null,
    ) {
        $this->accessToken = $accessToken ?? config('duffel.access_token');
        $this->baseUrl = $baseUrl ?? (string) config('duffel.base_url');
        $this->version = $version ?? (string) config('duffel.version');
        $this->timeout = $timeout ?? (int) config('duffel.timeout');
    }

    public function configured(): bool
    {
        return filled($this->accessToken);
    }

    public function createOfferRequest(array $data): array
    {
        return $this->request('post', '/air/offer_requests', [
            'query' => [
                'return_offers' => 'true',
                'supplier_timeout' => 20000,
            ],
            'json' => ['data' => $data],
        ]);
    }

    public function getOffer(string $offerId): array
    {
        return $this->request('get', '/air/offers/'.$offerId);
    }

    public function suggestPlaces(string $query): array
    {
        return $this->request('get', '/places/suggestions', [
            'query' => ['query' => $query],
        ]);
    }

    public function createOrder(array $data): array
    {
        return $this->request('post', '/air/orders', [
            'json' => ['data' => $data],
        ]);
    }

    public function createOrderCancellation(string $orderId): array
    {
        return $this->request('post', '/air/order_cancellations', [
            'json' => ['data' => ['order_id' => $orderId]],
        ]);
    }

    public function confirmOrderCancellation(string $cancellationId): array
    {
        return $this->request('post', '/air/order_cancellations/'.$cancellationId.'/actions/confirm');
    }

    private function request(string $method, string $uri, array $options = []): array
    {
        if (! $this->configured()) {
            throw new DuffelException('Duffel is not configured. Add DUFFEL_ACCESS_TOKEN to your .env file.');
        }

        try {
            $response = $this->http()->send($method, $uri, $options);
        } catch (RequestException $exception) {
            throw DuffelException::fromResponse($exception->response);
        }

        if ($response->failed()) {
            throw DuffelException::fromResponse($response);
        }

        return $response->json('data') ?? [];
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->withToken((string) $this->accessToken)
            ->withHeaders([
                'Duffel-Version' => $this->version,
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip',
            ])
            ->timeout($this->timeout)
            ->retry(1, 500, throw: false);
    }
}
