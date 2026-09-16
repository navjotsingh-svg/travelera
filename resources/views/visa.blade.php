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
        <div class="mt-12 max-w-xl rounded-[28px] bg-white p-6 shadow-sm ring-1 ring-slate-100">
            <x-contact-form
                intent="visa"
                submit-label="Submit"
                :show-title="true"
                title="Ask for a visa check"
            />
        </div>
    </section>
</x-public-layout>
