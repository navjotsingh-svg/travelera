<?php

namespace App\Services\Agent;

use App\Models\User;
use App\Services\Duffel\DuffelException;
use App\Services\Duffel\DuffelFlightService;
use App\Services\PayPal\PayPalPaymentService;
use App\Services\PlatformFeeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FlightAgentService
{
    public function __construct(
        private readonly FlightIntentParser $parser,
        private readonly DuffelFlightService $duffel,
        private readonly PlatformFeeService $fees,
        private readonly PayPalPaymentService $paypal,
    ) {}

    /**
     * @param  array<string, mixed>  $previousIntent
     * @param  list<array<string, mixed>>  $offerPool
     * @return array{
     *     reply: string,
     *     intent: array<string, mixed>,
     *     offers: list<array<string, mixed>>,
     *     session_offers: list<array<string, mixed>>,
     *     offer_pool: list<array<string, mixed>>,
     *     suggestions: list<array{label:string,message:string}>,
     *     selected: ?array<string, mixed>,
     *     clear_session: bool,
     *     can_search: bool
     * }
     */
    public function reply(
        string $message,
        array $previousIntent = [],
        array $offerPool = [],
        ?string $action = null,
        ?string $offerId = null,
        ?User $user = null,
    ): array {
        if (! config('agent.enabled', true)) {
            return $this->plain(
                'The flight agent is temporarily offline. You can still search on the Flights page.',
                $previousIntent,
            );
        }

        if ($action === 'reset' || preg_match('/\b(start\s+over|new\s+search|reset|clear\s+search)\b/i', $message)) {
            return $this->resetReply();
        }

        if (! $this->duffel->configured()) {
            return $this->plain(
                'Live fares are not configured yet. Please try the Flights search page or contact support.',
                $previousIntent,
            );
        }

        if ($action === 'select' && filled($offerId)) {
            $intent = array_merge($previousIntent, [
                'mode' => 'select',
                'selected_offer_id' => $offerId,
                'incomplete' => false,
                'missing' => [],
            ]);

            return $this->selectOffer($intent, $offerPool, $user);
        }

        if ($action === 'refine') {
            $previousIntent['mode'] = 'refine';
        }

        $intent = $this->parser->parse($message, $previousIntent);

        if (($intent['mode'] ?? '') === 'reset') {
            return $this->resetReply();
        }

        if (($intent['mode'] ?? '') === 'select') {
            return $this->selectOffer($intent, $offerPool, $user);
        }

        if ($intent['incomplete'] ?? true) {
            return [
                'reply' => $this->clarifyMessage($intent),
                'intent' => $intent,
                'offers' => [],
                'session_offers' => [],
                'offer_pool' => $offerPool,
                'suggestions' => $this->starterSuggestions(),
                'selected' => null,
                'clear_session' => false,
                'can_search' => false,
            ];
        }

        if (($intent['origin'] ?? null) === ($intent['destination'] ?? null)) {
            return $this->plain(
                'Origin and destination need to be different. Which cities should I search?',
                $intent,
            );
        }

        $pool = collect($offerPool);
        $canReusePool = $pool->isNotEmpty()
            && ($intent['mode'] ?? '') === 'refine'
            && ! ($intent['route_changed'] ?? false);

        if (! $canReusePool) {
            try {
                $result = $this->duffel->search(
                    origin: (string) $intent['origin'],
                    destination: (string) $intent['destination'],
                    departureDate: (string) $intent['departure_date'],
                    returnDate: $intent['return_date'] ?? null,
                    cabinClass: (string) ($intent['cabin'] ?? 'economy'),
                    adults: (int) ($intent['adults'] ?? 1),
                );
            } catch (DuffelException $exception) {
                return $this->plain(
                    'I could not fetch live fares right now: '.$exception->getMessage().' Try again in a moment, or use the Flights page.',
                    $intent,
                );
            }

            $pool = collect($result['offers'] ?? [])->map(fn (array $offer) => $this->toPoolOffer($offer))->values();
        } else {
            $pool = $pool->map(fn (array $offer) => $this->hydratePoolOffer($offer))->values();
        }

        if ($pool->isEmpty()) {
            return $this->plain(
                'No live offers matched '.$intent['origin'].' → '.$intent['destination'].' on '.$intent['departure_date'].'. Try another date or nearby airport.',
                $intent,
            );
        }

        $filtered = $this->filterOffers($pool, $intent);
        if ($filtered->isEmpty()) {
            return [
                'reply' => 'Nothing matched those filters on the current results. Try loosening the budget, allowing stops, or starting a new search.',
                'intent' => $intent,
                'offers' => [],
                'session_offers' => $this->sessionOfferPayload($pool),
                'offer_pool' => $pool->map(fn (array $o) => $this->serializePoolOffer($o))->values()->all(),
                'suggestions' => $this->refineSuggestions($intent, relax: true),
                'selected' => null,
                'clear_session' => false,
                'can_search' => true,
            ];
        }

        $ranked = $this->rank($filtered, (string) ($intent['preference'] ?? 'both'));
        $labeled = $this->labelOffers($ranked, $filtered, (string) ($intent['preference'] ?? 'both'));
        $presented = $labeled
            ->map(fn (array $offer) => $this->presentForChat($offer, $intent, $filtered))
            ->values()
            ->all();

        $preference = (string) ($intent['preference'] ?? 'both');
        $label = match ($preference) {
            'cheapest' => 'cheapest',
            'fastest' => 'fastest',
            default => 'cheapest and fastest',
        };

        $filterBits = $this->describeFilters($intent);
        $verb = $canReusePool ? 'Refined to' : 'Here are';
        $reply = $verb.' the '.$label.' options for '
            .$intent['origin'].' → '.$intent['destination']
            .' on '.$intent['departure_date']
            .($filterBits !== '' ? ' ('.$filterBits.')' : '')
            .'. Pick one to continue — I never charge or auto-pay.';

        return [
            'reply' => $reply,
            'intent' => $intent,
            'offers' => $presented,
            'session_offers' => $this->sessionOfferPayload($pool),
            'offer_pool' => $pool->map(fn (array $o) => $this->serializePoolOffer($o))->values()->all(),
            'suggestions' => $this->refineSuggestions($intent),
            'selected' => null,
            'clear_session' => false,
            'can_search' => true,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $offers
     * @return Collection<int, array<string, mixed>>
     */
    public function rank(Collection $offers, string $preference): Collection
    {
        $limit = max(1, (int) config('agent.max_results', 3));

        $byPrice = $offers->sortBy([
            fn (array $o) => (float) ($o['total_amount'] ?? 0),
            fn (array $o) => (int) ($o['duration_minutes'] ?? PHP_INT_MAX),
            fn (array $o) => (int) ($o['stops'] ?? 99),
        ])->values();

        $bySpeed = $offers->sortBy([
            fn (array $o) => (int) ($o['duration_minutes'] ?? PHP_INT_MAX),
            fn (array $o) => (int) ($o['stops'] ?? 99),
            fn (array $o) => (float) ($o['total_amount'] ?? 0),
        ])->values();

        if ($preference === 'cheapest') {
            return $byPrice->take($limit)->values();
        }

        if ($preference === 'fastest') {
            return $bySpeed->take($limit)->values();
        }

        $picked = collect();
        if ($byPrice->isNotEmpty()) {
            $picked->push($byPrice->first());
        }
        if ($bySpeed->isNotEmpty() && ($bySpeed->first()['id'] ?? null) !== ($picked->first()['id'] ?? null)) {
            $picked->push($bySpeed->first());
        }

        foreach ($byPrice as $offer) {
            if ($picked->count() >= $limit) {
                break;
            }
            if ($picked->contains(fn (array $item) => ($item['id'] ?? null) === ($offer['id'] ?? null))) {
                continue;
            }
            $picked->push($offer);
        }

        return $picked->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $offers
     * @param  array<string, mixed>  $intent
     * @return Collection<int, array<string, mixed>>
     */
    public function filterOffers(Collection $offers, array $intent): Collection
    {
        return $offers
            ->filter(function (array $offer) use ($intent) {
                if (isset($intent['max_stops']) && $intent['max_stops'] !== null) {
                    if ((int) ($offer['stops'] ?? 99) > (int) $intent['max_stops']) {
                        return false;
                    }
                }

                if (isset($intent['max_budget']) && $intent['max_budget'] !== null) {
                    $total = $this->fees->withFee((float) ($offer['total_amount'] ?? 0));
                    if ($total > (float) $intent['max_budget']) {
                        return false;
                    }
                }

                $window = $intent['departure_window'] ?? 'any';
                if ($window !== 'any') {
                    $hour = (int) ($offer['departure_hour'] ?? -1);
                    $ok = match ($window) {
                        'morning' => $hour >= 5 && $hour < 12,
                        'afternoon' => $hour >= 12 && $hour < 17,
                        'evening' => $hour >= 17 || ($hour >= 0 && $hour < 5),
                        default => true,
                    };
                    if (! $ok) {
                        return false;
                    }
                }

                return true;
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $intent
     * @param  list<array<string, mixed>>  $offerPool
     * @return array<string, mixed>
     */
    private function selectOffer(array $intent, array $offerPool, ?User $user): array
    {
        $pool = collect($offerPool)->map(fn (array $offer) => $this->hydratePoolOffer($offer));
        if ($pool->isEmpty()) {
            return $this->plain(
                'I don’t have results to book yet. Tell me a route and date first — then pick an option.',
                $intent,
            );
        }

        $filtered = $this->filterOffers($pool, $intent);
        $ranked = $this->labelOffers(
            $this->rank($filtered->isNotEmpty() ? $filtered : $pool, (string) ($intent['preference'] ?? 'both')),
            $filtered->isNotEmpty() ? $filtered : $pool,
            (string) ($intent['preference'] ?? 'both'),
        );

        $selector = (string) ($intent['selected_offer_id'] ?? '');
        $chosen = null;

        if (str_starts_with($selector, 'index:')) {
            $index = max(1, (int) substr($selector, 6)) - 1;
            $chosen = $ranked->get($index);
        } elseif (str_starts_with($selector, 'badge:')) {
            $badge = substr($selector, 6);
            $chosen = $ranked->first(fn (array $o) => str_contains(Str::lower((string) ($o['badge'] ?? '')), $badge))
                ?? ($badge === 'cheapest'
                    ? $pool->sortBy(fn (array $o) => (float) ($o['total_amount'] ?? 0))->first()
                    : $pool->sortBy(fn (array $o) => (int) ($o['duration_minutes'] ?? PHP_INT_MAX))->first());
        } elseif ($selector !== '') {
            $chosen = $pool->first(fn (array $o) => ($o['id'] ?? null) === $selector)
                ?? $ranked->first(fn (array $o) => ($o['id'] ?? null) === $selector);
        }

        if (! $chosen) {
            $presented = $ranked->map(fn (array $offer) => $this->presentForChat($offer, $intent, $filtered->isNotEmpty() ? $filtered : $pool))->values()->all();

            return [
                'reply' => 'Which option should I open for booking? Tap Continue to book on a card, or say “book the cheapest”.',
                'intent' => $intent,
                'offers' => $presented,
                'session_offers' => $this->sessionOfferPayload($pool),
                'offer_pool' => $pool->map(fn (array $o) => $this->serializePoolOffer($o))->values()->all(),
                'suggestions' => [
                    ['label' => 'Book cheapest', 'message' => 'Book the cheapest'],
                    ['label' => 'Book fastest', 'message' => 'Book the fastest'],
                ],
                'selected' => null,
                'clear_session' => false,
                'can_search' => true,
            ];
        }

        $presented = $this->presentForChat($chosen, $intent, $filtered->isNotEmpty() ? $filtered : $pool);
        $checkout = $this->checkoutForm($presented, $chosen, $user);
        $intent['selected_offer_id'] = $chosen['id'] ?? null;

        $reply = $user
            ? 'I kept this in the chat. Add passenger details below, then choose hold or pay. Nothing is charged until you open PayPal yourself.'
            : 'I can finish this in the chat after you sign in. Passenger details and payment stay here — I never auto-charge.';

        if ($user && $user->savedPassengers()->exists()) {
            $reply .= ' Your saved passengers can be applied with one tap.';
        }

        return [
            'reply' => $reply,
            'intent' => $intent,
            'offers' => [$presented],
            'session_offers' => $this->sessionOfferPayload($pool),
            'offer_pool' => $pool->map(fn (array $o) => $this->serializePoolOffer($o))->values()->all(),
            'suggestions' => [
                ['label' => 'New search', 'message' => 'Start over'],
            ],
            'selected' => [
                'id' => $presented['id'],
                'book_url' => $presented['book_url'],
                'checkout_url' => null,
            ],
            'checkout' => $checkout,
            'clear_session' => false,
            'can_search' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $presented
     * @param  array<string, mixed>  $chosen
     * @return array<string, mixed>
     */
    private function checkoutForm(array $presented, array $chosen, ?User $user): array
    {
        $name = $user?->name ?? '';
        $parts = $name !== '' ? explode(' ', $name, 2) : ['', ''];

        return [
            'offer_id' => $presented['id'],
            'auth_required' => $user === null,
            'login_url' => route('agent.login'),
            'passenger_count' => max(1, (int) ($chosen['passenger_count'] ?? $presented['passenger_count'] ?? 1)),
            'supports_hold' => (bool) ($chosen['supports_hold'] ?? false),
            'paypal_enabled' => $this->paypal->configured(),
            'summary' => [
                'airline' => $presented['airline'] ?? '',
                'flight_number' => $presented['flight_number'] ?? '',
                'origin' => $presented['origin'] ?? '',
                'destination' => $presented['destination'] ?? '',
                'total_amount' => $presented['total_amount'] ?? '',
                'total_currency' => $presented['total_currency'] ?? '',
            ],
            'saved_passengers' => $user
                ? $user->savedPassengers()->get()->map->toFormArray()->values()->all()
                : [],
            'defaults' => [
                'title' => 'mr',
                'given_name' => $parts[0] ?? '',
                'family_name' => $parts[1] ?? '',
                'gender' => 'm',
                'born_on' => '',
                'email' => $user?->email ?? '',
                'phone_number' => $user?->phone ?? '',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resetReply(): array
    {
        return [
            'reply' => 'Cleared. Tell me a new route — for example “Cheapest nonstop DEL to DXB under 500 on 12 Oct”.',
            'intent' => [],
            'offers' => [],
            'session_offers' => [],
            'offer_pool' => [],
            'suggestions' => $this->starterSuggestions(),
            'selected' => null,
            'clear_session' => true,
            'can_search' => false,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $ranked
     * @param  Collection<int, array<string, mixed>>  $all
     * @return Collection<int, array<string, mixed>>
     */
    private function labelOffers(Collection $ranked, Collection $all, string $preference): Collection
    {
        $cheapestId = $all->sortBy(fn (array $o) => (float) ($o['total_amount'] ?? 0))->first()['id'] ?? null;
        $fastestId = $all->sortBy([
            fn (array $o) => (int) ($o['duration_minutes'] ?? PHP_INT_MAX),
            fn (array $o) => (float) ($o['total_amount'] ?? 0),
        ])->first()['id'] ?? null;

        return $ranked->map(function (array $offer) use ($cheapestId, $fastestId, $preference) {
            $badges = [];
            if (($offer['id'] ?? null) === $cheapestId && in_array($preference, ['cheapest', 'both'], true)) {
                $badges[] = 'Cheapest';
            }
            if (($offer['id'] ?? null) === $fastestId && in_array($preference, ['fastest', 'both'], true)) {
                $badges[] = 'Fastest';
            }
            $offer['badge'] = $badges !== [] ? implode(' · ', $badges) : null;

            return $offer;
        });
    }

    /**
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $intent
     * @param  Collection<int, array<string, mixed>>  $set
     * @return array<string, mixed>
     */
    private function presentForChat(array $offer, array $intent = [], ?Collection $set = null): array
    {
        $base = (float) ($offer['total_amount'] ?? 0);
        $currency = (string) ($offer['total_currency'] ?? 'USD');
        $breakdown = $this->fees->breakdown($base);
        $id = $offer['id'] ?? null;
        $bookUrl = route('flights.offer', $id);

        $departureAt = $offer['departure_at'] ?? null;
        $arrivalAt = $offer['arrival_at'] ?? null;
        if (is_string($departureAt)) {
            $departureAt = Carbon::parse($departureAt);
        }
        if (is_string($arrivalAt)) {
            $arrivalAt = Carbon::parse($arrivalAt);
        }

        return [
            'id' => $id,
            'badge' => $offer['badge'] ?? null,
            'why' => $this->whyLine($offer, $intent, $set),
            'airline' => $offer['airline'] ?? 'Airline',
            'airline_logo' => $offer['airline_logo'] ?? null,
            'flight_number' => $offer['flight_number'] ?? null,
            'origin' => $offer['origin'] ?? '',
            'destination' => $offer['destination'] ?? '',
            'departure_label' => $departureAt ? $departureAt->format('D, M j · H:i') : null,
            'arrival_label' => $arrivalAt ? $arrivalAt->format('D, M j · H:i') : null,
            'duration' => $offer['duration'] ?? '',
            'duration_minutes' => $offer['duration_minutes'] ?? null,
            'stops' => (int) ($offer['stops'] ?? 0),
            'cabin_class' => $offer['cabin_class'] ?? 'economy',
            'fare_brand' => $offer['fare_brand'] ?? null,
            'base_amount' => number_format($breakdown['base_amount'], 2, '.', ''),
            'total_amount' => number_format($breakdown['total_amount'], 2, '.', ''),
            'platform_fee_percent' => $breakdown['platform_fee_percent'],
            'total_currency' => $currency,
            'book_url' => $bookUrl,
            'checkout_url' => route('flights.book', $id),
            'search_url' => route('flights.index', array_filter([
                'from' => $offer['origin'] ?? null,
                'to' => $offer['destination'] ?? null,
            ])),
        ];
    }

    /**
     * @param  array<string, mixed>  $offer
     * @param  array<string, mixed>  $intent
     * @param  Collection<int, array<string, mixed>>|null  $set
     */
    private function whyLine(array $offer, array $intent, ?Collection $set): string
    {
        $bits = [];
        $badge = Str::lower((string) ($offer['badge'] ?? ''));
        if (str_contains($badge, 'cheapest')) {
            $bits[] = 'Lowest fare in this set';
        }
        if (str_contains($badge, 'fastest')) {
            $bits[] = 'Shortest journey time';
        }
        if ((int) ($offer['stops'] ?? 0) === 0) {
            $bits[] = 'Non-stop';
        }
        if (isset($intent['max_budget']) && $intent['max_budget'] !== null) {
            $bits[] = 'Within your budget';
        }
        if (($intent['departure_window'] ?? 'any') !== 'any') {
            $bits[] = ucfirst((string) $intent['departure_window']).' departure';
        }

        if ($bits === [] && $set) {
            $bits[] = 'Strong match for your filters';
        }

        return implode(' · ', array_unique($bits));
    }

    /**
     * @param  array<string, mixed>  $offer
     * @return array<string, mixed>
     */
    private function toPoolOffer(array $offer): array
    {
        $departureAt = $offer['departure_at'] ?? null;

        return [
            'id' => $offer['id'] ?? null,
            'airline' => $offer['airline'] ?? null,
            'airline_logo' => $offer['airline_logo'] ?? null,
            'flight_number' => $offer['flight_number'] ?? null,
            'origin' => $offer['origin'] ?? null,
            'destination' => $offer['destination'] ?? null,
            'departure_at' => $departureAt instanceof Carbon ? $departureAt->toIso8601String() : $departureAt,
            'arrival_at' => isset($offer['arrival_at']) && $offer['arrival_at'] instanceof Carbon
                ? $offer['arrival_at']->toIso8601String()
                : ($offer['arrival_at'] ?? null),
            'departure_hour' => $departureAt instanceof Carbon ? (int) $departureAt->format('G') : null,
            'duration' => $offer['duration'] ?? null,
            'duration_minutes' => $offer['duration_minutes'] ?? null,
            'stops' => (int) ($offer['stops'] ?? 0),
            'passenger_count' => (int) ($offer['passenger_count'] ?? 1),
            'cabin_class' => $offer['cabin_class'] ?? null,
            'fare_brand' => $offer['fare_brand'] ?? null,
            'total_amount' => $offer['total_amount'] ?? null,
            'total_currency' => $offer['total_currency'] ?? null,
            'flight_key' => $offer['flight_key'] ?? null,
            'fare_features' => $offer['fare_features'] ?? [],
            'supports_hold' => $offer['supports_hold'] ?? false,
            'carbon_emissions' => $offer['carbon_emissions'] ?? null,
            'badge' => $offer['badge'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $offer
     * @return array<string, mixed>
     */
    private function serializePoolOffer(array $offer): array
    {
        return $this->toPoolOffer($this->hydratePoolOffer($offer));
    }

    /**
     * @param  array<string, mixed>  $offer
     * @return array<string, mixed>
     */
    private function hydratePoolOffer(array $offer): array
    {
        if (isset($offer['departure_at']) && is_string($offer['departure_at'])) {
            $offer['departure_at'] = Carbon::parse($offer['departure_at']);
            $offer['departure_hour'] = (int) $offer['departure_at']->format('G');
        }
        if (isset($offer['arrival_at']) && is_string($offer['arrival_at'])) {
            $offer['arrival_at'] = Carbon::parse($offer['arrival_at']);
        }

        return $offer;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $offers
     * @return list<array<string, mixed>>
     */
    private function sessionOfferPayload(Collection $offers): array
    {
        return $offers
            ->map(fn (array $offer) => [
                'id' => $offer['id'],
                'flight_key' => $offer['flight_key'] ?? null,
                'airline' => $offer['airline'] ?? null,
                'airline_logo' => $offer['airline_logo'] ?? null,
                'flight_number' => $offer['flight_number'] ?? null,
                'cabin_class' => $offer['cabin_class'] ?? null,
                'fare_brand' => $offer['fare_brand'] ?? null,
                'total_amount' => $offer['total_amount'] ?? null,
                'total_currency' => $offer['total_currency'] ?? null,
                'fare_features' => $offer['fare_features'] ?? [],
                'supports_hold' => $offer['supports_hold'] ?? false,
                'carbon_emissions' => $offer['carbon_emissions'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $intent
     */
    private function describeFilters(array $intent): string
    {
        $bits = [];
        if (isset($intent['max_stops']) && $intent['max_stops'] !== null) {
            $bits[] = (int) $intent['max_stops'] === 0 ? 'non-stop' : 'max '.$intent['max_stops'].' stops';
        }
        if (isset($intent['max_budget']) && $intent['max_budget'] !== null) {
            $bits[] = 'under '.$intent['max_budget'];
        }
        if (($intent['departure_window'] ?? 'any') !== 'any') {
            $bits[] = $intent['departure_window'].' departures';
        }

        return implode(', ', $bits);
    }

    /**
     * @return list<array{label:string,message:string}>
     */
    private function starterSuggestions(): array
    {
        return [
            ['label' => 'Cheapest LON → NYC', 'message' => 'Cheapest flight from London to New York next Friday'],
            ['label' => 'Nonstop DEL → DXB', 'message' => 'Cheapest nonstop Delhi to Dubai tomorrow under 500'],
            ['label' => 'Compare BOM → SIN', 'message' => 'Compare cheapest and fastest from Mumbai to Singapore on 2026-11-05'],
        ];
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return list<array{label:string,message:string}>
     */
    private function refineSuggestions(array $intent, bool $relax = false): array
    {
        if ($relax) {
            return [
                ['label' => 'Allow stops', 'message' => 'Allow connecting flights'],
                ['label' => 'Clear budget', 'message' => 'Clear budget'],
                ['label' => 'Any time', 'message' => 'Any time'],
                ['label' => 'New search', 'message' => 'Start over'],
            ];
        }

        $chips = [
            ['label' => 'Non-stop only', 'message' => 'Only nonstop'],
            ['label' => 'Show cheapest', 'message' => 'Show cheapest'],
            ['label' => 'Show fastest', 'message' => 'Show fastest'],
            ['label' => 'Morning flights', 'message' => 'Morning flights only'],
        ];

        if (isset($intent['max_budget']) && $intent['max_budget'] !== null) {
            array_unshift($chips, ['label' => 'Clear budget', 'message' => 'Clear budget']);
        } else {
            array_unshift($chips, ['label' => 'Under 500', 'message' => 'Under 500']);
        }

        $chips[] = ['label' => 'New search', 'message' => 'Start over'];

        return $chips;
    }

    /**
     * @param  array<string, mixed>  $intent
     */
    private function clarifyMessage(array $intent): string
    {
        $missing = $intent['missing'] ?? [];
        $parts = [];

        if (in_array('origin', $missing, true) && in_array('destination', $missing, true)) {
            $parts[] = 'Where are you flying from and to?';
        } elseif (in_array('origin', $missing, true)) {
            $parts[] = 'Which city or airport are you departing from?';
        } elseif (in_array('destination', $missing, true)) {
            $parts[] = 'Which city or airport is your destination?';
        }

        if (in_array('departure_date', $missing, true)) {
            $parts[] = 'What departure date works? (e.g. 2026-10-12 or next Friday)';
        }

        if ($parts === []) {
            $parts[] = 'Tell me the route and date — for example: “Cheapest nonstop Delhi to Dubai under 500 on 12 Oct”.';
        }

        $parts[] = 'I only suggest options and link you to book; payment stays under your control.';

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>  $intent
     * @return array<string, mixed>
     */
    private function plain(string $reply, array $intent): array
    {
        return [
            'reply' => $reply,
            'intent' => $intent,
            'offers' => [],
            'session_offers' => [],
            'offer_pool' => [],
            'suggestions' => $this->starterSuggestions(),
            'selected' => null,
            'clear_session' => false,
            'can_search' => false,
        ];
    }
}
