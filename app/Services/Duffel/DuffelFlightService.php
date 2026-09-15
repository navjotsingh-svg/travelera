<?php

namespace App\Services\Duffel;

use DateInterval;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DuffelFlightService
{
    public function __construct(private readonly DuffelClient $client) {}

    public function configured(): bool
    {
        return $this->client->configured();
    }

    public function airports(): Collection
    {
        return collect(config('duffel.airports', []));
    }

    public function resolveLocation(string $input): string
    {
        $input = trim($input);

        if ($input === '') {
            return '';
        }

        if (preg_match('/\(([A-Za-z]{3})\)\s*$/', $input, $matches)) {
            return strtoupper($matches[1]);
        }

        if (preg_match('/^[A-Za-z]{3}$/', $input)) {
            return strtoupper($input);
        }

        $needle = strtolower($input);
        $best = null;
        $bestScore = 0;

        foreach ($this->airports() as $code => $label) {
            $city = strtolower(trim(explode(',', (string) $label)[0]));
            $haystack = strtolower($code.' '.$label);
            $score = 0;

            if ($haystack === $needle || $city === $needle || strtolower((string) $code) === $needle) {
                $score = 100;
            } elseif (str_starts_with($city, $needle) || str_starts_with($needle, $city)) {
                $score = 80;
            } elseif (str_contains($haystack, $needle) || str_contains($needle, $city)) {
                $score = 50;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $code;
            }
        }

        if ($best) {
            return strtoupper((string) $best);
        }

        return strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $input) ?: $input, 0, 3));
    }

    public function suggestAirports(string $query): array
    {
        $query = trim($query);

        if (strlen($query) < 1) {
            return [];
        }

        if ($this->configured()) {
            try {
                return collect($this->client->suggestPlaces($query))
                    ->map(function (array $place) {
                        $code = $place['iata_code'] ?? $place['iata_city_code'] ?? '';

                        return [
                            'code' => strtoupper((string) $code),
                            'name' => $place['name'] ?? $code,
                            'city' => $place['city_name'] ?? ($place['iata_city_code'] ?? ''),
                            'type' => $place['type'] ?? 'place',
                        ];
                    })
                    ->filter(fn (array $place) => strlen($place['code']) === 3)
                    ->unique('code')
                    ->take(8)
                    ->values()
                    ->all();
            } catch (\Throwable) {
                // Fall back to the local airport list.
            }
        }

        $needle = strtolower($query);

        return $this->airports()
            ->filter(fn (string $label, string $code) => str_contains(strtolower($code.' '.$label), $needle))
            ->map(fn (string $label, string $code) => [
                'code' => $code,
                'name' => explode(',', $label)[0],
                'city' => $label,
                'type' => 'airport',
            ])
            ->take(8)
            ->values()
            ->all();
    }

    public function search(string $origin, string $destination, string $departureDate, ?string $returnDate = null, string $cabinClass = 'economy', int $adults = 1): array
    {
        $origin = $this->resolveLocation($origin);
        $destination = $this->resolveLocation($destination);
        $adults = max(1, min(9, $adults));

        $slices = [[
            'origin' => $origin,
            'destination' => $destination,
            'departure_date' => $departureDate,
        ]];

        if (filled($returnDate)) {
            $slices[] = [
                'origin' => $destination,
                'destination' => $origin,
                'departure_date' => $returnDate,
            ];
        }

        $passengers = array_fill(0, $adults, ['type' => 'adult']);

        $payload = [
            'slices' => $slices,
            'passengers' => $passengers,
        ];

        if (in_array($cabinClass, ['economy', 'premium_economy', 'business', 'first'], true)) {
            $payload['cabin_class'] = $cabinClass;
        }

        $request = $this->client->createOfferRequest($payload);
        $offers = collect($request['offers'] ?? [])
            ->sortBy(fn (array $offer) => (float) ($offer['total_amount'] ?? 0))
            ->take(20)
            ->values()
            ->map(fn (array $offer) => $this->presentOffer($offer))
            ->all();

        return [
            'id' => $request['id'] ?? null,
            'passengers' => $request['passengers'] ?? [],
            'offers' => $offers,
        ];
    }

    public function offer(string $offerId): array
    {
        return $this->presentOffer($this->client->getOffer($offerId), detailed: true);
    }

    public function book(string $offerId, array $passengers): array
    {
        $offer = $this->client->getOffer($offerId);

        $orderPassengers = [];
        foreach ($offer['passengers'] ?? [] as $index => $offerPassenger) {
            $input = $passengers[$index] ?? [];
            $orderPassengers[] = [
                'id' => $offerPassenger['id'],
                'title' => $input['title'],
                'given_name' => $input['given_name'],
                'family_name' => $input['family_name'],
                'born_on' => $input['born_on'],
                'gender' => $input['gender'],
                'email' => $input['email'],
                'phone_number' => $input['phone_number'],
            ];
        }

        return $this->client->createOrder([
            'type' => 'instant',
            'selected_offers' => [$offerId],
            'passengers' => $orderPassengers,
            'payments' => [[
                'type' => config('duffel.payment_type', 'balance'),
                'amount' => $offer['total_amount'],
                'currency' => $offer['total_currency'],
            ]],
            'metadata' => [
                'source' => 'travelera',
            ],
        ]);
    }

    public function cancelOrder(string $orderId): array
    {
        $cancellation = $this->client->createOrderCancellation($orderId);

        return $this->client->confirmOrderCancellation($cancellation['id']);
    }

    public function presentOffer(array $offer, bool $detailed = false): array
    {
        $slices = collect($offer['slices'] ?? []);
        $firstSlice = $slices->first() ?? [];
        $firstSegment = collect($firstSlice['segments'] ?? [])->first() ?? [];
        $lastSegment = collect($firstSlice['segments'] ?? [])->last() ?? $firstSegment;
        $carrier = $firstSegment['operating_carrier'] ?? $firstSegment['marketing_carrier'] ?? [];

        $presented = [
            'id' => $offer['id'],
            'airline' => $carrier['name'] ?? $offer['owner']['name'] ?? 'Airline',
            'airline_code' => $carrier['iata_code'] ?? $offer['owner']['iata_code'] ?? '',
            'flight_number' => $this->flightNumber($firstSegment),
            'origin' => $firstSegment['origin']['iata_code'] ?? ($firstSlice['origin']['iata_code'] ?? ''),
            'origin_name' => $firstSegment['origin']['name'] ?? ($firstSlice['origin']['name'] ?? ''),
            'destination' => $lastSegment['destination']['iata_code'] ?? ($firstSlice['destination']['iata_code'] ?? ''),
            'destination_name' => $lastSegment['destination']['name'] ?? ($firstSlice['destination']['name'] ?? ''),
            'departure_at' => isset($firstSegment['departing_at']) ? Carbon::parse($firstSegment['departing_at']) : null,
            'arrival_at' => isset($lastSegment['arriving_at']) ? Carbon::parse($lastSegment['arriving_at']) : null,
            'duration' => $this->formatDuration($firstSlice['duration'] ?? null),
            'stops' => max(0, count($firstSlice['segments'] ?? []) - 1),
            'cabin_class' => $offer['cabin_class'] ?? ($firstSlice['fare_brand_name'] ?? 'economy'),
            'total_amount' => $offer['total_amount'] ?? '0',
            'total_currency' => $offer['total_currency'] ?? 'USD',
            'expires_at' => isset($offer['expires_at']) ? Carbon::parse($offer['expires_at']) : null,
            'passengers' => $offer['passengers'] ?? [],
            'passenger_count' => count($offer['passengers'] ?? []),
            'is_return' => $slices->count() > 1,
        ];

        if ($detailed) {
            $presented['slices'] = $slices->map(function (array $slice) {
                return [
                    'origin' => $slice['origin']['iata_code'] ?? '',
                    'destination' => $slice['destination']['iata_code'] ?? '',
                    'duration' => $this->formatDuration($slice['duration'] ?? null),
                    'segments' => collect($slice['segments'] ?? [])->map(function (array $segment) {
                        return [
                            'airline' => $segment['operating_carrier']['name'] ?? ($segment['marketing_carrier']['name'] ?? ''),
                            'flight_number' => $this->flightNumber($segment),
                            'origin' => $segment['origin']['iata_code'] ?? '',
                            'origin_name' => $segment['origin']['name'] ?? '',
                            'destination' => $segment['destination']['iata_code'] ?? '',
                            'destination_name' => $segment['destination']['name'] ?? '',
                            'departure_at' => isset($segment['departing_at']) ? Carbon::parse($segment['departing_at']) : null,
                            'arrival_at' => isset($segment['arriving_at']) ? Carbon::parse($segment['arriving_at']) : null,
                            'duration' => $this->formatDuration($segment['duration'] ?? null),
                        ];
                    })->all(),
                ];
            })->all();
            $presented['conditions'] = $offer['conditions'] ?? [];
            $presented['raw'] = $offer;
        }

        return $presented;
    }

    public function snapshotFromOffer(array $offer): array
    {
        return [
            'airline' => $offer['airline'],
            'flight_number' => $offer['flight_number'],
            'origin' => $offer['origin'],
            'destination' => $offer['destination'],
            'departure_at' => optional($offer['departure_at'])->toIso8601String(),
            'arrival_at' => optional($offer['arrival_at'])->toIso8601String(),
            'cabin_class' => $offer['cabin_class'],
            'duration' => $offer['duration'],
            'stops' => $offer['stops'],
            'is_return' => $offer['is_return'],
            'slices' => $offer['slices'] ?? [],
        ];
    }

    public function e164(?string $phone): string
    {
        $raw = trim((string) $phone);
        $digits = preg_replace('/\D+/', '', $raw) ?: '';

        if (str_starts_with($raw, '+')) {
            return '+'.$digits;
        }

        if (strlen($digits) === 10) {
            return '+91'.$digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        return $digits !== '' ? '+'.$digits : '+910000000000';
    }

    private function flightNumber(array $segment): string
    {
        $code = $segment['marketing_carrier']['iata_code'] ?? $segment['operating_carrier']['iata_code'] ?? '';
        $number = $segment['marketing_carrier_flight_number'] ?? $segment['operating_carrier_flight_number'] ?? '';

        return trim($code.' '.$number);
    }

    private function formatDuration(?string $iso): string
    {
        if (! $iso) {
            return '';
        }

        try {
            $interval = new DateInterval($iso);
            $hours = ($interval->d * 24) + $interval->h;

            return $hours.'h '.$interval->i.'m';
        } catch (Exception) {
            return Str::of($iso)->replace(['PT', 'H', 'M'], ['', 'h ', 'm'])->toString();
        }
    }
}
