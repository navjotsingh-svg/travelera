<x-public-layout title="Contact">
    <section class="bg-gradient-to-r from-[#0a2a8f] to-[#2b6bff] py-16 text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <p class="text-xs font-bold tracking-[0.22em] text-blue-200">CONTACT</p>
            <h1 class="mt-3 text-4xl font-extrabold">Let’s plan your next journey</h1>
            <p class="mt-4 max-w-2xl text-blue-100">Tell us what you need — flights, stays, packages or visa help. Our desk replies on email and phone.</p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="grid items-start gap-10 lg:grid-cols-2">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900">Reach Travelera</h2>
                <div class="mt-8 space-y-5 text-sm text-slate-600">
                    <p class="flex items-start gap-3"><span class="mt-0.5 text-brand-700">📍</span> 30 N Gould St Ste 4000, Sheridan, WY 82801</p>
                    <p class="flex items-start gap-3"><span class="mt-0.5 text-brand-700">📞</span> <a href="tel:+18886526415" class="hover:text-brand-700">+1 888 652 6415</a></p>
                    <p class="flex items-start gap-3"><span class="mt-0.5 text-brand-700">✉️</span> <a href="mailto:support@travelera.us" class="hover:text-brand-700">support@travelera.us</a></p>
                </div>
                <p class="mt-8 max-w-md text-sm leading-6 text-slate-500">Queries submitted here are emailed to our support team and appear in the admin inbox.</p>
            </div>

            <div class="rounded-[28px] bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] ring-1 ring-slate-100 sm:p-8">
                <x-contact-form intent="contact" submit-label="Send message" />
            </div>
        </div>
    </section>
</x-public-layout>
