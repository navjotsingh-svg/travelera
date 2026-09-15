<x-public-layout title="About">
    <section class="bg-gradient-to-r from-[#0a2a8f] to-[#2b6bff] py-16 text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <p class="text-xs font-bold tracking-[0.22em] text-blue-200">ABOUT TRAVELERA</p>
            <h1 class="mt-3 text-4xl font-extrabold">Your journey, our era.</h1>
            <p class="mt-4 max-w-2xl text-blue-100">We started Travelera to make flights, hotels, cabs and visas feel like one trip — not four different checkouts.</p>
        </div>
    </section>
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="grid gap-10 lg:grid-cols-2">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900">How we work</h2>
                <p class="mt-4 leading-7 text-slate-600">Search live airline inventory, lock a stay, add an airport cab, and ask us for visa help if the route needs it. The same desk follows the booking from quote to boarding pass.</p>
                <div class="mt-8 grid grid-cols-2 gap-6">
                    <div><p class="text-3xl font-extrabold text-brand-700">12+</p><p class="text-sm text-slate-500">Years of care</p></div>
                    <div><p class="text-3xl font-extrabold text-brand-700">50+</p><p class="text-sm text-slate-500">Destinations</p></div>
                    <div><p class="text-3xl font-extrabold text-brand-700">10k+</p><p class="text-sm text-slate-500">Happy clients</p></div>
                    <div><p class="text-3xl font-extrabold text-brand-700">24/7</p><p class="text-sm text-slate-500">Support</p></div>
                </div>
            </div>
            <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80" alt="Travelera team" class="h-80 w-full rounded-3xl object-cover">
        </div>
        <a href="{{ url('/#contact') }}" class="mt-12 inline-flex rounded-full bg-brand-700 px-6 py-3 text-sm font-semibold text-white">Talk to us</a>
    </section>
</x-public-layout>
