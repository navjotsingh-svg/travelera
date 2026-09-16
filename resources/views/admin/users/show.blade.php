<x-admin-layout title="User · {{ $user->name }}">
    <div class="admin-panel mb-5">
        <dl class="admin-dl">
            <div><dt>Name</dt><dd>{{ $user->name }}</dd></div>
            <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
            <div><dt>Phone</dt><dd>{{ $user->phone ?: '—' }}</dd></div>
            <div><dt>Role</dt><dd>{{ $user->is_admin ? 'Admin' : 'User' }}</dd></div>
            <div><dt>Joined</dt><dd>{{ $user->created_at->format('d M Y H:i') }}</dd></div>
        </dl>
    </div>

    <section class="admin-panel">
        <div class="admin-panel-head"><h2>Recent bookings</h2></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($user->bookings as $booking)
                        <tr>
                            <td><a href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->booking_reference }}</a></td>
                            <td>{{ $booking->title() }}</td>
                            <td>{{ $booking->status }}</td>
                            <td>{{ $booking->payment_status }}</td>
                            <td>{{ $booking->currency }} {{ number_format((float) $booking->total_amount, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No bookings.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
