<x-public-layout title="My trips">
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold">My trips</h1>
                <p class="mt-1 text-slate-500">Hello {{ auth()->user()->name }} — your confirmed and cancelled bookings.</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="text-sm font-semibold text-teal-700">Profile</a>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-2xl bg-teal-50 p-4 text-sm text-teal-800">{{ session('status') }}</div>
        @endif

        <div class="mt-8 space-y-4">
            @forelse ($bookings as $booking)
                <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-teal-700">{{ $booking->typeLabel() }} · {{ $booking->booking_reference }}</p>
                            <h2 class="mt-1 text-lg font-bold">{{ $booking->title() }}</h2>
                            <p class="text-sm text-slate-500">
                                {{ $booking->travelers }} traveler(s)
                                @if ($booking->travel_date) · {{ $booking->travel_date->format('d M Y') }} @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-extrabold"><x-money :amount="$booking->total_amount" :currency="$booking->currency ?? 'INR'" /></p>
                            <span class="mt-1 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $booking->status === 'confirmed' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($booking->status) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-3">
                        <a href="{{ route('bookings.show', $booking) }}" class="text-sm font-semibold text-teal-700">View ticket</a>
                        @if ($booking->status === 'confirmed')
                            <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                                @csrf
                                @method('PATCH')
                                <button class="text-sm font-semibold text-rose-600">Cancel</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-3xl bg-white p-10 text-center">
                    <p class="font-semibold">No trips yet</p>
                    <p class="mt-1 text-sm text-slate-500">Search a flight, hotel, cab or package to get started.</p>
                    <a href="{{ route('home') }}" class="mt-4 inline-flex rounded-full bg-teal-600 px-5 py-2 text-sm font-semibold text-white">Start booking</a>
                </div>
            @endforelse
        </div>
        <div class="mt-8">{{ $bookings->links() }}</div>
    </div>
</x-public-layout>
