<x-public-layout title="Visa Services">
    <section class="bg-gradient-to-r from-[#0a2a8f] to-[#2b6bff] py-16 text-white">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <p class="text-xs font-bold tracking-[0.22em] text-blue-200">VISA SERVICES</p>
            <h1 class="mt-3 text-4xl font-extrabold">Paperwork, without the panic.</h1>
            <p class="mt-4 max-w-2xl text-blue-100">Tell us where you are flying. We will map documents, appointments and timelines around your ticket dates.</p>
        </div>
    </section>
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ([
                ['Tourist visas', 'Short stays for holidays, family visits and first-time trips.'],
                ['Business visas', 'Meetings, conferences and short work travel with letter support.'],
                ['Transit cover', 'Same-day and overnight connections that need a transit visa.'],
            ] as [$title, $copy])
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <p class="font-bold text-slate-900">{{ $title }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $copy }}</p>
                </div>
            @endforeach
        </div>
        <form method="POST" action="{{ route('contact.store') }}" class="mt-12 max-w-xl rounded-[28px] bg-white p-6 shadow-sm ring-1 ring-slate-100">
            @csrf
            <input type="hidden" name="intent" value="enquiry">
            <p class="font-bold text-slate-900">Ask for a visa check</p>
            <input name="name" required placeholder="Full Name" class="mt-4 w-full rounded-xl border-slate-200 text-sm">
            <input type="email" name="email" required placeholder="Email" class="mt-3 w-full rounded-xl border-slate-200 text-sm">
            <input name="phone" placeholder="Phone" class="mt-3 w-full rounded-xl border-slate-200 text-sm">
            <textarea name="message" rows="4" placeholder="Passport nationality, destination and travel dates" class="mt-3 w-full rounded-xl border-slate-200 text-sm"></textarea>
            <button class="mt-4 rounded-full bg-brand-700 px-6 py-3 text-sm font-semibold text-white">Submit</button>
        </form>
    </section>
</x-public-layout>
