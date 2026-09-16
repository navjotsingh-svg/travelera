<x-public-layout :title="'Fare options · '.$flight['origin'].' to '.$flight['destination']">
    @php
        $selectedId = $flight['id'];
        $supportsHold = (bool) ($flight['supports_hold'] ?? false);
    @endphp

    <div class="duffel-fare" x-data="{ selected: '{{ $selectedId }}' }">
        <div class="duffel-fare-inner">
            <nav class="duffel-crumbs">
                <a href="{{ route('flights.index') }}">Flights</a>
                <span>›</span>
                <a href="{{ route('flights.index', request()->only(['from','to','date','return_date','cabin','adults'])) }}">{{ $flight['origin'] }} to {{ $flight['destination'] }}</a>
                <span>›</span>
                <strong>Fare options</strong>
            </nav>

            <h1 class="duffel-fare-title">
                Flight to {{ $flight['destination'] }}
                @if ($flight['departure_at'])
                    {{ $flight['departure_at']->format('d M Y') }}
                @endif
            </h1>

            <div class="duffel-flight-strip">
                @if (! empty($flight['airline_logo']))
                    <img src="{{ $flight['airline_logo'] }}" alt="" class="duffel-airline-logo">
                @else
                    <span class="duffel-airline-mark">{{ strtoupper(substr($flight['airline'], 0, 1)) }}</span>
                @endif
                <div class="duffel-flight-times">
                    <div>
                        <strong>{{ optional($flight['departure_at'])->format('H:i') }}</strong>
                        <span>{{ $flight['origin'] }}</span>
                    </div>
                    <div class="duffel-flight-mid">
                        <span>{{ $flight['duration'] }}</span>
                        <div class="duffel-flight-line" aria-hidden="true"></div>
                        <span>{{ $flight['stops'] === 0 ? 'Direct' : $flight['stops'].' stop'.($flight['stops'] > 1 ? 's' : '') }}</span>
                    </div>
                    <div class="duffel-flight-end">
                        <strong>{{ optional($flight['arrival_at'])->format('H:i') }}</strong>
                        <span>{{ $flight['destination'] }}</span>
                    </div>
                </div>
                <p class="duffel-flight-meta">{{ $flight['airline'] }} · {{ $flight['flight_number'] }}</p>
            </div>

            <div class="duffel-fare-layout">
                <div class="duffel-fare-cards">
                    @foreach ($fareOptions as $option)
                        <article
                            class="duffel-fare-card"
                            :class="{ 'is-selected': selected === '{{ $option['id'] }}' }"
                            @click="selected = '{{ $option['id'] }}'"
                        >
                            <p class="duffel-fare-cabin">{{ strtoupper(str_replace('_', ' ', (string) ($option['cabin_class'] ?? 'economy'))) }}</p>
                            <h2>{{ $option['fare_brand'] ?? 'Standard' }}</h2>
                            <ul class="duffel-fare-features">
                                @foreach (($option['fare_features'] ?? []) as $feature)
                                    <li data-type="{{ $feature['type'] ?? '' }}">{{ $feature['label'] ?? '' }}</li>
                                @endforeach
                            </ul>
                            <div class="duffel-fare-price">
                                <span>total amount from</span>
                                <strong><x-money :amount="$option['total_amount']" :currency="$option['total_currency']" /></strong>
                            </div>
                        </article>
                    @endforeach
                </div>

                <aside class="duffel-summary">
                    <h2>Summary</h2>
                    <div class="duffel-summary-seller">
                        <span>Sold by</span>
                        @if (! empty($flight['airline_logo']))
                            <img src="{{ $flight['airline_logo'] }}" alt="">
                        @endif
                        <strong>{{ $flight['airline'] }}</strong>
                    </div>
                    @if (! empty($flight['carbon_emissions']) && is_numeric($flight['carbon_emissions']))
                        @php
                            $co2 = (float) $flight['carbon_emissions'];
                            $co2Kg = $co2 < 50 ? (int) round($co2 * 1000) : (int) round($co2);
                        @endphp
                        <p class="duffel-summary-co2">From {{ $co2Kg }}kg CO₂</p>
                    @endif

                    <template x-for="option in {{ Js::from($fareOptions->values()) }}" :key="option.id">
                        <div x-show="selected === option.id" x-cloak>
                            <p class="duffel-summary-total">
                                <span x-text="option.fare_brand || 'Fare'"></span>
                                <strong x-text="new Intl.NumberFormat(undefined, { style: 'currency', currency: option.total_currency || 'USD' }).format(Number(option.total_amount || 0))"></strong>
                            </p>
                            <a
                                class="duffel-summary-cta"
                                :href="`{{ url('/flights/offers') }}/${option.id}/book`"
                            >
                                Go to checkout →
                            </a>
                        </div>
                    </template>
                    <p class="duffel-summary-hint">Select a fare brand, then continue to passenger details.</p>
                </aside>
            </div>
        </div>
    </div>
</x-public-layout>
