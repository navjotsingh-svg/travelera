<x-public-layout title="Flights">
    <section class="flight-listing-hero">
        <div class="flight-listing-hero-inner">
            <p class="flight-listing-kicker">{{ $live ? 'Live fares via Duffel' : 'Sample flights' }}</p>
            <h1 class="flight-listing-title">Search flights</h1>
            <p class="flight-listing-lead">Compare times, stops and fares — then book in a few taps.</p>
            <div class="flight-listing-search-wrap">
                <x-flight-search-form />
            </div>
        </div>
    </section>

    <div class="flight-listing">
        <div class="flight-listing-inner">
            @if (! $live)
                <div class="flight-listing-note flight-listing-note-warn">
                    Add a Duffel test token as <code>DUFFEL_ACCESS_TOKEN</code> in <code>.env</code> to search live airline offers. Sample flights are shown until then.
                </div>
            @endif

            @if ($error)
                <div class="flight-listing-note flight-listing-note-error">{{ $error }}</div>
            @endif

            @if ($live)
                @if ($searched)
                    <div class="flight-listing-head">
                        <h2>{{ $offers->count() }} offers found</h2>
                        <p>Live fares for your search</p>
                    </div>
                    <div class="flight-list">
                        @forelse ($offers as $offer)
                            <article class="flight-card">
                                <div class="flight-card-top">
                                    <div class="flight-card-airline-mark">{{ strtoupper(substr($offer['airline'], 0, 1)) }}</div>
                                    <div>
                                        <p class="flight-card-airline">{{ $offer['airline'] }} · {{ $offer['flight_number'] }}</p>
                                        <p class="flight-card-meta">{{ $offer['cabin_class'] }} · {{ $offer['stops'] === 0 ? 'Non-stop' : $offer['stops'].' stop'.($offer['stops'] > 1 ? 's' : '') }}{{ $offer['is_return'] ? ' · Return' : '' }}</p>
                                    </div>
                                </div>

                                <div class="flight-card-route">
                                    <div class="flight-card-point">
                                        <p class="flight-card-time">{{ optional($offer['departure_at'])->format('H:i') }}</p>
                                        <p class="flight-card-code">{{ $offer['origin'] }}</p>
                                    </div>
                                    <div class="flight-card-mid">
                                        <p>{{ $offer['duration'] }}</p>
                                        <div class="flight-card-line" aria-hidden="true"></div>
                                        <p>{{ optional($offer['departure_at'])->format('D, d M') }}</p>
                                    </div>
                                    <div class="flight-card-point flight-card-point-end">
                                        <p class="flight-card-time">{{ optional($offer['arrival_at'])->format('H:i') }}</p>
                                        <p class="flight-card-code">{{ $offer['destination'] }}</p>
                                    </div>
                                </div>

                                <div class="flight-card-footer">
                                    <div>
                                        <p class="flight-card-price-label">From</p>
                                        <p class="flight-card-price"><x-money :amount="$offer['total_amount']" :currency="$offer['total_currency']" /></p>
                                    </div>
                                    <a href="{{ route('flights.offer', $offer['id']) }}" class="flight-card-cta">View & book</a>
                                </div>
                            </article>
                        @empty
                            <div class="flight-listing-empty">No Duffel offers for that search. Try another date or airport.</div>
                        @endforelse
                    </div>
                @else
                    <div class="flight-listing-empty">Choose origin, destination and date to search live airline inventory.</div>
                @endif
            @else
                <div class="flight-listing-head">
                    <h2>{{ $flights->total() }} flights found</h2>
                    <p>Sample inventory for browsing</p>
                </div>
                <div class="flight-list">
                    @forelse ($flights as $flight)
                        <article class="flight-card">
                            <div class="flight-card-top">
                                <img src="{{ $flight->image }}" alt="" class="flight-card-thumb">
                                <div>
                                    <p class="flight-card-airline">{{ $flight->airline }} · {{ $flight->flight_number }}</p>
                                    <p class="flight-card-meta">{{ $flight->cabin_class }} · {{ $flight->seats_available }} seats left</p>
                                </div>
                            </div>

                            <div class="flight-card-route">
                                <div class="flight-card-point">
                                    <p class="flight-card-time">{{ $flight->departure_at->format('H:i') }}</p>
                                    <p class="flight-card-code">{{ $flight->origin_code }}</p>
                                    <p class="flight-card-city">{{ $flight->origin }}</p>
                                </div>
                                <div class="flight-card-mid">
                                    <p>{{ $flight->durationLabel() }}</p>
                                    <div class="flight-card-line" aria-hidden="true"></div>
                                    <p>{{ $flight->departure_at->format('D, d M') }}</p>
                                </div>
                                <div class="flight-card-point flight-card-point-end">
                                    <p class="flight-card-time">{{ $flight->arrival_at->format('H:i') }}</p>
                                    <p class="flight-card-code">{{ $flight->destination_code }}</p>
                                    <p class="flight-card-city">{{ $flight->destination }}</p>
                                </div>
                            </div>

                            <div class="flight-card-footer">
                                <div>
                                    <p class="flight-card-price-label">From</p>
                                    <p class="flight-card-price"><x-money :amount="$flight->price" /></p>
                                </div>
                                <a href="{{ route('flights.show', $flight) }}" class="flight-card-cta">View & book</a>
                            </div>
                        </article>
                    @empty
                        <div class="flight-listing-empty">No flights match that search. Try another city or date.</div>
                    @endforelse
                </div>
                <div class="mt-8">{{ $flights->links() }}</div>
            @endif
        </div>
    </div>
</x-public-layout>
