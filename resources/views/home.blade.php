<x-public-layout>
    {{-- Hero --}}
    <section class="relative">
        <div class="relative h-[380px] overflow-hidden sm:h-[460px] lg:h-[520px]">
            <img src="{{ asset('images/sky-with-clouds-sunset-with.webp') }}" alt="Airplane in a bright sky" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-sky-400/10 via-transparent to-white"></div>
            <h1 class="hero-title absolute inset-x-0 bottom-24 text-center text-4xl font-extrabold tracking-tight text-white drop-shadow-[0_8px_24px_rgba(0,0,0,0.35)] sm:bottom-28 sm:text-5xl lg:text-[56px]">Fly Beyond <span>Boundaries</span></h1>
        </div>

        <div id="search" class="relative z-10 mx-auto max-w-6xl scroll-mt-28 px-4 pb-6 sm:px-6">
            <x-home-search :cities="$cities" />

            
        </div>
    </section>

    {{-- Exclusive deals --}}
    <section id="deals" class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <p class="section-kicker text-xs font-bold tracking-[0.22em] text-brand-700">EXCLUSIVE DEALS</p>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $dealTiles = [
                    ['London nights', 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?auto=format&fit=crop&w=900&q=80', 'London'],
                    ['Paris after dark', 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=900&q=80', 'Paris'],
                    ['Dubai skyline', 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=900&q=80', 'Dubai'],
                    ['Island reset', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=900&q=80', 'Bali'],
                ];
            @endphp
            @foreach ($dealTiles as [$title, $image, $city])
                <a href="{{ route('packages.index', ['destination' => $city]) }}" class="media-tile group relative h-44 overflow-hidden rounded-2xl">
                    <img src="{{ $image }}" alt="{{ $title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/15 to-transparent"></div>
                    <p class="absolute bottom-3 left-3 font-bold text-white">{{ $title }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Destinations --}}
    <section class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
        <p class="section-kicker text-xs font-bold tracking-[0.22em] text-brand-700">WHERE WILL YOU FLY NEXT?</p>
        <div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($destinations as $index => $destination)
                <a href="{{ $index === 3 ? route('packages.index') : route('packages.index', ['destination' => $destination->city]) }}" class="media-tile group relative h-48 overflow-hidden rounded-2xl sm:h-56">
                    <img src="{{ $destination->image }}" alt="{{ $destination->city }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 to-transparent"></div>
                    @if ($index === 3)
                        <div class="absolute inset-0 flex items-end justify-between p-4 text-white">
                            <span class="font-bold">{{ $destination->city }}</span>
                            <span class="text-xs font-bold tracking-widest">VIEW ALL →</span>
                        </div>
                    @else
                        <p class="absolute bottom-4 left-4 font-bold text-white">{{ $destination->city }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </section>

    {{-- Private experience --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <p class="section-kicker text-xs font-bold tracking-[0.22em] text-brand-700">YOUR EXPERIENCE,</p>
        <h2 class="section-heading text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">YOUR WAY</h2>
        <div class="mt-8 grid overflow-hidden rounded-[28px] bg-slate-50 shadow-sm lg:grid-cols-2">
            <img src="https://images.unsplash.com/photo-1570710891163-6d3b5c47248b?auto=format&fit=crop&w=1400&q=80" alt="Private jet cabin" class="h-72 w-full object-cover lg:h-full">
            <div class="p-6 sm:p-10">
                <h3 class="text-2xl font-extrabold text-slate-900">Where luxury meets altitude</h3>
                <p class="mt-3 text-sm leading-6 text-slate-500">Charter a cabin, plan a milestone trip, or let us build a private itinerary around your dates. Share a few details and a specialist will follow up.</p>
                <form method="POST" action="{{ route('contact.store') }}" class="site-form mt-6 grid gap-3">
                    @csrf
                    <input type="hidden" name="intent" value="enquiry">
                    <input name="name" required placeholder="Full Name" class="rounded-xl border-slate-200 text-sm">
                    <input name="phone" placeholder="Phone" class="rounded-xl border-slate-200 text-sm">
                    <input type="email" name="email" required placeholder="Email" class="rounded-xl border-slate-200 text-sm">
                    <textarea name="message" rows="2" placeholder="Tell us about the trip" class="rounded-xl border-slate-200 text-sm"></textarea>
                    <button class="mt-1 rounded-full bg-brand-700 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-800">Submit</button>
                </form>
            </div>
        </div>
    </section>

    {{-- Services --}}
    <section id="services" class="scroll-mt-24 mx-auto max-w-6xl px-4 pb-16 sm:px-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Flight Booking', 'Search and book airline tickets with live Duffel fares.', route('flights.index'), 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
                ['Hotel Booking', 'Hand-picked stays with guest scores you can trust.', route('hotels.index'), 'M4 21V7a2 2 0 012-2h12a2 2 0 012 2v14'],
                ['Cab Booking', 'Airport transfers and city cabs with clear fares.', route('cabs.index'), 'M4 16v2a1 1 0 001 1h1m13-3v2a1 1 0 01-1 1h-1M4 12l2-5h12l2 5'],
                ['Holiday Packages', 'Ready-made holidays with flights, hotels and more.', route('packages.index'), 'M12 3v18M8 7h8M6 11h12'],
            ] as [$title, $copy, $href, $icon])
                <a href="{{ $href }}" class="service-card rounded-2xl bg-white p-6 text-center shadow-[0_10px_40px_rgba(15,23,42,0.06)] ring-1 ring-slate-100 transition hover:-translate-y-0.5">
                    <span class="service-icon mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-700 text-white shadow-md shadow-blue-900/15">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </span>
                    <p class="mt-4 font-bold text-slate-900">{{ $title }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $copy }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Trusted partner --}}
    <section id="about" class="scroll-mt-24 bg-gradient-to-r from-[#0a2a8f] via-brand-700 to-[#2b6bff] py-16 text-white">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2">
            <div>
                <h2 class="section-heading text-3xl font-extrabold tracking-tight sm:text-4xl">YOUR TRUSTED TRAVEL PARTNER</h2>
                <p class="mt-4 max-w-lg text-sm leading-7 text-blue-100">From the first search to the boarding pass, Travelera looks after flights, stays, cabs and visas as one trip. We combine live airline inventory with local trip design so you spend less time stitching bookings together.</p>
                <div class="mt-8 grid grid-cols-2 gap-6 text-center sm:grid-cols-4 sm:text-left">
                    <div><p class="text-2xl font-extrabold">12+</p><p class="mt-1 text-xs text-blue-200">Years of care</p></div>
                    <div><p class="text-2xl font-extrabold">50+</p><p class="mt-1 text-xs text-blue-200">Destinations</p></div>
                    <div><p class="text-2xl font-extrabold">10k+</p><p class="mt-1 text-xs text-blue-200">Happy clients</p></div>
                    <div><p class="text-2xl font-extrabold">24/7</p><p class="mt-1 text-xs text-blue-200">Support</p></div>
                </div>
            </div>
            <div class="relative">
                <img src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?auto=format&fit=crop&w=1200&q=80" alt="Travelers planning a trip" class="h-72 w-full rounded-3xl object-cover shadow-2xl">
                <img src="https://images.unsplash.com/photo-1526778548025-fa2f459cd5c1?auto=format&fit=crop&w=400&q=80" alt="" class="absolute -left-4 -top-4 hidden h-24 w-40 rounded-2xl object-cover ring-4 ring-white/20 lg:block">
            </div>
        </div>
    </section>

    {{-- Inspiration --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <p class="section-kicker text-xs font-bold tracking-[0.22em] text-brand-700">ENJOY FRESH TRAVEL INSPIRATION</p>
        <div class="mt-6 grid gap-5 md:grid-cols-3">
            @php
                $stories = [
                    ['Bali temple days', 'How to slow down in Ubud without missing the coast.', 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=1200&q=80', now()->subDays(4)],
                    ['Himalayan quiet', 'A weekend in the mountains when the city feels too loud.', 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=80', now()->subDays(9)],
                    ['Golden hour cities', 'Where to stand when the light hits Paris, Dubai and Jaipur.', 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=1200&q=80', now()->subDays(14)],
                ];
            @endphp
            @foreach ($stories as [$title, $excerpt, $image, $date])
                <article class="media-tile group overflow-hidden rounded-2xl bg-slate-900">
                    <div class="relative h-64">
                        <img src="{{ $image }}" alt="{{ $title }}" class="h-full w-full object-cover opacity-90 transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-5 text-white">
                            <p class="text-xs font-semibold tracking-widest text-white/70">{{ $date->format('d M Y') }}</p>
                            <h3 class="mt-2 text-lg font-bold">{{ $title }}</h3>
                            <p class="mt-1 text-sm text-white/80">{{ $excerpt }}</p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Contact --}}
    <section id="contact" class="scroll-mt-24 mx-auto max-w-6xl px-4 pb-20 sm:px-6">
        <div class="grid items-start gap-10 lg:grid-cols-2">
            <div>
                <p class="section-kicker text-xs font-bold tracking-[0.22em] text-brand-700">CONTACT</p>
                <h2 class="section-heading mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">LET'S PLAN YOUR<br>NEXT JOURNEY</h2>
                <div class="mt-8 space-y-5 text-sm text-slate-600">
                    <p class="flex items-start gap-3"><span class="mt-0.5 text-brand-700">📍</span> 42 Horizon Plaza, Connaught Place, New Delhi 110001</p>
                    <p class="flex items-start gap-3"><span class="mt-0.5 text-brand-700">📞</span> +91 98765 43210</p>
                    <p class="flex items-start gap-3"><span class="mt-0.5 text-brand-700">✉️</span> hello@travelera.test</p>
                </div>
            </div>
            <form method="POST" action="{{ route('contact.store') }}" class="site-form rounded-[28px] bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 sm:p-8">
                @csrf
                <input type="hidden" name="intent" value="contact">
                <div class="grid gap-4 sm:grid-cols-2">
                    <input name="name" required placeholder="Full Name" class="rounded-xl border-slate-200 text-sm">
                    <input name="phone" placeholder="Phone" class="rounded-xl border-slate-200 text-sm">
                </div>
                <input type="email" name="email" required placeholder="Email" class="mt-4 w-full rounded-xl border-slate-200 text-sm">
                <textarea name="message" rows="5" placeholder="How can we help?" class="mt-4 w-full rounded-xl border-slate-200 text-sm"></textarea>
                <button class="mt-5 w-full rounded-full bg-brand-700 py-3 text-sm font-semibold text-white hover:bg-brand-800">Submit</button>
            </form>
        </div>
    </section>

<style>
    header.font-menu {
        position: absolute;
        width: 100%;
        top: 40px;
        background: transparent;
        box-shadow: none;
    }
    header.font-menu .mx-auto {
        background: #fff;
        border-radius: 8px;
    }
    @media (max-width: 767px) {
        header.font-menu {
            top: 12px;
            padding: 0 12px;
        }
        header.font-menu .mx-auto {
            border-radius: 12px;
        }
    }
</style>


</x-public-layout>
