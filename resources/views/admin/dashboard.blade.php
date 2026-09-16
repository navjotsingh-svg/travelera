<x-admin-layout title="Dashboard">
    <div class="admin-stats">
        <a href="{{ route('admin.users.index') }}" class="admin-stat">
            <span>Users</span>
            <strong>{{ number_format($stats['users']) }}</strong>
        </a>
        <a href="{{ route('admin.bookings.index') }}" class="admin-stat">
            <span>Bookings</span>
            <strong>{{ number_format($stats['bookings']) }}</strong>
        </a>
        <a href="{{ route('admin.bookings.index', ['payment_status' => 'paid']) }}" class="admin-stat">
            <span>Paid revenue</span>
            <strong>₹{{ number_format($stats['revenue'], 0) }}</strong>
        </a>
        <a href="{{ route('admin.abandoned.index') }}" class="admin-stat admin-stat-warn">
            <span>Abandoned</span>
            <strong>{{ number_format($stats['abandoned']) }}</strong>
        </a>
        <a href="{{ route('admin.searches.index') }}" class="admin-stat">
            <span>Searches</span>
            <strong>{{ number_format($stats['searches']) }}</strong>
            <em>{{ number_format($stats['searches_today']) }} today</em>
        </a>
        <a href="{{ route('admin.packages.index') }}" class="admin-stat">
            <span>Packages</span>
            <strong>{{ number_format($stats['packages']) }}</strong>
        </a>
        <a href="{{ route('admin.blogs.index') }}" class="admin-stat">
            <span>Blog posts</span>
            <strong>{{ number_format($stats['blogs']) }}</strong>
            <em>{{ number_format($stats['published_blogs']) }} published</em>
        </a>
        <a href="{{ route('admin.bookings.index', ['payment_status' => 'paid']) }}" class="admin-stat">
            <span>Paid bookings</span>
            <strong>{{ number_format($stats['paid']) }}</strong>
        </a>
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
                                <td><span class="admin-badge admin-badge-{{ $booking->payment_status }}">{{ $booking->payment_status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="admin-empty">No bookings yet.</td></tr>
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
                            <tr><td colspan="3" class="admin-empty">No searches logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="admin-panel admin-panel-spaced">
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
                        <tr><td colspan="4" class="admin-empty">No abandoned checkouts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
