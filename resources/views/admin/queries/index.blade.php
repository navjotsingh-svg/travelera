<x-admin-layout title="Queries">
    <div class="admin-stats admin-stats-compact">
        <div class="admin-stat"><span>All</span><strong>{{ number_format($totals['all']) }}</strong></div>
        <div class="admin-stat admin-stat-warn"><span>New</span><strong>{{ number_format($totals['new']) }}</strong></div>
        <div class="admin-stat"><span>Today</span><strong>{{ number_format($totals['today']) }}</strong></div>
    </div>

    <form method="GET" class="admin-filters">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email, phone, message">
        <select name="intent">
            <option value="">All types</option>
            @foreach (['contact' => 'Contact', 'enquiry' => 'Trip enquiry', 'visa' => 'Visa', 'newsletter' => 'Newsletter'] as $value => $label)
                <option value="{{ $value }}" @selected(request('intent') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">All statuses</option>
            @foreach (['new', 'read', 'replied', 'closed'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit">Filter</button>
    </form>

    <div class="admin-panel">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Type</th>
                        <th>From</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($queries as $query)
                        <tr>
                            <td>{{ $query->created_at->format('d M H:i') }}</td>
                            <td>{{ $query->intentLabel() }}</td>
                            <td>
                                <div>{{ $query->name ?: '—' }}</div>
                                <div class="admin-muted">{{ $query->email }}</div>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($query->message, 60) ?: '—' }}</td>
                            <td><span class="admin-badge admin-badge-{{ $query->status === 'new' ? 'pending' : 'paid' }}">{{ $query->status }}</span></td>
                            <td class="admin-actions">
                                <a href="{{ route('admin.queries.show', $query) }}">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="admin-empty">No queries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $queries->links() }}</div>
    </div>
</x-admin-layout>
