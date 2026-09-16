@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' · Travelera' : 'Travelera · Fly Beyond Boundaries' }}</title>
        <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|roboto:400,500,700,900&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/css/custom.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-white text-slate-800">
        <header x-data="{ open: false }" class="site-header sticky top-0 z-50 bg-white font-menu shadow-sm">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
                <a href="{{ route('home') }}" class="shrink-0">
                    <x-application-logo class="h-10 w-auto sm:h-11" />
                </a>

                <nav class="site-nav hidden items-center gap-8 text-[15px] font-medium text-slate-600 lg:flex">
                    <a class="{{ request()->routeIs('home') ? 'is-active text-brand-700' : 'hover:text-brand-700' }}" href="{{ route('home') }}">Home</a>
                    <a class="{{ request()->routeIs('about') ? 'is-active text-brand-700' : 'hover:text-brand-700' }}" href="{{ route('about') }}">About</a>
                    <a class="hover:text-brand-700" href="{{ url('/#services') }}">Services</a>
                    <a class="{{ request()->routeIs('visa') ? 'is-active text-brand-700' : 'hover:text-brand-700' }}" href="{{ route('visa') }}">Visa Services</a>
                    <a class="{{ request()->routeIs('blogs.*') ? 'is-active text-brand-700' : 'hover:text-brand-700' }}" href="{{ route('blogs.index') }}">Blog</a>
                    <a class="hover:text-brand-700" href="{{ url('/#contact') }}">Contact</a>
                </nav>

                <div class="flex items-center gap-3">
                    <a href="{{ url('/#search') }}" class="hidden h-10 w-10 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 sm:inline-flex" aria-label="Search">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                    </a>
                    @auth
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="hidden text-sm font-semibold text-slate-600 hover:text-brand-700 sm:inline">Admin</a>
                        @endif
                        <a href="{{ route('bookings.index') }}" class="hidden text-sm font-semibold text-slate-600 hover:text-brand-700 sm:inline">My trips</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-full bg-brand-700 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-800">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('register') }}" class="hidden text-sm font-semibold text-slate-600 hover:text-brand-700 sm:inline">Sign up</a>
                        <a href="{{ route('login') }}" class="btn-login rounded-full bg-brand-700 px-5 py-2 text-sm font-semibold text-white hover:bg-brand-800">Login</a>
                    @endauth
                    <button @click="open = ! open" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Menu">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
            <div x-show="open" x-cloak class="border-t border-slate-100 bg-white px-4 py-4 lg:hidden">
                <div class="grid gap-3 text-sm font-medium text-slate-700">
                    <a href="{{ route('home') }}">Home</a>
                    <a href="{{ route('about') }}">About</a>
                    <a href="{{ url('/#services') }}">Services</a>
                    <a href="{{ route('visa') }}">Visa Services</a>
                    <a href="{{ route('blogs.index') }}">Blog</a>
                    <a href="{{ url('/#contact') }}">Contact</a>
                    <a href="{{ route('flights.index') }}">Flights</a>
                    <a href="{{ route('hotels.index') }}">Hotels</a>
                    @auth
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}">Admin</a>
                        @endif
                    @endauth
                    @guest
                        <a href="{{ route('login') }}">Login</a>
                        <a href="{{ route('register') }}">Sign up</a>
                    @endguest
                </div>
            </div>
        </header>

        <main>
            @if (session('status') && request()->routeIs('home', 'about', 'visa'))
                <div class="bg-brand-50 px-4 py-3 text-center text-sm font-medium text-brand-800">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>

        <footer class="site-footer bg-[#0b1f5c] text-blue-100">
            <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                <div class="flex flex-col gap-6 border-b border-white/10 pb-8 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-lg font-bold text-white">Stay in the loop</p>
                        <p class="mt-1 text-sm text-blue-200">Trip deals, visa tips and destination ideas — no spam.</p>
                    </div>
                    <form method="POST" action="{{ route('contact.store') }}" class="flex w-full max-w-md gap-2">
                        @csrf
                        <input type="hidden" name="intent" value="newsletter">
                        <input type="email" name="email" required placeholder="Your email" class="w-full rounded-full border-0 bg-white px-4 py-2.5 text-sm text-slate-800">
                        <button class="shrink-0 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-brand-800 hover:bg-blue-50">Subscribe</button>
                    </form>
                </div>

                <div class="grid gap-10 py-10 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="lg:col-span-1">
                        <a href="{{ route('home') }}" class="inline-block rounded-xl bg-white px-3 py-2">
                            <x-application-logo class="h-10 w-auto" />
                        </a>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-[0.18em] text-white">EXPLORE</p>
                        <div class="mt-4 grid gap-2 text-sm">
                            <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                            <a href="{{ route('about') }}" class="hover:text-white">About</a>
                            <a href="{{ url('/#contact') }}" class="hover:text-white">Contact</a>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-[0.18em] text-white">SERVICES</p>
                        <div class="mt-4 grid gap-2 text-sm">
                            <a href="{{ route('flights.index') }}" class="hover:text-white">Flights</a>
                            <a href="{{ route('hotels.index') }}" class="hover:text-white">Hotels</a>
                            <a href="{{ route('packages.index') }}" class="hover:text-white">Holiday packages</a>
                            <a href="{{ route('cabs.index') }}" class="hover:text-white">Cabs</a>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-[0.18em] text-white">SUPPORT</p>
                        <div class="mt-4 grid gap-2 text-sm">
                            <a href="{{ route('visa') }}" class="hover:text-white">Visa services</a>
                            <a href="{{ url('/#contact') }}" class="hover:text-white">Help desk</a>
                            <span>24×7 booking desk</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold tracking-[0.18em] text-white">COMPANY</p>
                        <div class="mt-4 grid gap-2 text-sm">
                            <a href="{{ route('about') }}" class="hover:text-white">Our story</a>
                            <a href="{{ route('login') }}" class="hover:text-white">Login</a>
                            <a href="{{ route('register') }}" class="hover:text-white">Register</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10 py-4 text-center text-xs text-blue-300">
                © {{ date('Y') }} Travelera. Your journey, our era.
            </div>
        </footer>
        <style>[x-cloak]{display:none !important}</style>
    </body>
</html>
