<x-public-layout :title="$flight['airline'].' '.$flight['flight_number']">
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <a href="{{ route('flights.index') }}" class="text-sm font-semibold text-[#0033a0]">← All flights</a>
        <div class="mt-4 overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
            <div class="p-8">
                <p class="text-sm font-semibold uppercase tracking-widest text-[#0033a0]">Live offer · Duffel</p>
                <h1 class="mt-2 text-3xl font-extrabold">{{ $flight['origin'] }} to {{ $flight['destination'] }}</h1>
                <p class="mt-1 text-slate-500">{{ $flight['airline'] }} · {{ $flight['flight_number'] }} · {{ ucfirst(str_replace('_', ' ', (string) $flight['cabin_class'])) }}</p>

                <div class="mt-8 space-y-4">
                    @foreach ($flight['slices'] ?? [] as $index => $slice)
                        <div class="rounded-2xl bg-slate-50 p-5">
                            <p class="text-sm font-semibold text-slate-500">{{ $index === 0 ? 'Outbound' : 'Return' }} · {{ $slice['origin'] }} → {{ $slice['destination'] }} · {{ $slice['duration'] }}</p>
                            <div class="mt-3 space-y-3">
                                @foreach ($slice['segments'] as $segment)
                                    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                                        <p class="font-semibold">{{ $segment['airline'] }} {{ $segment['flight_number'] }}</p>
                                        <p class="text-sm text-slate-600">
                                            {{ optional($segment['departure_at'])->format('D d M H:i') }} {{ $segment['origin'] }}
                                            →
                                            {{ optional($segment['arrival_at'])->format('H:i') }} {{ $segment['destination'] }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (! empty($flight['included_baggage']))
                    <div class="mt-8 rounded-2xl border border-slate-100 bg-slate-50/80 p-5">
                        <p class="text-sm font-semibold text-slate-500">Baggage included</p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            @foreach ($flight['included_baggage'] as $bag)
                                <div class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200">
                                    <span>{{ ($bag['type'] ?? '') === 'carry_on' ? '🎒' : '🧳' }}</span>
                                    <span>{{ $bag['quantity'] }}× {{ $bag['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if (! empty($flight['bag_services']))
                            <p class="mt-3 text-sm text-slate-500">Extra bags and seats can be added on the next step.</p>
                        @endif
                    </div>
                @elseif (! empty($flight['bag_services']))
                    <div class="mt-8 rounded-2xl border border-amber-100 bg-amber-50/70 p-5 text-sm text-amber-900">
                        No free check-in bag on this fare — you can add baggage before confirming.
                    </div>
                @endif

                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Passengers</p>
                        <p class="text-xl font-bold">{{ $flight['passenger_count'] }} adult{{ $flight['passenger_count'] > 1 ? 's' : '' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Offer expires</p>
                        <p class="text-xl font-bold">{{ optional($flight['expires_at'])->format('H:i') ?? 'Soon' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">Total</p>
                        <p class="text-xl font-bold"><x-money :amount="$flight['total_amount']" :currency="$flight['total_currency']" /></p>
                    </div>
                </div>

                <a href="{{ route('flights.book', $flight['id']) }}" class="mt-8 inline-flex rounded-full bg-[#0033a0] px-6 py-3 font-semibold text-white hover:bg-[#00287d]">Continue · bags & seats</a>
            </div>
        </div>
    </div>
</x-public-layout>
