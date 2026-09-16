<x-admin-layout title="Packages">
    <div class="admin-panel-head mb-4">
        <h2 class="admin-panel-title" style="margin:0">Holiday packages</h2>
        <a href="{{ route('admin.packages.create') }}" class="admin-btn">Add package</a>
    </div>

    <div class="admin-panel">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Destination</th>
                        <th>Days</th>
                        <th>Price</th>
                        <th>Featured</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($packages as $package)
                        <tr>
                            <td>{{ $package->title }}</td>
                            <td>{{ $package->destination?->city }}</td>
                            <td>{{ $package->duration_days }}</td>
                            <td>₹{{ number_format((float) $package->price, 0) }}</td>
                            <td>{{ $package->is_featured ? 'Yes' : 'No' }}</td>
                            <td class="admin-actions">
                                <a href="{{ route('admin.packages.edit', $package) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" onsubmit="return confirm('Delete this package?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $packages->links() }}</div>
    </div>
</x-admin-layout>
