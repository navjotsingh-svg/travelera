<x-admin-layout title="Dashboard">
    <div class="admin-stats">
        <div class="admin-stat"><span>Users</span><strong>{{ number_format($stats['users']) }}</strong></div>
        <div class="admin-stat"><span>Bookings</span><strong>{{ number_format($stats['bookings']) }}</strong></div>
        <div class="admin-stat"><span>Paid</span><strong>{{ number_format($stats['paid']) }}</strong></div>
        <div class="admin-stat"><span>Revenue</span><strong>₹{{ number_format($stats['revenue'], 0) }}</strong></div>
        <div class="admin-stat"><span>Searches</span><strong>{{ number_format($stats['searches']) }}</strong></div>
        <div class="admin-stat"><span>Searches today</span><strong>{{ number_format($stats['searches_today']) }}</strong></div>
        <div class="admin-stat"><span>Abandoned</span><strong>{{ number_format($stats['abandoned']) }}</strong></div>
        <div class="admin-stat"><span>Packages</span><strong>{{ number_format($stats['packages']) }}</strong></div>
        <div class="admin-stat"><span>Blogs</span><strong>{{ number_format($stats['blogs']) }}</strong></div>
    </div>

    <div class="admin-grid-2">
        <section class="admin-panel">
            <div class="admin-panel-head">
                <h2>Recent bookings</h2>
                <a href="{{ route('admin.bookings.index') }}">View all</a>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Guest</th>
                            <th>Amount</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentBookings as $booking)
                            <tr>
                                <td><a href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->booking_reference }}</a></td>
                                <td>{{ $booking->guest_name }}</td>
                                <td>{{ $booking->currency }} {{ number_format((float) $booking->total_amount, 0) }}</td>
                                <td><span class="admin-badge">{{ $booking->payment_status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No bookings yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-head">
                <h2>Recent searches</h2>
                <a href="{{ route('admin.searches.index') }}">View all</a>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>Date</th>
                            <th>Results</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSearches as $search)
                            <tr>
                                <td>{{ $search->origin }} → {{ $search->destination }}</td>
                                <td>{{ optional($search->departure_date)->format('d M Y') ?? '—' }}</td>
                                <td>{{ $search->results_count }}{{ $search->had_error ? ' · error' : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No searches logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="admin-panel mt-5">
        <div class="admin-panel-head">
            <h2>Abandoned payments</h2>
            <a href="{{ route('admin.abandoned.index') }}">View all</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Flight</th>
                        <th>Amount</th>
                        <th>Started</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($abandoned as $attempt)
                        <tr>
                            <td>{{ $attempt->user?->email ?? 'Guest' }}</td>
                            <td>{{ $attempt->title() }}</td>
                            <td>{{ $attempt->currency }} {{ number_format((float) $attempt->amount, 0) }}</td>
                            <td>{{ $attempt->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No abandoned checkouts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
