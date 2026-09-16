<x-admin-layout title="Users">
    <form method="GET" class="admin-filters">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email, phone">
        <button type="submit">Search</button>
    </form>

    <div class="admin-panel">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Bookings</th>
                        <th>Role</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?: '—' }}</td>
                            <td>{{ $user->bookings_count }}</td>
                            <td><span class="admin-badge {{ $user->is_admin ? 'admin-badge-paid' : '' }}">{{ $user->is_admin ? 'Admin' : 'User' }}</span></td>
                            <td><a href="{{ route('admin.users.show', $user) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="admin-empty">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $users->links() }}</div>
    </div>
</x-admin-layout>
