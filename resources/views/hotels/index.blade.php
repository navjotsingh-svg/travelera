<x-public-layout title="Hotels">
    <div class="bg-slate-950 py-10 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-extrabold">Find a stay</h1>
            <form action="{{ route('hotels.index') }}" method="GET" class="mt-6 grid gap-3 rounded-3xl bg-white p-4 text-slate-800 md:grid-cols-3">
                <select name="city" class="rounded-2xl border-slate-200">
                    <option value="">All cities</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>
                    @endforeach
                </select>
                <select name="stars" class="rounded-2xl border-slate-200">
                    <option value="">Any rating</option>
                    <option value="5" @selected(request('stars') == 5)>5 star</option>
                    <option value="4" @selected(request('stars') == 4)>4 star & above</option>
                </select>
                <button class="rounded-2xl bg-teal-600 font-semibold text-white">Search hotels</button>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($hotels as $hotel)
                <a href="{{ route('hotels.show', $hotel) }}" class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
                    <img src="{{ $hotel->image }}" alt="{{ $hotel->name }}" class="h-52 w-full object-cover">
                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-bold">{{ $hotel->name }}</p>
                                <p class="text-sm text-slate-500">{{ $hotel->city }} · {{ str_repeat('★', $hotel->star_rating) }}</p>
                            </div>
                            <span class="rounded-full bg-teal-700 px-2.5 py-1 text-xs font-bold text-white">{{ $hotel->guest_rating }}</span>
                        </div>
                        <p class="mt-4 text-lg font-extrabold"><x-money :amount="$hotel->price_per_night" /> <span class="text-sm font-medium text-slate-500">/ night</span></p>
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-3xl bg-white p-10 text-center text-slate-500">No hotels in that city yet.</div>
            @endforelse
        </div>
        <div class="mt-8">{{ $hotels->links() }}</div>
    </div>
</x-public-layout>
