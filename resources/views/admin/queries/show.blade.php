<x-admin-layout title="Query #{{ $query->id }}">
    <div class="admin-toolbar">
        <h2 class="admin-panel-title">{{ $query->intentLabel() }}</h2>
        <a href="{{ route('admin.queries.index') }}" class="admin-btn-secondary">Back</a>
    </div>

    <div class="admin-panel admin-detail">
        <dl>
            <div><dt>Status</dt><dd>{{ ucfirst($query->status) }}</dd></div>
            <div><dt>Name</dt><dd>{{ $query->name ?: '—' }}</dd></div>
            <div><dt>Email</dt><dd><a href="mailto:{{ $query->email }}">{{ $query->email }}</a></dd></div>
            <div><dt>Phone</dt><dd>{{ $query->phone ?: '—' }}</dd></div>
            <div><dt>Received</dt><dd>{{ $query->created_at->format('d M Y H:i') }}</dd></div>
            <div><dt>IP</dt><dd>{{ $query->ip_address ?: '—' }}</dd></div>
        </dl>

        <div class="admin-message-box">
            <p class="admin-muted">Message</p>
            <p>{{ $query->message ?: '—' }}</p>
        </div>

        <form method="POST" action="{{ route('admin.queries.update', $query) }}" class="admin-form-inline">
            @csrf
            @method('PATCH')
            <label>Update status
                <select name="status">
                    @foreach (['new', 'read', 'replied', 'closed'] as $status)
                        <option value="{{ $status }}" @selected($query->status === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="admin-btn">Save</button>
        </form>

        <form method="POST" action="{{ route('admin.queries.destroy', $query) }}" onsubmit="return confirm('Delete this query?')" class="mt-4">
            @csrf
            @method('DELETE')
            <button type="submit" class="admin-btn-secondary">Delete</button>
        </form>
    </div>
</x-admin-layout>
