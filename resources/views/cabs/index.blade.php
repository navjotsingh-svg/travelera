<x-public-layout title="Cabs">
    <div class="bg-slate-950 py-10 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-extrabold">Airport & city cabs</h1>
            <form action="{{ route('cabs.index') }}" method="GET" class="mt-6 grid gap-3 rounded-3xl bg-white p-4 text-slate-800 md:grid-cols-2">
                <select name="city" class="rounded-2xl border-slate-200">
                    <option value="">All cities</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>
                    @endforeach
                </select>
                <button class="rounded-2xl bg-teal-600 font-semibold text-white">Find cabs</button>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($cabs as $cab)
                <a href="{{ route('cabs.show', $cab) }}" class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
                    <img src="{{ $cab->image }}" alt="{{ $cab->name }}" class="h-48 w-full object-cover">
                    <div class="p-5">
                        <p class="font-bold">{{ $cab->name }}</p>
                        <p class="text-sm text-slate-500">{{ $cab->vehicle_type }} · {{ $cab->capacity }} seats · {{ $cab->city }}</p>
                        <p class="mt-3 font-extrabold">Base <x-money :amount="$cab->base_fare" /> · <x-money :amount="$cab->price_per_km" />/km</p>
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-3xl bg-white p-10 text-center text-slate-500">No cabs in that city yet.</div>
            @endforelse
        </div>
        <div class="mt-8">{{ $cabs->links() }}</div>
    </div>
</x-public-layout>
