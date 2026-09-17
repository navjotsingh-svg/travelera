<x-public-layout title="Booking {{ $booking->booking_reference }}">
    @php
        $departure = $booking->departureAt();
        $arrival = $booking->arrivalAt();
        $slices = $booking->provider === 'duffel' || $booking->typeLabel() === 'Flight'
            ? $booking->itinerarySlices()
            : [];
    @endphp

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-2xl bg-teal-50 p-4 text-sm text-teal-800">{{ session('status') }}</div>
        @endif

        <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-100">
            <p class="text-sm font-semibold uppercase tracking-widest text-teal-700">{{ $booking->typeLabel() }} ticket</p>
            <h1 class="mt-2 text-3xl font-extrabold">{{ $booking->title() }}</h1>
            <p class="mt-1 text-slate-500">{{ $booking->booking_reference }} · {{ ucfirst($booking->status) }}</p>

            @if ($departure || $arrival || $slices !== [])
                <div class="ticket-schedule mt-8 overflow-hidden rounded-[24px] p-6 sm:p-7">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="ticket-kicker text-xs font-bold tracking-[0.2em]">FLIGHT SCHEDULE</p>
                            @if ($departure)
                                <p class="mt-2 text-lg font-semibold">{{ $departure->format('D, d M Y') }}</p>
                            @elseif ($booking->travel_date)
                                <p class="mt-2 text-lg font-semibold">{{ $booking->travel_date->format('D, d M Y') }}</p>
                            @endif
                        </div>
                        @if ($booking->flightDurationLabel())
                            <p class="ticket-chip rounded-full px-3 py-1 text-xs font-semibold tracking-wide">
                                {{ $booking->flightDurationLabel() }}
                            </p>
                        @endif
                    </div>

                    <div class="mt-8 grid gap-6 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
                        <div>
                            <p class="text-4xl font-extrabold tracking-tight">{{ $departure?->format('H:i') ?? '—' }}</p>
                            <p class="ticket-soft mt-2 text-sm font-semibold">{{ $booking->snapshot['origin'] ?? 'Departure' }}</p>
                            <p class="ticket-muted mt-1 text-xs">{{ $departure?->format('D, d M Y') ?? 'Local departure' }}</p>
                        </div>

                        <div class="hidden text-center sm:block">
                            <div class="ticket-rule mx-auto h-px w-16"></div>
                            <p class="ticket-muted mt-2 text-[11px] font-bold tracking-[0.18em]">
                                {{ ($booking->snapshot['stops'] ?? 0) > 0 ? ($booking->snapshot['stops'].' stop') : 'Non-stop' }}
                            </p>
                        </div>

                        <div class="sm:text-right">
                            <p class="text-4xl font-extrabold tracking-tight">{{ $arrival?->format('H:i') ?? '—' }}</p>
                            <p class="ticket-soft mt-2 text-sm font-semibold">{{ $booking->snapshot['destination'] ?? 'Arrival' }}</p>
                            <p class="ticket-muted mt-1 text-xs">{{ $arrival?->format('D, d M Y') ?? 'Local arrival' }}</p>
                        </div>
                    </div>

                    @if (count($slices) > 0)
                        <div class="mt-8 space-y-4 border-t border-white/15 pt-6">
                            @foreach ($slices as $sliceIndex => $slice)
                                @php
                                    $segments = $slice['segments'] ?? [];
                                @endphp
                                @if (count($slices) > 1)
                                    <p class="ticket-kicker text-xs font-bold tracking-[0.18em]">
                                        {{ $sliceIndex === 0 ? 'OUTBOUND' : 'RETURN' }}
                                        @if (! empty($slice['origin']) && ! empty($slice['destination']))
                                            · {{ $slice['origin'] }} → {{ $slice['destination'] }}
                                        @endif
                                    </p>
                                @endif

                                @foreach ($segments as $segment)
                                    @php
                                        $segDep = filled($segment['departure_at'] ?? null) ? \Carbon\Carbon::parse($segment['departure_at']) : null;
                                        $segArr = filled($segment['arrival_at'] ?? null) ? \Carbon\Carbon::parse($segment['arrival_at']) : null;
                                    @endphp
                                    <div class="ticket-segment rounded-2xl px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                            <p class="font-semibold">
                                                {{ $segment['flight_number'] ?? ($booking->snapshot['flight_number'] ?? 'Flight') }}
                                                @if (! empty($segment['airline']))
                                                    <span class="ticket-soft font-normal">· {{ $segment['airline'] }}</span>
                                                @endif
                                            </p>
                                            @if (! empty($segment['duration']))
                                                <p class="ticket-soft text-xs">{{ $segment['duration'] }}</p>
                                            @endif
                                        </div>
                                        <div class="ticket-soft mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm">
                                            <p>
                                                <span class="ticket-muted">Dep</span>
                                                <strong class="ml-1">{{ $segDep?->format('D, d M · H:i') ?? '—' }}</strong>
                                                <span class="ticket-soft ml-1">{{ $segment['origin'] ?? '' }}</span>
                                            </p>
                                            <p>
                                                <span class="ticket-muted">Arr</span>
                                                <strong class="ml-1">{{ $segArr?->format('D, d M · H:i') ?? '—' }}</strong>
                                                <span class="ticket-soft ml-1">{{ $segment['destination'] ?? '' }}</span>
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

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
                @if ((float) ($booking->platform_fee_amount ?? 0) > 0)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Fare</dt>
                        <dd class="font-semibold"><x-money :amount="$booking->base_amount ?? ($booking->total_amount - $booking->platform_fee_amount)" :currency="$booking->currency ?? 'INR'" /></dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Platform fee ({{ number_format((float) $booking->platform_fee_percent, 2) }}%)</dt>
                        <dd class="font-semibold"><x-money :amount="$booking->platform_fee_amount" :currency="$booking->currency ?? 'INR'" /></dd>
                    </div>
                @endif
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Payment</dt>
                    <dd class="font-semibold capitalize">{{ $booking->payment_status }}</dd>
                </div>
                @if ($departure)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Departure</dt>
                        <dd class="font-semibold">{{ $departure->format('D, d M Y · H:i') }}</dd>
                    </div>
                @elseif ($booking->travel_date)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Travel date</dt>
                        <dd class="font-semibold">{{ $booking->travel_date->format('D, d M Y') }}</dd>
                    </div>
                @endif
                @if ($arrival)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Arrival</dt>
                        <dd class="font-semibold">{{ $arrival->format('D, d M Y · H:i') }}</dd>
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
                @if ($booking->paypal_capture_id || $booking->paypal_order_id)
                    <div class="rounded-2xl bg-slate-50 p-4 md:col-span-2">
                        <dt class="text-sm text-slate-500">PayPal payment</dt>
                        <dd class="font-semibold break-all">{{ $booking->paypal_capture_id ?: $booking->paypal_order_id }}</dd>
                    </div>
                @elseif ($booking->stripe_payment_intent_id)
                    <div class="rounded-2xl bg-slate-50 p-4 md:col-span-2">
                        <dt class="text-sm text-slate-500">Stripe payment</dt>
                        <dd class="font-semibold break-all">{{ $booking->stripe_payment_intent_id }}</dd>
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
