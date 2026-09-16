<x-public-layout title="Complete booking">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-extrabold">Complete your booking</h1>
        <p class="mt-2 text-slate-500">{{ ucfirst($type) }} ·
            @if ($type === 'flight')
                {{ $bookable->airline }} {{ $bookable->flight_number }} · {{ $bookable->origin }} → {{ $bookable->destination }}
            @elseif ($type === 'hotel')
                {{ $bookable->name }}, {{ $bookable->city }}
            @elseif ($type === 'cab')
                {{ $bookable->name }} in {{ $bookable->city }}
            @else
                {{ $bookable->title }}
            @endif
        </p>

        @if ($errors->any())
            <div class="mt-6 rounded-2xl bg-red-50 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('bookings.store') }}" class="mt-8 space-y-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="id" value="{{ $bookable->id }}">

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium">Lead traveler</label>
                    <input name="guest_name" value="{{ old('guest_name', auth()->user()->name) }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                </div>
                <div>
                    <label class="text-sm font-medium">Email</label>
                    <input type="email" name="guest_email" value="{{ old('guest_email', auth()->user()->email) }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                </div>
                <div>
                    <label class="text-sm font-medium">Phone</label>
                    <input name="guest_phone" value="{{ old('guest_phone', auth()->user()->phone) }}" class="mt-1 w-full rounded-2xl border-slate-200">
                </div>
                <div>
                    <label class="text-sm font-medium">Travelers</label>
                    <input type="number" min="1" max="12" name="travelers" value="{{ old('travelers', 1) }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                </div>
            </div>

            @if ($type === 'flight')
                <div>
                    <label class="text-sm font-medium">Travel date</label>
                    <input type="date" name="travel_date" value="{{ old('travel_date', $bookable->departure_at->toDateString()) }}" class="mt-1 w-full rounded-2xl border-slate-200">
                </div>
            @endif

            @if ($type === 'hotel')
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium">Check-in</label>
                        <input type="date" name="check_in" value="{{ old('check_in', now()->toDateString()) }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Check-out</label>
                        <input type="date" name="check_out" value="{{ old('check_out', now()->addDays(2)->toDateString()) }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                    </div>
                </div>
            @endif

            @if ($type === 'cab')
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium">Pickup</label>
                        <input name="pickup_location" value="{{ old('pickup_location') }}" placeholder="Airport / hotel / address" class="mt-1 w-full rounded-2xl border-slate-200" required>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Drop</label>
                        <input name="drop_location" value="{{ old('drop_location') }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Travel date</label>
                        <input type="date" name="travel_date" value="{{ old('travel_date', now()->toDateString()) }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Distance (km)</label>
                        <input type="number" min="1" name="distance_km" value="{{ old('distance_km', 15) }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                    </div>
                </div>
            @endif

            @if ($type === 'package')
                <div>
                    <label class="text-sm font-medium">Start date</label>
                    <input type="date" name="travel_date" value="{{ old('travel_date', now()->addWeek()->toDateString()) }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                </div>
            @endif

            <div>
                <label class="text-sm font-medium">Notes</label>
                <textarea name="notes" rows="3" class="mt-1 w-full rounded-2xl border-slate-200">{{ old('notes') }}</textarea>
            </div>

            <button class="w-full rounded-full bg-teal-600 py-3 font-semibold text-white hover:bg-teal-500">
                {{ ! empty($stripeEnabled) ? 'Pay securely with Stripe' : 'Confirm booking' }}
            </button>
            @if (! empty($stripeEnabled))
                <p class="text-center text-xs text-slate-500">Card payment is processed on Stripe’s secure checkout.</p>
            @endif
        </form>
    </div>
</x-public-layout>
