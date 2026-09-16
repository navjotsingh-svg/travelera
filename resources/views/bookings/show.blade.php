<x-public-layout title="Booking {{ $booking->booking_reference }}">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-2xl bg-teal-50 p-4 text-sm text-teal-800">{{ session('status') }}</div>
        @endif

        <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-100">
            <p class="text-sm font-semibold uppercase tracking-widest text-teal-700">{{ $booking->typeLabel() }} ticket</p>
            <h1 class="mt-2 text-3xl font-extrabold">{{ $booking->title() }}</h1>
            <p class="mt-1 text-slate-500">{{ $booking->booking_reference }} · {{ ucfirst($booking->status) }}</p>

            <dl class="mt-8 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Lead traveler</dt>
                    <dd class="font-semibold">{{ $booking->guest_name }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Email</dt>
                    <dd class="font-semibold">{{ $booking->guest_email }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Travelers</dt>
                    <dd class="font-semibold">{{ $booking->travelers }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Total paid</dt>
                    <dd class="font-semibold"><x-money :amount="$booking->total_amount" :currency="$booking->currency ?? 'INR'" /></dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Payment</dt>
                    <dd class="font-semibold capitalize">{{ $booking->payment_status }}</dd>
                </div>
                @if ($booking->stripe_payment_intent_id)
                    <div class="rounded-2xl bg-slate-50 p-4 md:col-span-2">
                        <dt class="text-sm text-slate-500">Stripe payment</dt>
                        <dd class="font-semibold break-all">{{ $booking->stripe_payment_intent_id }}</dd>
                    </div>
                @endif
                @if ($booking->airline_pnr)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Airline PNR</dt>
                        <dd class="font-semibold">{{ $booking->airline_pnr }}</dd>
                    </div>
                @endif
                @if ($booking->duffel_order_id)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Duffel order</dt>
                        <dd class="font-semibold break-all">{{ $booking->duffel_order_id }}</dd>
                    </div>
                @endif
                @if ($booking->travel_date)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Travel date</dt>
                        <dd class="font-semibold">{{ $booking->travel_date->format('D, d M Y') }}</dd>
                    </div>
                @endif
                @if (! empty($booking->snapshot['selected_services']))
                    <div class="rounded-2xl bg-slate-50 p-4 md:col-span-2">
                        <dt class="text-sm text-slate-500">Seats &amp; baggage</dt>
                        <dd class="mt-2 space-y-1">
                            @foreach ($booking->snapshot['selected_services'] as $service)
                                <p class="font-semibold">
                                    {{ $service['label'] ?? ($service['type'] ?? 'Extra') }}
                                    @if (! empty($service['designator']))
                                        · {{ $service['designator'] }}
                                    @endif
                                    · qty {{ $service['quantity'] ?? 1 }}
                                    @if (! empty($service['line_total']))
                                        · <x-money :amount="$service['line_total']" :currency="$service['total_currency'] ?? ($booking->currency ?? 'INR')" />
                                    @endif
                                </p>
                            @endforeach
                        </dd>
                    </div>
                @elseif (! empty($booking->snapshot['included_baggage']))
                    <div class="rounded-2xl bg-slate-50 p-4 md:col-span-2">
                        <dt class="text-sm text-slate-500">Included baggage</dt>
                        <dd class="mt-2 space-y-1">
                            @foreach ($booking->snapshot['included_baggage'] as $bag)
                                <p class="font-semibold">{{ $bag['quantity'] ?? 1 }}× {{ $bag['label'] ?? 'Bag' }}</p>
                            @endforeach
                        </dd>
                    </div>
                @endif
                @if ($booking->check_in)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Stay</dt>
                        <dd class="font-semibold">{{ $booking->check_in->format('d M') }} – {{ $booking->check_out?->format('d M Y') }}</dd>
                    </div>
                @endif
                @if ($booking->pickup_location)
                    <div class="rounded-2xl bg-slate-50 p-4 md:col-span-2">
                        <dt class="text-sm text-slate-500">Cab route</dt>
                        <dd class="font-semibold">{{ $booking->pickup_location }} → {{ $booking->drop_location }} ({{ $booking->distance_km }} km)</dd>
                    </div>
                @endif
            </dl>

            <div class="mt-8 flex gap-3">
                <a href="{{ route('bookings.index') }}" class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Back to trips</a>
                @if ($booking->status === 'confirmed')
                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-full px-5 py-2.5 text-sm font-semibold text-rose-600">Cancel booking</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-public-layout>
