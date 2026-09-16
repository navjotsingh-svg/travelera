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
        return $this->presentOffer($this->client->getOffer($offerId, returnAvailableServices: true), detailed: true);
    }

    public function seatMaps(string $offerId): array
    {
        try {
            $maps = $this->client->getSeatMaps($offerId);
        } catch (\Throwable) {
            return [];
        }

        return collect(is_array($maps) ? $maps : [])
            ->map(fn (array $map) => $this->presentSeatMap($map))
            ->filter()
            ->values()
            ->all();
    }

    public function quote(string $offerId, array $services = []): array
    {
        $offer = $this->client->getOffer($offerId, returnAvailableServices: true);
        $selectedServices = $this->normalizeSelectedServices($offer, $services);
        $totalAmount = $this->sumAmounts(
            (string) ($offer['total_amount'] ?? '0'),
            collect($selectedServices)->sum(fn (array $service) => (float) $service['line_total'])
        );

        return [
            'offer' => $offer,
            'selected_services' => $selectedServices,
            'total_amount' => $totalAmount,
            'total_currency' => $offer['total_currency'] ?? 'USD',
        ];
    }

    public function book(string $offerId, array $passengers, array $services = [], string $orderType = 'instant'): array
    {
        $offer = $this->client->getOffer($offerId, returnAvailableServices: true);
        $selectedServices = $this->normalizeSelectedServices($offer, $services);
        $orderType = $orderType === 'hold' ? 'hold' : 'instant';

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

        $paymentAmount = $this->sumAmounts(
            (string) ($offer['total_amount'] ?? '0'),
            collect($selectedServices)->sum(fn (array $service) => (float) $service['line_total'])
        );

        $payload = [
            'type' => $orderType,
            'selected_offers' => [$offerId],
            'passengers' => $orderPassengers,
            'metadata' => [
                'source' => 'travelera',
            ],
        ];

        if ($orderType === 'instant') {
            $payload['payments'] = [[
                'type' => config('duffel.payment_type', 'balance'),
                'amount' => $paymentAmount,
                'currency' => $offer['total_currency'] ?? 'USD',
            ]];
        }

        if ($selectedServices !== []) {
            $payload['services'] = collect($selectedServices)
                ->map(fn (array $service) => [
                    'id' => $service['id'],
                    'quantity' => $service['quantity'],
                ])
                ->values()
                ->all();
        }

        $order = $this->client->createOrder($payload);
        $order['_selected_services'] = $selectedServices;
        $order['_charged_amount'] = $paymentAmount;
        $order['_order_type'] = $orderType;

        return $order;
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
            'airline_logo' => $carrier['logo_symbol_url']
                ?? ($offer['owner']['logo_symbol_url'] ?? null),
            'flight_number' => $this->flightNumber($firstSegment),
            'origin' => $firstSegment['origin']['iata_code'] ?? ($firstSlice['origin']['iata_code'] ?? ''),
            'origin_name' => $firstSegment['origin']['name'] ?? ($firstSlice['origin']['name'] ?? ''),
            'origin_city' => $firstSegment['origin']['city_name'] ?? ($firstSlice['origin']['city_name'] ?? ''),
            'destination' => $lastSegment['destination']['iata_code'] ?? ($firstSlice['destination']['iata_code'] ?? ''),
            'destination_name' => $lastSegment['destination']['name'] ?? ($firstSlice['destination']['name'] ?? ''),
            'destination_city' => $lastSegment['destination']['city_name'] ?? ($firstSlice['destination']['city_name'] ?? ''),
            'departure_at' => isset($firstSegment['departing_at']) ? Carbon::parse($firstSegment['departing_at']) : null,
            'arrival_at' => isset($lastSegment['arriving_at']) ? Carbon::parse($lastSegment['arriving_at']) : null,
            'duration' => $this->formatDuration($firstSlice['duration'] ?? null),
            'stops' => max(0, count($firstSlice['segments'] ?? []) - 1),
            'cabin_class' => $offer['cabin_class'] ?? 'economy',
            'fare_brand' => $firstSlice['fare_brand_name'] ?? ($offer['cabin_class'] ?? 'Standard'),
            'total_amount' => $offer['total_amount'] ?? '0',
            'total_currency' => $offer['total_currency'] ?? 'USD',
            'expires_at' => isset($offer['expires_at']) ? Carbon::parse($offer['expires_at']) : null,
            'passengers' => $offer['passengers'] ?? [],
            'passenger_count' => count($offer['passengers'] ?? []),
            'is_return' => $slices->count() > 1,
            'flight_key' => implode('|', [
                $firstSegment['origin']['iata_code'] ?? '',
                $lastSegment['destination']['iata_code'] ?? '',
                $firstSegment['departing_at'] ?? '',
                $this->flightNumber($firstSegment),
            ]),
            'supports_hold' => ! (bool) ($offer['payment_requirements']['requires_instant_payment'] ?? false),
            'carbon_emissions' => $firstSlice['carbon_emissions']['tonnes']
                ?? ($offer['total_emissions_kg'] ?? null),
        ];

        $presented['fare_features'] = $this->presentFareFeatures($offer, $detailed);

        if ($detailed) {
            $presented['slices'] = $slices->map(function (array $slice) {
                return [
                    'origin' => $slice['origin']['iata_code'] ?? '',
                    'origin_name' => $slice['origin']['name'] ?? '',
                    'origin_city' => $slice['origin']['city_name'] ?? '',
                    'destination' => $slice['destination']['iata_code'] ?? '',
                    'destination_name' => $slice['destination']['name'] ?? '',
                    'destination_city' => $slice['destination']['city_name'] ?? '',
                    'duration' => $this->formatDuration($slice['duration'] ?? null),
                    'fare_brand' => $slice['fare_brand_name'] ?? null,
                    'segments' => collect($slice['segments'] ?? [])->map(function (array $segment) {
                        return [
                            'id' => $segment['id'] ?? null,
                            'airline' => $segment['operating_carrier']['name'] ?? ($segment['marketing_carrier']['name'] ?? ''),
                            'airline_logo' => $segment['operating_carrier']['logo_symbol_url']
                                ?? ($segment['marketing_carrier']['logo_symbol_url'] ?? null),
                            'flight_number' => $this->flightNumber($segment),
                            'aircraft' => $segment['aircraft']['name'] ?? null,
                            'origin' => $segment['origin']['iata_code'] ?? '',
                            'origin_name' => $segment['origin']['name'] ?? '',
                            'origin_city' => $segment['origin']['city_name'] ?? '',
                            'destination' => $segment['destination']['iata_code'] ?? '',
                            'destination_name' => $segment['destination']['name'] ?? '',
                            'destination_city' => $segment['destination']['city_name'] ?? '',
                            'departure_at' => isset($segment['departing_at']) ? Carbon::parse($segment['departing_at']) : null,
                            'arrival_at' => isset($segment['arriving_at']) ? Carbon::parse($segment['arriving_at']) : null,
                            'duration' => $this->formatDuration($segment['duration'] ?? null),
                            'passengers' => collect($segment['passengers'] ?? [])->map(function (array $passenger) {
                                return [
                                    'passenger_id' => $passenger['passenger_id'] ?? null,
                                    'cabin_class' => $passenger['cabin_class'] ?? null,
                                    'baggages' => collect($passenger['baggages'] ?? [])->map(fn (array $bag) => [
                                        'type' => $bag['type'] ?? 'checked',
                                        'quantity' => (int) ($bag['quantity'] ?? 0),
                                    ])->all(),
                                ];
                            })->all(),
                        ];
                    })->all(),
                ];
            })->all();
            $presented['conditions'] = $offer['conditions'] ?? [];
            $presented['included_baggage'] = $this->presentIncludedBaggage($offer);
            $presented['bag_services'] = $this->presentBagServices($offer);
            $presented['available_services'] = $offer['available_services'] ?? [];
            $presented['raw'] = $offer;
        }

        return $presented;
    }

    public function snapshotFromOffer(array $offer, array $selectedServices = []): array
    {
        $slices = collect($offer['slices'] ?? [])->map(function (array $slice) {
            return [
                ...$slice,
                'segments' => collect($slice['segments'] ?? [])->map(function (array $segment) {
                    return [
                        ...$segment,
                        'departure_at' => $this->toIsoString($segment['departure_at'] ?? null),
                        'arrival_at' => $this->toIsoString($segment['arrival_at'] ?? null),
                    ];
                })->all(),
            ];
        })->all();

        return [
            'airline' => $offer['airline'],
            'flight_number' => $offer['flight_number'],
            'origin' => $offer['origin'],
            'destination' => $offer['destination'],
            'departure_at' => $this->toIsoString($offer['departure_at'] ?? null),
            'arrival_at' => $this->toIsoString($offer['arrival_at'] ?? null),
            'cabin_class' => $offer['cabin_class'],
            'duration' => $offer['duration'],
            'stops' => $offer['stops'],
            'is_return' => $offer['is_return'],
            'slices' => $slices,
            'included_baggage' => $offer['included_baggage'] ?? [],
            'selected_services' => $selectedServices,
        ];
    }

    private function toIsoString(mixed $value): ?string
    {
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->toIso8601String();
        }

        if (is_string($value) && filled($value)) {
            try {
                return \Carbon\Carbon::parse($value)->toIso8601String();
            } catch (\Throwable) {
                return $value;
            }
        }

        return null;
    }

    public function e164(?string $phone): string
    {
        $raw = trim((string) $phone);
        $digits = preg_replace('/\D+/', '', $raw) ?: '';

        // Collapse accidental doubled Indian country codes (+91+91..., 9191...).
        while (str_starts_with($digits, '9191') && strlen($digits) > 12) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($raw, '+') || str_starts_with($digits, '00')) {
            if (str_starts_with($digits, '00')) {
                $digits = substr($digits, 2);
            }

            return $digits !== '' ? '+'.$digits : '+910000000000';
        }

        if (strlen($digits) === 10) {
            return '+91'.$digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return '+'.$digits;
        }

        return $digits !== '' ? '+'.$digits : '+910000000000';
    }

    private function presentFareFeatures(array $offer, bool $detailed = false): array
    {
        $conditions = $offer['conditions'] ?? [];
        $change = $conditions['change_before_departure'] ?? null;
        $refund = $conditions['refund_before_departure'] ?? null;
        $supportsHold = ! (bool) ($offer['payment_requirements']['requires_instant_payment'] ?? false);

        $features = [];

        if (is_array($change)) {
            if (($change['allowed'] ?? false) === true) {
                $penalty = $change['penalty_amount'] ?? null;
                $currency = $change['penalty_currency'] ?? ($offer['total_currency'] ?? '');
                $features[] = [
                    'type' => 'changes',
                    'label' => $penalty !== null && (float) $penalty > 0
                        ? 'Changes allowed (from '.$currency.' '.$penalty.')'
                        : 'Changes allowed',
                ];
            } elseif (array_key_exists('allowed', $change) && $change['allowed'] === false) {
                $features[] = ['type' => 'changes', 'label' => 'Changes not allowed'];
            } else {
                $features[] = ['type' => 'changes', 'label' => 'No data on changes'];
            }
        } else {
            $features[] = ['type' => 'changes', 'label' => 'No data on changes'];
        }

        if (is_array($refund)) {
            if (($refund['allowed'] ?? false) === true) {
                $penalty = $refund['penalty_amount'] ?? null;
                $currency = $refund['penalty_currency'] ?? ($offer['total_currency'] ?? '');
                $features[] = [
                    'type' => 'refunds',
                    'label' => $penalty !== null && (float) $penalty > 0
                        ? 'Refundable (from '.$currency.' '.$penalty.')'
                        : 'Refundable',
                ];
            } elseif (array_key_exists('allowed', $refund) && $refund['allowed'] === false) {
                $features[] = ['type' => 'refunds', 'label' => 'Non-refundable'];
            } else {
                $features[] = ['type' => 'refunds', 'label' => 'No data on refunds'];
            }
        } else {
            $features[] = ['type' => 'refunds', 'label' => 'No data on refunds'];
        }

        $features[] = [
            'type' => 'hold',
            'label' => $supportsHold ? 'Hold space' : 'Pay now required',
        ];

        $bags = $detailed ? $this->presentIncludedBaggage($offer) : [];
        if ($bags === [] && ! $detailed) {
            // Light scan for list offers that include segment passenger baggages.
            $bags = $this->presentIncludedBaggage($offer);
        }

        $hasCarryOn = collect($bags)->contains(fn (array $bag) => ($bag['type'] ?? '') === 'carry_on');
        $hasChecked = collect($bags)->contains(fn (array $bag) => ($bag['type'] ?? '') === 'checked');

        $features[] = [
            'type' => 'carry_on',
            'label' => $hasCarryOn ? 'Includes carry-on bags' : 'Carry-on not included',
        ];
        $features[] = [
            'type' => 'checked',
            'label' => $hasChecked ? 'Includes checked bags' : 'Checked bags not included',
        ];

        return $features;
    }

    private function presentIncludedBaggage(array $offer): array
    {
        $bags = [];

        foreach ($offer['slices'] ?? [] as $sliceIndex => $slice) {
            foreach ($slice['segments'] ?? [] as $segment) {
                foreach ($segment['passengers'] ?? [] as $passenger) {
                    foreach ($passenger['baggages'] ?? [] as $bag) {
                        $type = (string) ($bag['type'] ?? 'checked');
                        $quantity = (int) ($bag['quantity'] ?? 0);
                        if ($quantity < 1) {
                            continue;
                        }

                        $key = $type;
                        if (! isset($bags[$key])) {
                            $bags[$key] = [
                                'type' => $type,
                                'label' => $this->baggageLabel($type),
                                'quantity' => $quantity,
                                'slice_indexes' => [],
                            ];
                        } else {
                            $bags[$key]['quantity'] = max($bags[$key]['quantity'], $quantity);
                        }

                        if (! in_array($sliceIndex, $bags[$key]['slice_indexes'], true)) {
                            $bags[$key]['slice_indexes'][] = $sliceIndex;
                        }
                    }
                }
            }
        }

        return array_values($bags);
    }

    private function presentBagServices(array $offer): array
    {
        return collect($offer['available_services'] ?? [])
            ->filter(fn (array $service) => ($service['type'] ?? '') === 'baggage')
            ->map(function (array $service) {
                $meta = $service['metadata'] ?? [];
                $bagType = $meta['type'] ?? 'checked';
                $weight = $meta['maximum_weight_kg'] ?? null;

                return [
                    'id' => $service['id'],
                    'type' => 'baggage',
                    'bag_type' => $bagType,
                    'label' => $this->baggageLabel((string) $bagType),
                    'weight_kg' => $weight,
                    'description' => $weight
                        ? $this->baggageLabel((string) $bagType).' · up to '.$weight.' kg'
                        : $this->baggageLabel((string) $bagType),
                    'total_amount' => (string) ($service['total_amount'] ?? '0'),
                    'total_currency' => $service['total_currency'] ?? ($offer['total_currency'] ?? 'USD'),
                    'maximum_quantity' => max(1, (int) ($service['maximum_quantity'] ?? 1)),
                    'passenger_ids' => $service['passenger_ids'] ?? [],
                    'segment_ids' => $service['segment_ids'] ?? [],
                ];
            })
            ->values()
            ->all();
    }

    private function presentSeatMap(array $map): ?array
    {
        $cabins = collect($map['cabins'] ?? [])->map(function (array $cabin) {
            $rows = collect($cabin['rows'] ?? [])->map(function (array $row) {
                $elements = [];
                foreach ($row['sections'] ?? [] as $sectionIndex => $section) {
                    if ($sectionIndex > 0) {
                        $elements[] = ['type' => 'aisle', 'designator' => null];
                    }
                    foreach ($section['elements'] ?? [] as $element) {
                        $services = collect($element['available_services'] ?? [])->map(fn (array $service) => [
                            'id' => $service['id'],
                            'passenger_id' => $service['passenger_id'] ?? null,
                            'total_amount' => (string) ($service['total_amount'] ?? '0'),
                            'total_currency' => $service['total_currency'] ?? 'USD',
                        ])->values()->all();

                        $elements[] = [
                            'type' => $element['type'] ?? 'seat',
                            'designator' => $element['designator'] ?? null,
                            'name' => $element['name'] ?? '',
                            'disclosures' => $element['disclosures'] ?? [],
                            'available_services' => $services,
                            'available' => ($element['type'] ?? '') === 'seat' && $services !== [],
                        ];
                    }
                }

                return ['elements' => $elements];
            })->filter(fn (array $row) => $row['elements'] !== [])->values()->all();

            return [
                'cabin_class' => $cabin['cabin_class'] ?? 'economy',
                'rows' => $rows,
            ];
        })->filter(fn (array $cabin) => $cabin['rows'] !== [])->values()->all();

        if ($cabins === []) {
            return null;
        }

        return [
            'id' => $map['id'] ?? null,
            'segment_id' => $map['segment_id'] ?? null,
            'slice_id' => $map['slice_id'] ?? null,
            'cabins' => $cabins,
        ];
    }

    private function normalizeSelectedServices(array $offer, array $services): array
    {
        if ($services === []) {
            return [];
        }

        $catalog = [];

        foreach ($offer['available_services'] ?? [] as $service) {
            if (! isset($service['id'])) {
                continue;
            }
            $catalog[$service['id']] = [
                'id' => $service['id'],
                'type' => $service['type'] ?? 'baggage',
                'total_amount' => (string) ($service['total_amount'] ?? '0'),
                'total_currency' => $service['total_currency'] ?? ($offer['total_currency'] ?? 'USD'),
                'maximum_quantity' => max(1, (int) ($service['maximum_quantity'] ?? 1)),
                'label' => $this->baggageLabel((string) (($service['metadata']['type'] ?? 'checked'))),
                'designator' => null,
            ];
        }

        try {
            foreach ($this->client->getSeatMaps($offer['id'] ?? '') as $map) {
                foreach ($map['cabins'] ?? [] as $cabin) {
                    foreach ($cabin['rows'] ?? [] as $row) {
                        foreach ($row['sections'] ?? [] as $section) {
                            foreach ($section['elements'] ?? [] as $element) {
                                foreach ($element['available_services'] ?? [] as $service) {
                                    if (! isset($service['id'])) {
                                        continue;
                                    }
                                    $catalog[$service['id']] = [
                                        'id' => $service['id'],
                                        'type' => 'seat',
                                        'total_amount' => (string) ($service['total_amount'] ?? '0'),
                                        'total_currency' => $service['total_currency'] ?? ($offer['total_currency'] ?? 'USD'),
                                        'maximum_quantity' => 1,
                                        'label' => 'Seat '.($element['designator'] ?? ''),
                                        'designator' => $element['designator'] ?? null,
                                        'passenger_id' => $service['passenger_id'] ?? null,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable) {
            // Seat maps may be unavailable for some offers.
        }

        $normalized = [];
        foreach ($services as $service) {
            $id = $service['id'] ?? null;
            $quantity = max(0, (int) ($service['quantity'] ?? 0));
            if (! $id || $quantity < 1 || ! isset($catalog[$id])) {
                continue;
            }

            $quantity = min($quantity, $catalog[$id]['maximum_quantity']);
            $unit = (float) $catalog[$id]['total_amount'];

            $normalized[] = [
                'id' => $id,
                'type' => $catalog[$id]['type'],
                'quantity' => $quantity,
                'total_amount' => $catalog[$id]['total_amount'],
                'total_currency' => $catalog[$id]['total_currency'],
                'line_total' => number_format($unit * $quantity, 2, '.', ''),
                'label' => $catalog[$id]['label'],
                'designator' => $catalog[$id]['designator'] ?? null,
            ];
        }

        return $normalized;
    }

    private function sumAmounts(string $base, float $extra): string
    {
        return number_format(((float) $base) + $extra, 2, '.', '');
    }

    private function baggageLabel(string $type): string
    {
        return match ($type) {
            'carry_on' => 'Cabin bag',
            'checked' => 'Check-in bag',
            default => Str::of($type)->replace('_', ' ')->title()->toString(),
        };
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
