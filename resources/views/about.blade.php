<x-public-layout title="About">
    {{-- Hero --}}
    <section class="about-hero relative overflow-hidden">
        <img
            src="{{ asset('images/sky-with-clouds-sunset-with.webp') }}"
            alt="Airplane wing above the clouds"
            class="absolute inset-0 h-full w-full object-cover"
        >
        <div class="absolute inset-0 bg-gradient-to-r from-[#04153f]/92 via-[#0a2a8f]/78 to-[#2b6bff]/45"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#04153f]/55 via-transparent to-transparent"></div>

        <div class="relative mx-auto flex min-h-[420px] max-w-6xl flex-col justify-end px-4 pb-14 pt-28 sm:min-h-[520px] sm:px-6 sm:pb-20">
            <p class="about-fade-up text-xs font-bold tracking-[0.28em] text-blue-200">ABOUT TRAVELERA</p>
            <h1 class="about-fade-up about-fade-up-delay-1 mt-4 max-w-3xl text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
                Your Journey, Our Era
            </h1>
            <p class="about-fade-up about-fade-up-delay-2 mt-5 max-w-2xl text-base leading-7 text-blue-100 sm:text-lg">
                Travel booking assistance from a U.S.-registered company — clearer options, trusted suppliers, and support that stays with you.
            </p>
            <div class="about-fade-up about-fade-up-delay-3 mt-8 flex flex-wrap gap-3">
                <a href="{{ route('contact') }}" class="inline-flex rounded-full bg-white px-6 py-3 text-sm font-semibold text-brand-700 shadow-lg shadow-blue-950/20 transition hover:-translate-y-0.5 hover:bg-blue-50">
                    Talk to us
                </a>
                <a href="{{ route('flights.index') }}" class="inline-flex rounded-full border border-white/35 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                    Search flights
                </a>
            </div>
        </div>
    </section>

    {{-- Company intro --}}
    <section class="relative overflow-hidden bg-white">
        <div class="pointer-events-none absolute -right-24 top-10 h-72 w-72 rounded-full bg-brand-soft/80 blur-3xl"></div>
        <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
                <div>
                    <p class="section-kicker">TRAVEL ERA LLC</p>
                    <h2 class="section-heading mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Operated with care in the United States</h2>
                    <p class="mt-5 text-base leading-8 text-slate-600">
                        Travelera is operated by <strong class="font-semibold text-slate-900">TRAVEL ERA LLC</strong>, a United States registered company providing travel booking assistance services.
                    </p>
                    <p class="mt-4 text-base leading-8 text-slate-600">
                        We help customers identify suitable flight options by working with airline networks and travel suppliers, ensuring a convenient and efficient booking experience.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-x-8 gap-y-4 border-t border-slate-100 pt-8 text-sm text-slate-500">
                        <p><span class="block text-xs font-bold tracking-[0.18em] text-brand-700">ENTITY</span> TRAVEL ERA LLC</p>
                        <p><span class="block text-xs font-bold tracking-[0.18em] text-brand-700">SUPPORT</span> <a href="mailto:support@travelera.us" class="hover:text-brand-700">support@travelera.us</a></p>
                        <p><span class="block text-xs font-bold tracking-[0.18em] text-brand-700">PHONE</span> <a href="tel:+18886526415" class="hover:text-brand-700">+1 888 652 6415</a></p>
                    </div>
                </div>

                <div class="about-photo-stack relative">
                    <div class="overflow-hidden rounded-[28px] shadow-[0_28px_80px_rgba(15,23,42,0.16)] ring-1 ring-slate-100">
                        <img
                            src="https://images.unsplash.com/photo-1488085061387-422e29b40080?auto=format&fit=crop&w=1400&q=80"
                            alt="Traveler looking out at a city skyline"
                            class="h-80 w-full object-cover sm:h-[420px]"
                        >
                    </div>
                    <div class="about-office-badge absolute -bottom-5 left-5 right-5 rounded-2xl bg-white/95 p-4 shadow-xl ring-1 ring-slate-100 backdrop-blur sm:left-auto sm:right-6 sm:w-64">
                        <p class="text-xs font-bold tracking-[0.18em] text-brand-700">TRAVELERA OFFICE</p>
                        <p class="mt-2 text-sm font-semibold leading-6 text-slate-900">30 N Gould St Ste 4000<br>Sheridan, WY 82801</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Who we are --}}
    <section class="bg-gradient-to-b from-slate-50 to-white">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-20">
            <div class="grid overflow-hidden rounded-[28px] bg-[#04153f] text-white shadow-[0_28px_80px_rgba(4,21,63,0.25)] lg:grid-cols-2">
                <div class="relative min-h-[280px]">
                    <img
                        src="https://images.unsplash.com/photo-1529070538774-1843cb3265df?auto=format&fit=crop&w=1400&q=80"
                        alt="Passengers boarding an aircraft"
                        class="absolute inset-0 h-full w-full object-cover opacity-80"
                    >
                    <div class="absolute inset-0 bg-gradient-to-r from-[#04153f]/20 to-[#04153f]/70 lg:bg-gradient-to-l"></div>
                </div>
                <div class="relative p-8 sm:p-12">
                    <p class="text-xs font-bold tracking-[0.22em] text-blue-200">WHO WE ARE</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Built to make travel planning easier</h2>
                    <p class="mt-5 text-base leading-8 text-blue-100">
                        Travelera was founded with a simple mission – to make travel planning easier, more transparent, and customer-friendly. We connect you with a wide range of airline options and trusted travel partners to ensure you get the best possible choices and support when you need it.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Values --}}
    <section class="mx-auto max-w-6xl px-4 pb-8 pt-4 sm:px-6 lg:pb-12">
        <div class="max-w-2xl">
            <p class="section-kicker">WHAT GUIDES US</p>
            <h2 class="section-heading mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">The Travelera standard</h2>
            <p class="mt-4 text-base leading-7 text-slate-500">Five promises behind every booking we help you make.</p>
        </div>

        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['U.S.-Registered Company', 'Travelera is a registered company in the United States (TRAVEL ERA LLC).', 'M3 21h18M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16'],
                ['Trusted Partnerships', 'We work with airline networks and accredited travel suppliers to bring you more options.', 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75'],
                ['Customer First', 'Our customers are at the heart of everything we do. We\'re here to help, before, during, and after your booking.', 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
                ['Transparent Pricing', 'We believe in clear and honest communication with no hidden fees.', 'M12 1v22M17 5H9.5a3.5 3.5 0 100 7h5a3.5 3.5 0 110 7H6'],
                ['Secure & Reliable', 'Your information and bookings are handled with the highest standards of security.', 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'],
            ] as $index => [$title, $copy, $icon])
                <article class="about-value rounded-[24px] bg-white p-6 shadow-[0_16px_50px_rgba(15,23,42,0.06)] ring-1 ring-slate-100 transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_60px_rgba(0,51,160,0.12)] {{ $index === 4 ? 'sm:col-span-2 lg:col-span-1' : '' }}">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#eef4ff] text-[#0033a0]">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                        </svg>
                    </span>
                    <h3 class="mt-5 text-lg font-bold text-slate-900">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-7 text-slate-500">{{ $copy }}</p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="about-cta relative overflow-hidden rounded-[28px] bg-gradient-to-r from-[#0a2a8f] via-brand-700 to-[#2b6bff] px-6 py-12 text-white sm:px-10 sm:py-14">
            <div class="pointer-events-none absolute -right-10 -top-10 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-16 left-10 h-48 w-48 rounded-full bg-sky-300/20 blur-2xl"></div>
            <div class="relative max-w-2xl">
                <p class="text-xs font-bold tracking-[0.22em] text-blue-200">READY WHEN YOU ARE</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Let’s plan your next journey</h2>
                <p class="mt-4 text-base leading-7 text-blue-100">
                    Share your dates and destinations — our desk will help you compare options and book with confidence.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('contact') }}" class="inline-flex rounded-full bg-white px-6 py-3 text-sm font-semibold text-brand-700 transition hover:bg-blue-50">Contact Travelera</a>
                    <a href="tel:+18886526415" class="inline-flex rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">+1 888 652 6415</a>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
