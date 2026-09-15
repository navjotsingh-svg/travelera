<x-public-layout :title="$package->title">
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <a href="{{ route('packages.index') }}" class="text-sm font-semibold text-teal-700">← All packages</a>
        <div class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
            <img src="{{ $package->image }}" alt="{{ $package->title }}" class="h-80 w-full object-cover">
            <div class="p-8">
                <p class="text-sm font-semibold uppercase tracking-widest text-teal-700">{{ $package->destination->city }}</p>
                <h1 class="mt-2 text-3xl font-extrabold">{{ $package->title }}</h1>
                <p class="mt-2 text-slate-500">{{ $package->duration_days }} days · {{ $package->destination->country }}</p>
                <p class="mt-6 leading-7 text-slate-600">{{ $package->description }}</p>
                <h2 class="mt-8 font-bold">What’s included</h2>
                <ul class="mt-3 grid gap-2 md:grid-cols-2">
                    @foreach ($package->includes ?? [] as $item)
                        <li class="rounded-2xl bg-teal-50 px-4 py-3 text-sm text-teal-900">{{ $item }}</li>
                    @endforeach
                </ul>
                <div class="mt-8 flex items-center justify-between rounded-2xl bg-slate-50 p-5">
                    <div>
                        <p class="text-sm text-slate-500">From</p>
                        <p class="text-3xl font-extrabold"><x-money :amount="$package->price" /> <span class="text-base font-medium text-slate-500">/ person</span></p>
                    </div>
                    <a href="{{ route('bookings.create', ['type' => 'package', 'id' => $package->id]) }}" class="rounded-full bg-teal-600 px-6 py-3 font-semibold text-white">Book package</a>
                </div>
            </div>
        </div>
    </div>
</x-public-layout>
