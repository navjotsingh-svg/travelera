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
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?: '—' }}</td>
                            <td>{{ $user->bookings_count }}</td>
                            <td>{{ $user->is_admin ? 'Admin' : 'User' }}</td>
                            <td><a href="{{ route('admin.users.show', $user) }}">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $users->links() }}</div>
    </div>
</x-admin-layout>
