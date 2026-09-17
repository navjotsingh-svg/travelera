<x-public-layout title="Checkout · {{ $flight['origin'] }} → {{ $flight['destination'] }}">
    @php
        $bagServices = $flight['bag_services'] ?? [];
        $includedBags = $flight['included_baggage'] ?? [];
        $passengersMeta = collect($flight['passengers'] ?? [])->values()->map(fn ($p, $i) => [
            'id' => $p['id'] ?? ('pas_'.$i),
            'label' => 'Adult '.($i + 1),
        ])->all();
        $nameParts = explode(' ', auth()->user()->name, 2);
        $firstSlice = ($flight['slices'] ?? [])[0] ?? null;
        $firstSegment = ($firstSlice['segments'] ?? [])[0] ?? null;
        $supportsHold = (bool) ($flight['supports_hold'] ?? false);
        $paymentDefault = old('payment_choice', 'pay_now');
    @endphp

    <div
        class="duffel-checkout"
        x-data="flightBookExtras({
            baseAmount: {{ json_encode((float) $flight['total_amount']) }},
            currency: {{ json_encode($flight['total_currency']) }},
            bagServices: {{ Js::from($bagServices) }},
            seatMaps: {{ Js::from($seatMaps) }},
            passengers: {{ Js::from($passengersMeta) }},
            oldServices: {{ Js::from(old('services', [])) }},
            paymentChoice: {{ json_encode($paymentDefault) }},
            supportsHold: {{ $supportsHold ? 'true' : 'false' }},
            platformFeePercent: {{ json_encode((float) ($platformFeePercent ?? 0)) }},
            paypalEnabled: {{ ! empty($paypalEnabled) ? 'true' : 'false' }},
            savedPassengers: {{ Js::from($savedPassengers ?? []) }},
            oldPassengers: {{ Js::from(old('passengers', [])) }},
            defaultPassenger: {{ Js::from([
                'title' => 'mr',
                'given_name' => $nameParts[0] ?? '',
                'family_name' => $nameParts[1] ?? '',
                'gender' => 'm',
                'born_on' => '',
                'email' => auth()->user()->email,
                'phone_number' => $defaultPhone ?? '',
                'passport_country' => '',
                'passport_number' => '',
                'passport_expiry' => '',
            ]) }},
            passengerSlots: {{ json_encode(count($flight['passengers'] ?? [])) }},
        })"
    >
        <div class="duffel-checkout-inner">
            <nav class="duffel-crumbs">
                <a href="{{ route('flights.index') }}">Flights</a>
                <span>›</span>
                <a href="{{ route('flights.offer', $flight['id']) }}">Fare options</a>
                <span>›</span>
                <strong>Checkout</strong>
            </nav>

            <div class="duffel-badges">
                <span>{{ ! empty($flight['is_return']) ? 'Return' : 'One way' }}</span>
                <span>{{ optional($flight['departure_at'])->format('D, d M Y') }}</span>
                <span>{{ $flight['passenger_count'] }} Passenger{{ $flight['passenger_count'] > 1 ? 's' : '' }}</span>
                <span>{{ ucfirst(str_replace('_', ' ', (string) $flight['cabin_class'])) }}</span>
            </div>

            <h1 class="duffel-checkout-title">{{ $flight['origin'] }} → {{ $flight['destination'] }}</h1>
            <p class="duffel-checkout-expire">
                This offer will expire on {{ optional($flight['expires_at'])->format('d/m/Y, H:i') ?? 'soon' }}.
            </p>

            @if ($errors->any())
                <div class="flight-book-error">{{ $errors->first() }}</div>
            @endif

            <section class="duffel-selected">
                <h2>Selected flights</h2>
                <div class="duffel-selected-head">
                    @if (! empty($flight['airline_logo']))
                        <img src="{{ $flight['airline_logo'] }}" alt="" class="duffel-airline-logo">
                    @else
                        <span class="duffel-airline-mark">{{ strtoupper(substr($flight['airline'], 0, 1)) }}</span>
                    @endif
                    <div>
                        <p class="duffel-selected-when">
                            {{ optional($flight['departure_at'])->format('D, d M Y H:i') }}
                            –
                            {{ optional($flight['arrival_at'])->format('H:i') }}
                        </p>
                        <p class="duffel-selected-meta">
                            {{ $flight['fare_brand'] ?? ucfirst(str_replace('_', ' ', (string) $flight['cabin_class'])) }}
                            · {{ $flight['airline'] }}
                        </p>
                    </div>
                    <div class="duffel-selected-right">
                        <strong>{{ $flight['duration'] }}</strong>
                        <span>{{ $flight['origin'] }} – {{ $flight['destination'] }}</span>
                        <span>{{ $flight['stops'] === 0 ? 'Non-stop' : $flight['stops'].' stop'.($flight['stops'] > 1 ? 's' : '') }}</span>
                    </div>
                </div>

                @if ($firstSegment)
                    <div class="duffel-timeline">
                        <div class="duffel-timeline-item">
                            <span class="duffel-timeline-dot"></span>
                            <p>
                                <strong>{{ optional($firstSegment['departure_at'])->format('D, d M Y, H:i') }}</strong>
                                Depart from {{ $firstSegment['origin_name'] ?: $firstSegment['origin_city'] ?: '' }}
                                ({{ $firstSegment['origin'] }})
                            </p>
                        </div>
                        <div class="duffel-timeline-mid">
                            <span class="duffel-timeline-line"></span>
                            <p>Flight duration: {{ $firstSegment['duration'] ?: $flight['duration'] }}</p>
                        </div>
                        <div class="duffel-timeline-item">
                            <span class="duffel-timeline-dot"></span>
                            <p>
                                <strong>{{ optional($firstSegment['arrival_at'])->format('D, d M Y, H:i') }}</strong>
                                Arrive at {{ $firstSegment['destination_name'] ?: $firstSegment['destination_city'] ?: '' }}
                                ({{ $firstSegment['destination'] }})
                            </p>
                        </div>
                    </div>

                    <div class="duffel-selected-foot">
                        <span>{{ ucfirst(str_replace('_', ' ', (string) ($firstSegment['passengers'][0]['cabin_class'] ?? $flight['cabin_class']))) }}</span>
                        <span>{{ $firstSegment['airline'] ?: $flight['airline'] }}</span>
                        @if (! empty($firstSegment['aircraft']))
                            <span>{{ $firstSegment['aircraft'] }}</span>
                        @endif
                        <span>{{ $firstSegment['flight_number'] ?: $flight['flight_number'] }}</span>
                        @foreach ($includedBags as $bag)
                            <span class="duffel-bag-pill">{{ $bag['quantity'] }}× {{ $bag['label'] }}</span>
                        @endforeach
                    </div>
                @endif
            </section>

            <form method="POST" action="{{ route('flights.book.store', $flight['id']) }}" class="duffel-checkout-form" @submit="syncServices()">
                @csrf

                <template x-for="(service, index) in selectedServices()" :key="service.id + '-' + index">
                    <div>
                        <input type="hidden" :name="`services[${index}][id]`" :value="service.id">
                        <input type="hidden" :name="`services[${index}][quantity]`" :value="service.quantity">
                    </div>
                </template>
                <input type="hidden" name="payment_choice" :value="paymentChoice">

                <section class="duffel-pay-choice">
                    <h2>Paying now, or later?</h2>
                    <p>Pay now to confirm seats and bags, or hold this fare if the airline allows it.</p>
                    <div class="duffel-pay-grid">
                        <button
                            type="button"
                            class="duffel-pay-card"
                            :class="{ 'is-selected': paymentChoice === 'pay_now' }"
                            @click="paymentChoice = 'pay_now'"
                        >
                            <span class="duffel-pay-check" aria-hidden="true"></span>
                            <span>
                                <strong>Pay now</strong>
                                <small>Pay now and confirm seat and baggage selection</small>
                            </span>
                        </button>
                        <button
                            type="button"
                            class="duffel-pay-card"
                            :class="{ 'is-selected': paymentChoice === 'hold', 'is-disabled': !supportsHold }"
                            @click="supportsHold && (paymentChoice = 'hold')"
                            :disabled="!supportsHold"
                        >
                            <span class="duffel-pay-check" aria-hidden="true"></span>
                            <span>
                                <strong>Hold order</strong>
                                <small>{{ $supportsHold ? 'Hold space on this trip and pay later' : 'Hold is not available on this fare' }}</small>
                            </span>
                        </button>
                    </div>
                </section>

                <section class="duffel-passengers">
                    <h2>Passengers</h2>
                    <p class="duffel-pax-hint">Select a saved traveller or add someone new — just like your travel wallet.</p>

                    <template x-for="(form, index) in passengerForms" :key="'pax-' + index">
                        <div class="duffel-passenger-block">
                            <div class="duffel-pax-head">
                                <span class="duffel-pax-badge" x-text="'Adult ' + (index + 1)"></span>
                            </div>

                            <div class="duffel-saved-pax" x-show="savedPassengers.length" x-cloak>
                                <p class="duffel-saved-label">Choose traveller</p>
                                <div class="duffel-saved-grid">
                                    <button
                                        type="button"
                                        class="duffel-saved-chip is-new"
                                        :class="{ 'is-active': form.selectedId === 'new' }"
                                        @click="selectSavedPassenger(index, 'new')"
                                    >
                                        <span class="duffel-saved-plus">+</span>
                                        <span>Add new</span>
                                    </button>
                                    <template x-for="saved in savedPassengers" :key="'saved-' + index + '-' + saved.id">
                                        <button
                                            type="button"
                                            class="duffel-saved-chip"
                                            :class="{ 'is-active': String(form.selectedId) === String(saved.id) }"
                                            @click="selectSavedPassenger(index, saved.id)"
                                        >
                                            <strong x-text="saved.label"></strong>
                                            <small x-text="saved.born_on || 'Saved traveller'"></small>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <h3>Personal details</h3>
                            <div class="duffel-fields duffel-fields-3">
                                <label>
                                    <span>Title *</span>
                                    <select :name="`passengers[${index}][title]`" x-model="form.title" required>
                                        <option value="mr">Mr</option>
                                        <option value="ms">Ms</option>
                                        <option value="mrs">Mrs</option>
                                        <option value="miss">Miss</option>
                                        <option value="dr">Dr</option>
                                    </select>
                                </label>
                                <label>
                                    <span>Given name *</span>
                                    <input :name="`passengers[${index}][given_name]`" x-model="form.given_name" required>
                                </label>
                                <label>
                                    <span>Family name *</span>
                                    <input :name="`passengers[${index}][family_name]`" x-model="form.family_name" required>
                                </label>
                                <label>
                                    <span>Date of birth *</span>
                                    <input type="date" :name="`passengers[${index}][born_on]`" x-model="form.born_on" max="{{ now()->subYears(12)->toDateString() }}" required>
                                </label>
                                <label>
                                    <span>Gender *</span>
                                    <select :name="`passengers[${index}][gender]`" x-model="form.gender" required>
                                        <option value="m">Male</option>
                                        <option value="f">Female</option>
                                    </select>
                                </label>
                                <label>
                                    <span>Email *</span>
                                    <input type="email" :name="`passengers[${index}][email]`" x-model="form.email" required>
                                </label>
                                <label class="duffel-span-2">
                                    <span>Phone *</span>
                                    <input :name="`passengers[${index}][phone_number]`" x-model="form.phone_number" placeholder="+919876543210" required>
                                </label>
                            </div>

                            <h3>Passport details</h3>
                            <div class="duffel-fields duffel-fields-2">
                                <label class="duffel-span-2">
                                    <span>Country of issue</span>
                                    <input :name="`passengers[${index}][passport_country]`" x-model="form.passport_country" placeholder="India">
                                </label>
                                <label>
                                    <span>Passport number</span>
                                    <input :name="`passengers[${index}][passport_number]`" x-model="form.passport_number">
                                </label>
                                <label>
                                    <span>Expiry date</span>
                                    <input type="date" :name="`passengers[${index}][passport_expiry]`" x-model="form.passport_expiry" min="{{ now()->toDateString() }}">
                                </label>
                            </div>
                        </div>
                    </template>
                </section>

                <section class="duffel-extras">
                    <h2>Add extras</h2>

                    <div class="duffel-extra-row">
                        <div class="duffel-extra-copy">
                            <span class="duffel-extra-icon" aria-hidden="true">🧳</span>
                            <div>
                                <strong>Extra baggage</strong>
                                <p>Add any extra baggage you need for your trip</p>
                            </div>
                        </div>
                        @if (count($bagServices))
                            <button type="button" class="duffel-extra-action" @click="bagsOpen = true">
                                <span x-text="selectedBags().length ? 'Edit' : 'Add'"></span>
                            </button>
                        @else
                            <span class="duffel-extra-na">Not available</span>
                        @endif
                    </div>

                    <div class="duffel-extra-row">
                        <div class="duffel-extra-copy">
                            <span class="duffel-extra-icon" aria-hidden="true">💺</span>
                            <div>
                                <strong>Seat selection</strong>
                                <p>Specify where on the plane you’d like to sit</p>
                            </div>
                        </div>
                        @if (count($seatMaps))
                            <button type="button" class="duffel-extra-action" @click="openSeats()">
                                <span x-text="selectedSeats().length ? 'Edit' : 'Select'"></span>
                            </button>
                        @else
                            <span class="duffel-extra-na">Not available</span>
                        @endif
                    </div>

                    <div class="duffel-extra-summary" x-show="selectedBags().length || selectedSeats().length" x-cloak>
                        <template x-for="bag in selectedBags()" :key="bag.id">
                            <p x-text="bag.label + ' · ' + formatMoney(bag.amount)"></p>
                        </template>
                        <template x-for="seat in selectedSeats()" :key="seat.id">
                            <p x-text="'Seat ' + seat.designator + ' · ' + formatMoney(seat.amount)"></p>
                        </template>
                    </div>
                </section>

                <div class="duffel-checkout-footer">
                    <div>
                        <p class="duffel-footer-label">Total</p>
                        <p class="duffel-footer-total" x-text="formatMoney(grandTotal())"></p>
                        <p class="duffel-footer-fee" x-show="platformFeePercent > 0" x-cloak>
                            Includes <span x-text="platformFeePercent"></span>% platform fee
                            (<span x-text="formatMoney(platformFeeAmount())"></span>)
                        </p>
                    </div>
                    <button type="submit" class="duffel-checkout-submit">
                        <span x-text="paymentChoice === 'hold' ? 'Hold this fare' : (paypalEnabled ? 'Pay securely with PayPal' : 'Confirm booking')"></span>
                        · <span x-text="formatMoney(grandTotal())"></span>
                    </button>
                </div>
            </form>
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
                        <button type="button" class="flight-seat-pax" :class="{ 'is-active': activePassengerIndex === index }" @click="activePassengerIndex = index" x-text="passenger.label"></button>
                    </template>
                </div>
                <div class="flight-seat-toolbar" x-show="seatMaps.length > 1">
                    <template x-for="(map, index) in seatMaps" :key="map.id || index">
                        <button type="button" class="flight-seat-leg" :class="{ 'is-active': activeMapIndex === index }" @click="activeMapIndex = index" x-text="'Flight ' + (index + 1)"></button>
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
                                                <button type="button" class="flight-seat-cell" :class="seatClass(el)" :disabled="!el.available || !serviceForPassenger(el)" @click="pickSeat(el)" :title="el.designator || el.type">
                                                    <span x-text="el.type === 'seat' ? (el.designator || '·') : (el.type === 'aisle' ? '' : '·')"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-public-layout>
