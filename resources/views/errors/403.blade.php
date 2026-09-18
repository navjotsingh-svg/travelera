<x-public-layout title="Access denied">
    <section class="relative overflow-hidden bg-gradient-to-r from-[#0a2a8f] via-brand-700 to-[#2b6bff] py-16 text-white">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/10 blur-2xl"></div>
        <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
            <p class="text-xs font-bold tracking-[0.22em] text-blue-200">ERROR 403</p>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl">Access denied</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-blue-100">
                {{ $exception->getMessage() ?: 'You do not have permission to view this page.' }}
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="max-w-xl rounded-[28px] bg-white p-8 shadow-[0_20px_60px_rgba(15,23,42,0.08)] ring-1 ring-slate-100">
            <p class="text-sm leading-7 text-slate-600">
                @auth
                    You’re signed in as <strong class="text-slate-900">{{ auth()->user()->email }}</strong>, but this area is limited to Travelera admins.
                @else
                    Please sign in with an admin account if you need access to this area.
                @endauth
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('home') }}" class="inline-flex rounded-full bg-brand-700 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-800">
                    Back to home
                </a>
                @auth
                    <a href="{{ route('bookings.index') }}" class="inline-flex rounded-full border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        My trips
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex rounded-full border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Login
                    </a>
                @endauth
                <a href="{{ route('contact') }}" class="inline-flex rounded-full px-6 py-3 text-sm font-semibold text-brand-700 hover:bg-brand-50">
                    Contact support
                </a>
            </div>
        </div>
    </section>
</x-public-layout>
