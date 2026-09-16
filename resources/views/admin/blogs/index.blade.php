<x-admin-layout title="Blogs">
    <div class="admin-toolbar">
        <h2 class="admin-panel-title">Content / blog posts</h2>
        <a href="{{ route('admin.blogs.create') }}" class="admin-btn">New post</a>
    </div>

    <div class="admin-panel">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Published</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blogs as $blog)
                        <tr>
                            <td>{{ $blog->title }}</td>
                            <td>{{ $blog->author?->name ?? '—' }}</td>
                            <td><span class="admin-badge {{ $blog->is_published ? 'admin-badge-paid' : 'admin-badge-pending' }}">{{ $blog->is_published ? 'Published' : 'Draft' }}</span></td>
                            <td>{{ optional($blog->published_at)->format('d M Y') ?? '—' }}</td>
                            <td class="admin-actions">
                                <a href="{{ route('admin.blogs.edit', $blog) }}">Edit</a>
                                @if ($blog->is_published)
                                    <a href="{{ route('blogs.show', $blog) }}" target="_blank">View</a>
                                @endif
                                <form method="POST" action="{{ route('admin.blogs.destroy', $blog) }}" onsubmit="return confirm('Delete this post?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="admin-empty">No blog posts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $blogs->links() }}</div>
    </div>
</x-admin-layout>
