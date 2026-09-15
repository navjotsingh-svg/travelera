@props(['cities'])

@php
    $tabs = [
        ['flights', 'Flights', '✈️'],
        ['hotels', 'Hotels', '🏨'],
        ['packages', 'Experiences', '🎭'],
        ['cabs', 'Cabs', '🚗'],
        ['corporate', 'Corporate', '💼'],
    ];
@endphp

<div class="booking-widget" x-data="homeSearch">
    <div class="booking-tabs">
        @foreach ($tabs as [$key, $label, $icon])
            @if ($key === 'flights')
                <button type="button" class="booking-tab is-active">
                    <span class="booking-tab-icon" aria-hidden="true">{{ $icon }}</span>
                    {{ $label }}
                </button>
            @else
                <span class="booking-tab is-disabled" aria-disabled="true" title="Coming soon">
                    <span class="booking-tab-icon" aria-hidden="true">{{ $icon }}</span>
                    {{ $label }}
                </span>
            @endif
        @endforeach
        <div class="booking-currency">$ USD</div>
    </div>

    <form x-show="tab === 'flights'" action="{{ route('flights.index') }}" method="GET" class="booking-body">
        <div class="trip-types">
            <label class="trip-option"><input type="radio" value="one_way" x-model="trip"><span>One Way</span></label>
            <label class="trip-option"><input type="radio" value="round" x-model="trip"><span>Round Trip</span></label>
            <label class="trip-option"><input type="radio" value="multi" x-model="trip"><span>Multi City</span></label>
        </div>

        <div class="booking-fields">
            <div class="booking-route">
                <x-airport-input
                    name="from"
                    label="From"
                    :value="request('from')"
                    placeholder="Leaving from?"
                    hint="Search by place / airport"
                    variant="hero"
                    x-ref="fromPicker"
                />

                <button type="button" class="search-swap" @click="swapAirports()" aria-label="Swap airports">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h11M15 5l3 3-3 3M17 16H6M9 13l-3 3 3 3"/>
                    </svg>
                </button>

                <x-airport-input
                    name="to"
                    label="To"
                    :value="request('to')"
                    placeholder="Going to?"
                    hint="Search by place / airport"
                    variant="hero"
                    x-ref="toPicker"
                />
            </div>

            <label class="booking-field booking-date-field">
                <span class="booking-label">Departure</span>
                <span class="booking-date-placeholder">Select date</span>
                <input type="date" name="date" x-model="departDate" min="{{ now()->toDateString() }}" class="booking-date" :class="departDate && 'is-filled'" required>
            </label>

            <label class="booking-field booking-date-field" x-show="trip === 'round'" x-cloak>
                <span class="booking-label">Return</span>
                <span class="booking-date-placeholder">Select date</span>
                <input type="date" name="return_date" x-model="returnDate" min="{{ now()->toDateString() }}" class="booking-date" :class="returnDate && 'is-filled'" :disabled="trip !== 'round'">
            </label>

            <div class="booking-field booking-travellers" @click.outside="travellersOpen = false">
                <button type="button" class="booking-travellers-toggle" @click="travellersOpen = ! travellersOpen">
                    <span class="booking-label">Travellers + Class</span>
                    <span class="booking-value" x-text="adults + (adults === 1 ? ' Passenger' : ' Passengers')"></span>
                    <span class="booking-hint">Economy · Business · First</span>
                </button>
                <div class="travellers-panel" x-show="travellersOpen" x-cloak>
                    <label>Adults
                        <select x-model.number="adults" name="adults">
                            @for ($i = 1; $i <= 6; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </label>
                    <label>Class
                        <select x-model="cabin" name="cabin">
                            <option value="economy">Economy</option>
                            <option value="premium_economy">Premium economy</option>
                            <option value="business">Business</option>
                            <option value="first">First</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <div class="booking-footer">
            <label class="pay-with">
                Pay with
                <select>
                    <option>Card / Bank</option>
                    <option>UPI</option>
                    <option>Wallet</option>
                </select>
            </label>

            <div class="offer-row">
                <span class="offer-chip">Travelera Exclusive <em>Offer</em></span>
                <span class="offer-chip">Students <em>Offer</em></span>
                <span class="offer-chip">Family &amp; Friends <em>Offer</em></span>
                <button type="button" class="promo-link" @click="promoOpen = ! promoOpen">+ ADD PROMOCODE</button>
            </div>

            <input x-show="promoOpen" x-cloak type="text" name="promo" x-model="promo" placeholder="Enter code" class="promo-input">

            <button type="submit" class="booking-search">Search</button>
        </div>
    </form>

    <form x-show="tab === 'hotels'" action="{{ route('hotels.index') }}" method="GET" class="booking-body" style="display: none;">
        <div class="booking-fields">
            <label class="booking-field">
                <span class="booking-label">City</span>
                <input type="text" name="city" list="home-cities" placeholder="Where to stay?" class="booking-value">
                <span class="booking-hint">City or hotel area</span>
            </label>
            <label class="booking-field booking-date-field">
                <span class="booking-label">Check-in</span>
                <input type="date" name="check_in" value="{{ now()->toDateString() }}" class="booking-date">
            </label>
            <label class="booking-field booking-date-field">
                <span class="booking-label">Check-out</span>
                <input type="date" name="check_out" value="{{ now()->addDays(2)->toDateString() }}" class="booking-date">
            </label>
            <button class="booking-search">Search</button>
        </div>
        <datalist id="home-cities">
            @foreach ($cities as $city)
                <option value="{{ $city }}"></option>
            @endforeach
        </datalist>
    </form>

    <form x-show="tab === 'packages'" action="{{ route('packages.index') }}" method="GET" class="booking-body" style="display: none;">
        <div class="booking-fields">
            <label class="booking-field">
                <span class="booking-label">Destination</span>
                <input type="text" name="destination" placeholder="Where next?" class="booking-value">
                <span class="booking-hint">City or country</span>
            </label>
            <label class="booking-field">
                <span class="booking-label">Travellers</span>
                <input type="number" name="travelers" min="1" value="2" class="booking-value">
            </label>
            <button class="booking-search">Search</button>
        </div>
    </form>

    <form x-show="tab === 'cabs'" action="{{ route('cabs.index') }}" method="GET" class="booking-body" style="display: none;">
        <div class="booking-fields">
            <label class="booking-field">
                <span class="booking-label">City</span>
                <input type="text" name="city" placeholder="Pickup city" class="booking-value">
            </label>
            <label class="booking-field">
                <span class="booking-label">Pickup</span>
                <input type="text" name="q" placeholder="Airport, hotel or area" class="booking-value">
            </label>
            <button class="booking-search">Search</button>
        </div>
    </form>

    <div x-show="tab === 'corporate'" class="booking-body corporate-note" style="display: none;">
        <p>Need a corporate travel desk? Share your company details and we will set up billed trips, approvals and fare deals.</p>
        <a href="{{ url('/#contact') }}" class="booking-search">Talk to us</a>
    </div>

    <a href="{{ url('/#deals') }}" class="booking-deals">Deals and Offers</a>
</div>

<div class="booking-links">
    <a href="{{ route('home') }}">Travelera BluSky Rewards <img src="{{ asset('images/teer.png') }}"></a>
    <a href="{{ route('visa') }}">Flight Cancellation Policy <img src="{{ asset('images/teer.png') }}"></a>
    <a href="{{ route('bookings.index') }}">Manage My Booking <img src="{{ asset('images/teer.png') }}"></a>
</div>
