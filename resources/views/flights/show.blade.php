<x-public-layout :title="$flight->airline.' '.$flight->flight_number">
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <a href="{{ route('flights.index') }}" class="text-sm font-semibold text-teal-700">← All flights</a>
        <div class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
            <img src="{{ $flight->image }}" alt="" class="h-64 w-full object-cover">
            <div class="p-8">
                <p class="text-sm font-semibold uppercase tracking-widest text-teal-700">{{ $flight->airline }}</p>
                <h1 class="mt-2 text-3xl font-extrabold">{{ $flight->origin }} to {{ $flight->destination }}</h1>
                <p class="mt-1 text-slate-500">{{ $flight->flight_number }} · {{ ucfirst($flight->cabin_class) }} · {{ $flight->durationLabel() }}</p>

                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Departs</p>
                        <p class="text-xl font-bold">{{ $flight->departure_at->format('D, d M Y H:i') }}</p>
                        <p class="text-sm">{{ $flight->origin_code }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Arrives</p>
                        <p class="text-xl font-bold">{{ $flight->arrival_at->format('D, d M Y H:i') }}</p>
                        <p class="text-sm">{{ $flight->destination_code }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Fare</p>
                        <p class="text-xl font-bold"><x-money :amount="$flight->price" /> <span class="text-sm font-medium text-slate-500">/ adult</span></p>
                        <p class="text-sm">{{ $flight->seats_available }} seats left</p>
                    </div>
                </div>

                <a href="{{ route('bookings.create', ['type' => 'flight', 'id' => $flight->id]) }}" class="mt-8 inline-flex rounded-full bg-teal-600 px-6 py-3 font-semibold text-white hover:bg-teal-500">Continue to book</a>
            </div>
        </div>
    </div>
</x-public-layout>
