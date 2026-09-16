<x-admin-layout title="Flight searches">
    <div class="admin-stats admin-stats-compact">
        <div class="admin-stat"><span>All</span><strong>{{ number_format($totals['all']) }}</strong></div>
        <div class="admin-stat"><span>Today</span><strong>{{ number_format($totals['today']) }}</strong></div>
        <div class="admin-stat"><span>Last 7 days</span><strong>{{ number_format($totals['week']) }}</strong></div>
        <div class="admin-stat admin-stat-warn"><span>Errors</span><strong>{{ number_format($totals['errors']) }}</strong></div>
    </div>

    <form method="GET" class="admin-filters">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Origin or destination">
        <button type="submit">Search</button>
    </form>

    <div class="admin-panel">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Route</th>
                        <th>Travel date</th>
                        <th>Adults</th>
                        <th>Results</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($searches as $search)
                        <tr>
                            <td>{{ $search->created_at->format('d M H:i') }}</td>
                            <td>{{ $search->user?->email ?? 'Guest' }}</td>
                            <td>{{ $search->origin }} → {{ $search->destination }}</td>
                            <td>{{ optional($search->departure_date)->format('d M Y') ?? '—' }}</td>
                            <td>{{ $search->adults }}</td>
                            <td>{{ $search->results_count }}{{ $search->had_error ? ' · error' : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="admin-empty">No searches logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $searches->links() }}</div>
    </div>
</x-admin-layout>
