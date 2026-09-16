@php
    $isEdit = isset($blog);
    $action = $isEdit ? route('admin.blogs.update', $blog) : route('admin.blogs.store');
@endphp

<x-admin-layout :title="$isEdit ? 'Edit blog post' : 'New blog post'">
    <form method="POST" action="{{ $action }}" class="admin-panel admin-form">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <label>Title
            <input type="text" name="title" value="{{ old('title', $blog->title ?? '') }}" required>
        </label>

        <label>Excerpt
            <textarea name="excerpt" rows="2">{{ old('excerpt', $blog->excerpt ?? '') }}</textarea>
        </label>

        <label>Body
            <textarea name="body" rows="12" required>{{ old('body', $blog->body ?? '') }}</textarea>
        </label>

        <label>Cover image URL
            <input type="url" name="cover_image" value="{{ old('cover_image', $blog->cover_image ?? '') }}">
        </label>

        <label class="admin-check">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $blog->is_published ?? false))>
            Publish now
        </label>

        <div class="admin-form-actions">
            <button type="submit" class="admin-btn">{{ $isEdit ? 'Update' : 'Create' }}</button>
            <a href="{{ route('admin.blogs.index') }}" class="admin-btn-secondary">Cancel</a>
        </div>
    </form>
</x-admin-layout>
