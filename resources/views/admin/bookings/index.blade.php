<x-admin-layout title="Bookings">
    <form method="GET" class="admin-filters">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Ref, guest, email">
        <select name="status">
            <option value="">All statuses</option>
            @foreach (['confirmed', 'cancelled', 'pending'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <select name="payment_status">
            <option value="">All payments</option>
            @foreach (['paid', 'pending', 'failed', 'refunded', 'abandoned'] as $status)
                <option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit">Filter</button>
    </form>

    <div class="admin-panel">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Guest</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Amount</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->booking_reference }}</td>
                            <td>
                                <div>{{ $booking->guest_name }}</div>
                                <div class="admin-muted">{{ $booking->guest_email }}</div>
                            </td>
                            <td>{{ $booking->typeLabel() }}</td>
                            <td><span class="admin-badge admin-badge-{{ $booking->status }}">{{ $booking->status }}</span></td>
                            <td><span class="admin-badge admin-badge-{{ $booking->payment_status }}">{{ $booking->payment_status }}</span></td>
                            <td>{{ $booking->currency }} {{ number_format((float) $booking->total_amount, 0) }}</td>
                            <td><a href="{{ route('admin.bookings.show', $booking) }}">Manage</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="admin-empty">No bookings found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $bookings->links() }}</div>
    </div>
</x-admin-layout>
