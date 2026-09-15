<x-public-layout title="Book {{ $flight['airline'] }} {{ $flight['flight_number'] }}">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <a href="{{ route('flights.offer', $flight['id']) }}" class="text-sm font-semibold text-[#0033a0]">← Offer details</a>
        <h1 class="mt-4 text-3xl font-extrabold">Passenger details</h1>
        <p class="mt-2 text-slate-500">{{ $flight['airline'] }} {{ $flight['flight_number'] }} · {{ $flight['origin'] }} → {{ $flight['destination'] }} · <x-money :amount="$flight['total_amount']" :currency="$flight['total_currency']" /></p>
        <p class="mt-2 text-sm text-slate-500">Names must match the passport. Duffel books this fare with the airline and pays from your Duffel balance in test mode.</p>

        @if ($errors->any())
            <div class="mt-6 rounded-2xl bg-red-50 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('flights.book.store', $flight['id']) }}" class="mt-8 space-y-6">
            @csrf
            @foreach ($flight['passengers'] as $index => $passenger)
                @php
                    $nameParts = explode(' ', auth()->user()->name, 2);
                @endphp
                <fieldset class="space-y-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <legend class="px-2 font-semibold">Passenger {{ $index + 1 }}</legend>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium">Title</label>
                            <select name="passengers[{{ $index }}][title]" class="mt-1 w-full rounded-2xl border-slate-200" required>
                                @foreach (['mr' => 'Mr', 'ms' => 'Ms', 'mrs' => 'Mrs', 'miss' => 'Miss', 'dr' => 'Dr'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old("passengers.$index.title", 'mr') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium">Gender</label>
                            <select name="passengers[{{ $index }}][gender]" class="mt-1 w-full rounded-2xl border-slate-200" required>
                                <option value="m" @selected(old("passengers.$index.gender", 'm') === 'm')>Male</option>
                                <option value="f" @selected(old("passengers.$index.gender") === 'f')>Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium">Given name</label>
                            <input name="passengers[{{ $index }}][given_name]" value="{{ old("passengers.$index.given_name", $index === 0 ? ($nameParts[0] ?? '') : '') }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                        </div>
                        <div>
                            <label class="text-sm font-medium">Family name</label>
                            <input name="passengers[{{ $index }}][family_name]" value="{{ old("passengers.$index.family_name", $index === 0 ? ($nameParts[1] ?? 'Traveler') : '') }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                        </div>
                        <div>
                            <label class="text-sm font-medium">Date of birth</label>
                            <input type="date" name="passengers[{{ $index }}][born_on]" value="{{ old("passengers.$index.born_on") }}" max="{{ now()->subYears(12)->toDateString() }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                        </div>
                        <div>
                            <label class="text-sm font-medium">Email</label>
                            <input type="email" name="passengers[{{ $index }}][email]" value="{{ old("passengers.$index.email", auth()->user()->email) }}" class="mt-1 w-full rounded-2xl border-slate-200" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium">Phone (with country code)</label>
                            <input name="passengers[{{ $index }}][phone_number]" value="{{ old("passengers.$index.phone_number", auth()->user()->phone ? '+91'.auth()->user()->phone : '+919876543210') }}" class="mt-1 w-full rounded-2xl border-slate-200" placeholder="+919876543210" required>
                        </div>
                    </div>
                </fieldset>
            @endforeach

            <button class="w-full rounded-full bg-[#0033a0] py-3 font-semibold text-white hover:bg-[#00287d]">Confirm Duffel booking</button>
        </form>
    </div>
</x-public-layout>
