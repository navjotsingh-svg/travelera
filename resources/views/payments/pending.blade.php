<x-public-layout title="Payment pending">
    <div class="mx-auto max-w-xl px-4 py-16 sm:px-6">
        <div class="rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-100 text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-amber-600">Payment pending</p>
            <h1 class="mt-3 text-2xl font-extrabold text-slate-900">We’re still confirming your payment</h1>
            <p class="mt-3 text-slate-500">Booking {{ $booking->booking_reference }} will update automatically once Stripe confirms the charge.</p>
            <a href="{{ route('bookings.show', $booking) }}" class="mt-8 inline-flex rounded-full bg-[#0033a0] px-6 py-3 text-sm font-semibold text-white">View booking</a>
        </div>
    </div>
</x-public-layout>
