@props([
    'showCabin' => true,
    'framed' => true,
])

<form action="{{ route('flights.index') }}" method="GET" x-data="flightSearch" {{ $attributes }}>
    <div @class([
        'search-bar flight-listing-search',
        'rounded-[22px] border border-slate-100 bg-white' => $framed,
    ])>
        <div class="flight-listing-route">
            <x-airport-input
                name="from"
                label="From"
                :value="request('from')"
                placeholder="City or airport"
                compact
                x-ref="fromPicker"
            />
            <button
                type="button"
                @click="swapAirports()"
                class="search-swap flight-listing-swap"
                aria-label="Swap airports"
            >
                ⇄
            </button>
            <x-airport-input
                name="to"
                label="To"
                :value="request('to')"
                placeholder="City or airport"
                compact
                x-ref="toPicker"
            />
        </div>

        <label class="flight-listing-field">
            <span class="flight-listing-label">Departure</span>
            <input type="date" name="date" value="{{ request('date', now()->addDay()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
        </label>

        <label class="flight-listing-field">
            <span class="flight-listing-label">Return</span>
            <input type="date" name="return_date" value="{{ request('return_date') }}" min="{{ now()->toDateString() }}">
        </label>

        <label class="flight-listing-field flight-listing-travellers">
            <span class="flight-listing-label">Travellers</span>
            <select name="adults">
                @for ($i = 1; $i <= 6; $i++)
                    <option value="{{ $i }}" @selected((int) request('adults', 1) === $i)>{{ $i }} Adult{{ $i > 1 ? 's' : '' }}</option>
                @endfor
            </select>
            @if ($showCabin)
                <select name="cabin" class="flight-listing-cabin">
                    <option value="economy" @selected(request('cabin', 'economy') === 'economy')>Economy</option>
                    <option value="premium_economy" @selected(request('cabin') === 'premium_economy')>Premium economy</option>
                    <option value="business" @selected(request('cabin') === 'business')>Business</option>
                    <option value="first" @selected(request('cabin') === 'first')>First</option>
                </select>
            @else
                <input type="hidden" name="cabin" value="economy">
            @endif
        </label>

        <button type="submit" class="search-submit flight-listing-submit" aria-label="Search flights">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
            <span class="flight-listing-submit-text">Search</span>
        </button>
    </div>
</form>
