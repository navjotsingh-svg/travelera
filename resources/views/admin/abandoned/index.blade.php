<x-admin-layout title="Abandoned payments">
    <div class="admin-panel-head mb-4">
        <p class="admin-muted">{{ $count }} abandoned checkout{{ $count === 1 ? '' : 's' }} (started 30+ minutes ago, not completed).</p>
        <div class="admin-filters" style="margin:0">
            <a class="admin-btn-secondary {{ ($filter ?? '') !== 'all' ? 'is-active' : '' }}" href="{{ route('admin.abandoned.index') }}">Abandoned</a>
            <a class="admin-btn-secondary {{ ($filter ?? '') === 'all' ? 'is-active' : '' }}" href="{{ route('admin.abandoned.index', ['filter' => 'all']) }}">All open</a>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Flight</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr>
                            <td>{{ $attempt->user?->email ?? 'Guest' }}</td>
                            <td>{{ $attempt->title() }}</td>
                            <td>{{ $attempt->currency }} {{ number_format((float) $attempt->amount, 0) }}</td>
                            <td><span class="admin-badge">{{ $attempt->status }}</span></td>
                            <td>{{ $attempt->created_at->diffForHumans() }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.abandoned.update', $attempt) }}" class="admin-inline-form">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status">
                                        @foreach (['started', 'abandoned', 'completed', 'cancelled'] as $status)
                                            <option value="{{ $status }}" @selected($attempt->status === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit">Update</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No abandoned payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $attempts->links() }}</div>
    </div>
</x-admin-layout>
