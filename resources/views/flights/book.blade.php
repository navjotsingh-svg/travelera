<x-public-layout title="Book {{ $flight['airline'] }} {{ $flight['flight_number'] }}">
    @php
        $bagServices = $flight['bag_services'] ?? [];
        $includedBags = $flight['included_baggage'] ?? [];
        $passengersMeta = collect($flight['passengers'] ?? [])->values()->map(fn ($p, $i) => [
            'id' => $p['id'] ?? ('pas_'.$i),
            'label' => 'Passenger '.($i + 1),
        ])->all();
        $nameParts = explode(' ', auth()->user()->name, 2);
    @endphp

    <div
        class="flight-book"
        x-data="flightBookExtras({
            baseAmount: {{ json_encode((float) $flight['total_amount']) }},
            currency: {{ json_encode($flight['total_currency']) }},
            bagServices: {{ Js::from($bagServices) }},
            seatMaps: {{ Js::from($seatMaps) }},
            passengers: {{ Js::from($passengersMeta) }},
            oldServices: {{ Js::from(old('services', [])) }},
        })"
    >
        <div class="flight-book-inner">
            <a href="{{ route('flights.offer', $flight['id']) }}" class="flight-book-back">← Offer details</a>
            <div class="flight-book-grid">
                <div class="flight-book-main">
                    <header class="flight-book-hero">
                        <p class="flight-book-kicker">Complete your booking</p>
                        <h1>Passenger details &amp; add-ons</h1>
                        <p>{{ $flight['airline'] }} {{ $flight['flight_number'] }} · {{ $flight['origin'] }} → {{ $flight['destination'] }}</p>
                    </header>

                    @if ($errors->any())
                        <div class="flight-book-error">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('flights.book.store', $flight['id']) }}" class="flight-book-form" @submit="syncServices()">
                        @csrf

                        <template x-for="(service, index) in selectedServices()" :key="service.id + '-' + index">
                            <div>
                                <input type="hidden" :name="`services[${index}][id]`" :value="service.id">
                                <input type="hidden" :name="`services[${index}][quantity]`" :value="service.quantity">
                            </div>
                        </template>

                        @foreach ($flight['passengers'] as $index => $passenger)
                            <fieldset class="flight-book-card">
                                <legend>Passenger {{ $index + 1 }}</legend>
                                <div class="flight-book-fields">
                                    <label>
                                        <span>Title</span>
                                        <select name="passengers[{{ $index }}][title]" required>
                                            @foreach (['mr' => 'Mr', 'ms' => 'Ms', 'mrs' => 'Mrs', 'miss' => 'Miss', 'dr' => 'Dr'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old("passengers.$index.title", 'mr') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label>
                                        <span>Gender</span>
                                        <select name="passengers[{{ $index }}][gender]" required>
                                            <option value="m" @selected(old("passengers.$index.gender", 'm') === 'm')>Male</option>
                                            <option value="f" @selected(old("passengers.$index.gender") === 'f')>Female</option>
                                        </select>
                                    </label>
                                    <label>
                                        <span>Given name</span>
                                        <input name="passengers[{{ $index }}][given_name]" value="{{ old("passengers.$index.given_name", $index === 0 ? ($nameParts[0] ?? '') : '') }}" required>
                                    </label>
                                    <label>
                                        <span>Family name</span>
                                        <input name="passengers[{{ $index }}][family_name]" value="{{ old("passengers.$index.family_name", $index === 0 ? ($nameParts[1] ?? 'Traveler') : '') }}" required>
                                    </label>
                                    <label>
                                        <span>Date of birth</span>
                                        <input type="date" name="passengers[{{ $index }}][born_on]" value="{{ old("passengers.$index.born_on") }}" max="{{ now()->subYears(12)->toDateString() }}" required>
                                    </label>
                                    <label>
                                        <span>Email</span>
                                        <input type="email" name="passengers[{{ $index }}][email]" value="{{ old("passengers.$index.email", auth()->user()->email) }}" required>
                                    </label>
                                    <label class="flight-book-span-2">
                                        <span>Phone (with country code)</span>
                                        <input name="passengers[{{ $index }}][phone_number]" value="{{ old("passengers.$index.phone_number", auth()->user()->phone ? '+91'.auth()->user()->phone : '+919876543210') }}" placeholder="+919876543210" required>
                                    </label>
                                </div>
                            </fieldset>
                        @endforeach

                        <section class="flight-book-card flight-book-addons">
                            <div class="flight-book-card-head">
                                <div>
                                    <h2>Baggage</h2>
                                    <p>Included allowance and extra bags from Duffel</p>
                                </div>
                                @if (count($bagServices))
                                    <button type="button" class="flight-book-sheet-btn" @click="bagsOpen = true">Add baggage</button>
                                @endif
                            </div>

                            <div class="flight-bag-grid">
                                @forelse ($includedBags as $bag)
                                    <div class="flight-bag-chip is-included">
                                        <span class="flight-bag-icon" aria-hidden="true">{{ ($bag['type'] ?? '') === 'carry_on' ? '🎒' : '🧳' }}</span>
                                        <div>
                                            <p class="flight-bag-title">{{ $bag['label'] }}</p>
                                            <p class="flight-bag-meta">{{ $bag['quantity'] }} included</p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="flight-bag-chip is-muted">
                                        <span class="flight-bag-icon" aria-hidden="true">🧳</span>
                                        <div>
                                            <p class="flight-bag-title">No free check-in bag</p>
                                            <p class="flight-bag-meta">Add baggage if the airline offers it</p>
                                        </div>
                                    </div>
                                @endforelse

                                <template x-for="bag in selectedBags()" :key="bag.id">
                                    <div class="flight-bag-chip is-extra">
                                        <span class="flight-bag-icon" aria-hidden="true">➕</span>
                                        <div>
                                            <p class="flight-bag-title" x-text="bag.label"></p>
                                            <p class="flight-bag-meta">
                                                <span x-text="bag.quantity + ' × ' + formatMoney(bag.amount)"></span>
                                            </p>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            @unless (count($bagServices))
                                <p class="flight-book-empty-note">Extra baggage is not offered on this fare right now.</p>
                            @endunless
                        </section>

                        <section class="flight-book-card flight-book-addons">
                            <div class="flight-book-card-head">
                                <div>
                                    <h2>Seats</h2>
                                    <p>Pick your seat from the airline seat map</p>
                                </div>
                                @if (count($seatMaps))
                                    <button type="button" class="flight-book-sheet-btn" @click="openSeats()">Select seats</button>
                                @endif
                            </div>

                            <div class="flight-seat-summary" x-show="selectedSeats().length" x-cloak>
                                <template x-for="seat in selectedSeats()" :key="seat.id">
                                    <div class="flight-seat-chip">
                                        <strong x-text="seat.designator"></strong>
                                        <span x-text="seat.passengerLabel"></span>
                                        <span x-text="formatMoney(seat.amount)"></span>
                                    </div>
                                </template>
                            </div>

                            <p class="flight-book-empty-note" x-show="!selectedSeats().length">
                                @if (count($seatMaps))
                                    No seats selected yet — aisle or window seats may be free or paid.
                                @else
                                    Seat map is unavailable for this offer.
                                @endif
                            </p>
                        </section>

                        <button type="submit" class="flight-book-submit">
                            {{ ! empty($stripeEnabled) ? 'Pay securely with Stripe' : 'Confirm booking' }}
                            · <span x-text="formatMoney(grandTotal())"></span>
                        </button>
                        @if (! empty($stripeEnabled))
                            <p class="mt-3 text-center text-xs text-slate-500">You’ll complete card payment on Stripe’s secure checkout, then we confirm the airline ticket.</p>
                        @endif
                    </form>
                </div>

                <aside class="flight-book-side">
                    <div class="flight-fare-card">
                        <p class="flight-fare-kicker">Fare summary</p>
                        <h2>{{ $flight['origin'] }} → {{ $flight['destination'] }}</h2>
                        <p class="flight-fare-meta">{{ $flight['airline'] }} · {{ optional($flight['departure_at'])->format('D, d M · H:i') }}</p>

                        <dl class="flight-fare-lines">
                            <div>
                                <dt>Base fare</dt>
                                <dd><x-money :amount="$flight['total_amount']" :currency="$flight['total_currency']" /></dd>
                            </div>
                            <div x-show="extrasTotal() > 0" x-cloak>
                                <dt>Seats &amp; bags</dt>
                                <dd x-text="formatMoney(extrasTotal())"></dd>
                            </div>
                            <div class="is-total">
                                <dt>Total</dt>
                                <dd x-text="formatMoney(grandTotal())"></dd>
                            </div>
                        </dl>

                        @if (count($includedBags))
                            <div class="flight-fare-bags">
                                <p>Included</p>
                                <ul>
                                    @foreach ($includedBags as $bag)
                                        <li>{{ $bag['quantity'] }}× {{ $bag['label'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </aside>
            </div>
        </div>

        <div class="flight-sheet" x-show="bagsOpen" x-cloak>
            <div class="flight-sheet-backdrop" @click="bagsOpen = false"></div>
            <div class="flight-sheet-panel" @click.stop>
                <div class="flight-sheet-handle"></div>
                <div class="flight-sheet-head">
                    <div>
                        <h3>Add baggage</h3>
                        <p>Prices from Duffel for this offer</p>
                    </div>
                    <button type="button" class="flight-sheet-close" @click="bagsOpen = false">Done</button>
                </div>
                <div class="flight-sheet-body">
                    <template x-for="service in bagServices" :key="service.id">
                        <div class="flight-bag-option">
                            <div class="flight-bag-option-info">
                                <p class="flight-bag-title" x-text="service.description"></p>
                                <p class="flight-bag-meta" x-text="formatMoney(parseFloat(service.total_amount))"></p>
                            </div>
                            <div class="flight-qty">
                                <button type="button" @click="changeBag(service.id, -1)" :disabled="(bagQty[service.id] || 0) <= 0">−</button>
                                <span x-text="bagQty[service.id] || 0"></span>
                                <button type="button" @click="changeBag(service.id, 1)" :disabled="(bagQty[service.id] || 0) >= service.maximum_quantity">+</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="flight-sheet flight-sheet-seats" x-show="seatsOpen" x-cloak>
            <div class="flight-sheet-backdrop" @click="seatsOpen = false"></div>
            <div class="flight-sheet-panel is-wide" @click.stop>
                <div class="flight-sheet-handle"></div>
                <div class="flight-sheet-head">
                    <div>
                        <h3>Select seats</h3>
                        <p x-text="activePassengerLabel()"></p>
                    </div>
                    <button type="button" class="flight-sheet-close" @click="seatsOpen = false">Done</button>
                </div>

                <div class="flight-seat-toolbar" x-show="passengers.length > 1">
                    <template x-for="(passenger, index) in passengers" :key="passenger.id">
                        <button
                            type="button"
                            class="flight-seat-pax"
                            :class="{ 'is-active': activePassengerIndex === index }"
                            @click="activePassengerIndex = index"
                            x-text="passenger.label"
                        ></button>
                    </template>
                </div>

                <div class="flight-seat-toolbar" x-show="seatMaps.length > 1">
                    <template x-for="(map, index) in seatMaps" :key="map.id || index">
                        <button
                            type="button"
                            class="flight-seat-leg"
                            :class="{ 'is-active': activeMapIndex === index }"
                            @click="activeMapIndex = index"
                            x-text="'Flight ' + (index + 1)"
                        ></button>
                    </template>
                </div>

                <div class="flight-sheet-body flight-seat-map-wrap">
                    <template x-if="activeMap()">
                        <div class="flight-seat-map">
                            <div class="flight-seat-nose">Front of aircraft</div>
                            <template x-for="(cabin, cabinIndex) in activeMap().cabins" :key="cabinIndex">
                                <div class="flight-seat-cabin">
                                    <p class="flight-seat-cabin-label" x-text="(cabin.cabin_class || 'economy').replace('_', ' ')"></p>
                                    <template x-for="(row, rowIndex) in cabin.rows" :key="rowIndex">
                                        <div class="flight-seat-row">
                                            <template x-for="(el, elIndex) in row.elements" :key="elIndex">
                                                <button
                                                    type="button"
                                                    class="flight-seat-cell"
                                                    :class="seatClass(el)"
                                                    :disabled="!el.available || !serviceForPassenger(el)"
                                                    @click="pickSeat(el)"
                                                    :title="el.designator || el.type"
                                                >
                                                    <span x-text="el.type === 'seat' ? (el.designator || '·') : (el.type === 'aisle' ? '' : '·')"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <div class="flight-seat-legend">
                                <span><i class="is-free"></i> Available</span>
                                <span><i class="is-selected"></i> Selected</span>
                                <span><i class="is-taken"></i> Unavailable</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-public-layout>
