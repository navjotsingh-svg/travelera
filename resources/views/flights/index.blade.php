<x-public-layout title="Flights">
    <div class="flight-listing-hero bg-slate-950 py-8 text-white sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-teal-300 sm:text-sm">{{ $live ? 'Live fares via Duffel' : 'Sample flights' }}</p>
            <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">Search flights</h1>
            <x-flight-search-form class="mt-5 sm:mt-6" />
        </div>
    </div>

    <div class="flight-listing mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
        @if (! $live)
            <div class="mb-5 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900">
                Add a Duffel test token as <code class="font-semibold">DUFFEL_ACCESS_TOKEN</code> in <code>.env</code> to search live airline offers. Sample flights are shown until then.
            </div>
        @endif

        @if ($error)
            <div class="mb-5 rounded-2xl bg-red-50 p-4 text-sm text-red-700">{{ $error }}</div>
        @endif

        @if ($live)
            @if ($searched)
                <p class="mb-4 text-sm text-slate-500">{{ $offers->count() }} Duffel offers found</p>
                <div class="flight-list space-y-3 sm:space-y-4">
                    @forelse ($offers as $offer)
                        <article class="flight-card">
                            <div class="flight-card-top">
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
                                    <div class="flight-card-line"></div>
                                    <p>{{ optional($offer['departure_at'])->format('D, d M') }}</p>
                                </div>
                                <div class="flight-card-point flight-card-point-end">
                                    <p class="flight-card-time">{{ optional($offer['arrival_at'])->format('H:i') }}</p>
                                    <p class="flight-card-code">{{ $offer['destination'] }}</p>
                                </div>
                            </div>

                            <div class="flight-card-footer">
                                <p class="flight-card-price"><x-money :amount="$offer['total_amount']" :currency="$offer['total_currency']" /></p>
                                <a href="{{ route('flights.offer', $offer['id']) }}" class="flight-card-cta">View & book</a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-3xl bg-white p-8 text-center text-slate-500 sm:p-10">No Duffel offers for that search. Try another date or airport.</div>
                    @endforelse
                </div>
            @else
                <div class="rounded-3xl bg-white p-8 text-center text-slate-500 sm:p-10">Choose origin, destination and date to search live airline inventory.</div>
            @endif
        @else
            <p class="mb-4 text-sm text-slate-500">{{ $flights->total() }} sample flights found</p>
            <div class="flight-list space-y-3 sm:space-y-4">
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
                                <div class="flight-card-line"></div>
                                <p>{{ $flight->departure_at->format('D, d M') }}</p>
                            </div>
                            <div class="flight-card-point flight-card-point-end">
                                <p class="flight-card-time">{{ $flight->arrival_at->format('H:i') }}</p>
                                <p class="flight-card-code">{{ $flight->destination_code }}</p>
                                <p class="flight-card-city">{{ $flight->destination }}</p>
                            </div>
                        </div>

                        <div class="flight-card-footer">
                            <p class="flight-card-price"><x-money :amount="$flight->price" /></p>
                            <a href="{{ route('flights.show', $flight) }}" class="flight-card-cta">View & book</a>
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl bg-white p-8 text-center text-slate-500 sm:p-10">No flights match that search. Try another city or date.</div>
                @endforelse
            </div>
            <div class="mt-8">{{ $flights->links() }}</div>
        @endif
    </div>
</x-public-layout>
