<x-public-layout :title="$hotel->name">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <a href="{{ route('hotels.index') }}" class="text-sm font-semibold text-teal-700">← All hotels</a>
        <div class="mt-4 grid gap-8 lg:grid-cols-[1.4fr_0.8fr]">
            <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
                <img src="{{ $hotel->image }}" alt="{{ $hotel->name }}" class="h-80 w-full object-cover">
                <div class="p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-extrabold">{{ $hotel->name }}</h1>
                            <p class="mt-1 text-slate-500">{{ $hotel->address }}, {{ $hotel->city }}</p>
                            <p class="mt-2 text-amber-500">{{ str_repeat('★', $hotel->star_rating) }}</p>
                        </div>
                        <span class="rounded-2xl bg-teal-700 px-3 py-2 text-lg font-bold text-white">{{ $hotel->guest_rating }}</span>
                    </div>
                    <p class="mt-6 leading-7 text-slate-600">{{ $hotel->description }}</p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($hotel->amenities ?? [] as $amenity)
                            <span class="rounded-full bg-teal-50 px-3 py-1 text-sm font-medium text-teal-800">{{ $amenity }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            <aside class="h-fit rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <p class="text-sm text-slate-500">From</p>
                <p class="text-3xl font-extrabold"><x-money :amount="$hotel->price_per_night" /></p>
                <p class="text-sm text-slate-500">per night · {{ $hotel->rooms_available }} rooms left</p>
                <a href="{{ route('bookings.create', ['type' => 'hotel', 'id' => $hotel->id]) }}" class="mt-6 inline-flex w-full justify-center rounded-full bg-teal-600 px-6 py-3 font-semibold text-white">Reserve stay</a>
            </aside>
        </div>

        @if ($similar->isNotEmpty())
            <h2 class="mt-12 text-2xl font-bold">More stays in {{ $hotel->city }}</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                @foreach ($similar as $item)
                    <a href="{{ route('hotels.show', $item) }}" class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-100">
                        <img src="{{ $item->image }}" class="h-36 w-full object-cover" alt="{{ $item->name }}">
                        <div class="p-4">
                            <p class="font-semibold">{{ $item->name }}</p>
                            <p class="text-sm text-slate-500"><x-money :amount="$item->price_per_night" /> / night</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-public-layout>
