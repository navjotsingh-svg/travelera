<x-public-layout :title="$cab->name">
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <a href="{{ route('cabs.index') }}" class="text-sm font-semibold text-teal-700">← All cabs</a>
        <div class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
            <img src="{{ $cab->image }}" alt="{{ $cab->name }}" class="h-72 w-full object-cover">
            <div class="p-8">
                <h1 class="text-3xl font-extrabold">{{ $cab->name }}</h1>
                <p class="mt-1 text-slate-500">{{ $cab->vehicle_type }} · {{ $cab->city }} · up to {{ $cab->capacity }} travelers</p>
                <p class="mt-6 leading-7 text-slate-600">{{ $cab->description }}</p>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Base fare</p>
                        <p class="text-2xl font-extrabold"><x-money :amount="$cab->base_fare" /></p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Per kilometre</p>
                        <p class="text-2xl font-extrabold"><x-money :amount="$cab->price_per_km" /></p>
                    </div>
                </div>
                <a href="{{ route('bookings.create', ['type' => 'cab', 'id' => $cab->id]) }}" class="mt-8 inline-flex rounded-full bg-teal-600 px-6 py-3 font-semibold text-white">Book this cab</a>
            </div>
        </div>
    </div>
</x-public-layout>
