<x-public-layout title="Holiday packages">
    <div class="bg-slate-950 py-10 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-extrabold">Holiday packages</h1>
            <form action="{{ route('packages.index') }}" method="GET" class="mt-6 grid gap-3 rounded-3xl bg-white p-4 text-slate-800 md:grid-cols-2">
                <select name="destination" class="rounded-2xl border-slate-200">
                    <option value="">All destinations</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" @selected(request('destination') === $city)>{{ $city }}</option>
                    @endforeach
                </select>
                <button class="rounded-2xl bg-teal-600 font-semibold text-white">See packages</button>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-6 md:grid-cols-2">
            @forelse ($packages as $package)
                <a href="{{ route('packages.show', $package) }}" class="flex overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
                    <img src="{{ $package->image }}" alt="{{ $package->title }}" class="h-44 w-44 object-cover">
                    <div class="flex flex-1 flex-col justify-between p-5">
                        <div>
                            <p class="text-sm text-teal-700">{{ $package->destination->city }}, {{ $package->destination->country }}</p>
                            <p class="mt-1 text-xl font-bold">{{ $package->title }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $package->duration_days }} days</p>
                        </div>
                        <p class="text-lg font-extrabold"><x-money :amount="$package->price" /> <span class="text-sm font-medium text-slate-500">/ person</span></p>
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-3xl bg-white p-10 text-center text-slate-500">No packages for that destination yet.</div>
            @endforelse
        </div>
        <div class="mt-8">{{ $packages->links() }}</div>
    </div>
</x-public-layout>
