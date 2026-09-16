<x-admin-layout title="Booking · {{ $booking->booking_reference }}">
    <div class="admin-grid-2">
        <section class="admin-panel">
            <h2 class="admin-panel-title">Details</h2>
            <dl class="admin-dl">
                <div><dt>Reference</dt><dd>{{ $booking->booking_reference }}</dd></div>
                <div><dt>Title</dt><dd>{{ $booking->title() }}</dd></div>
                <div><dt>Type</dt><dd>{{ $booking->typeLabel() }}</dd></div>
                <div><dt>User</dt><dd>{{ $booking->user?->email ?? '—' }}</dd></div>
                <div><dt>Guest</dt><dd>{{ $booking->guest_name }} · {{ $booking->guest_email }}</dd></div>
                <div><dt>Travel date</dt><dd>{{ optional($booking->travel_date)->format('d M Y') ?? '—' }}</dd></div>
                <div><dt>Departure</dt><dd>{{ optional($booking->departureAt())->format('d M Y H:i') ?? '—' }}</dd></div>
                <div><dt>Arrival</dt><dd>{{ optional($booking->arrivalAt())->format('d M Y H:i') ?? '—' }}</dd></div>
                <div><dt>Amount</dt><dd>{{ $booking->currency }} {{ number_format((float) $booking->total_amount, 2) }}</dd></div>
                <div><dt>Provider</dt><dd>{{ $booking->provider }}</dd></div>
                <div><dt>PNR</dt><dd>{{ $booking->airline_pnr ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="admin-panel">
            <h2 class="admin-panel-title">Update status</h2>
            <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="admin-form">
                @csrf
                @method('PATCH')
                <label>Booking status
                    <select name="status">
                        @foreach (['confirmed', 'cancelled', 'pending'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $booking->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Payment status
                    <select name="payment_status">
                        @foreach (['paid', 'pending', 'failed', 'refunded', 'abandoned'] as $status)
                            <option value="{{ $status }}" @selected(old('payment_status', $booking->payment_status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Notes
                    <textarea name="notes" rows="4">{{ old('notes', $booking->notes) }}</textarea>
                </label>
                <button type="submit" class="admin-btn">Save changes</button>
            </form>
        </section>
    </div>
</x-admin-layout>
