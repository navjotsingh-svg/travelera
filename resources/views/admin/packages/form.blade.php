@php
    $isEdit = isset($package);
    $action = $isEdit ? route('admin.packages.update', $package) : route('admin.packages.store');
@endphp

<x-admin-layout :title="$isEdit ? 'Edit package' : 'Add package'">
    <form method="POST" action="{{ $action }}" class="admin-panel admin-form">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <label>Destination
            <select name="destination_id" required>
                @foreach ($destinations as $destination)
                    <option value="{{ $destination->id }}" @selected(old('destination_id', $package->destination_id ?? null) == $destination->id)>
                        {{ $destination->city }} · {{ $destination->country }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>Title
            <input type="text" name="title" value="{{ old('title', $package->title ?? '') }}" required>
        </label>

        <div class="admin-form-row">
            <label>Duration (days)
                <input type="number" name="duration_days" min="1" value="{{ old('duration_days', $package->duration_days ?? 3) }}" required>
            </label>
            <label>Price (INR)
                <input type="number" step="0.01" name="price" min="0" value="{{ old('price', $package->price ?? '') }}" required>
            </label>
        </div>

        <label>Description
            <textarea name="description" rows="5" required>{{ old('description', $package->description ?? '') }}</textarea>
        </label>

        <label>Includes (one per line)
            <textarea name="includes_text" rows="4">{{ old('includes_text', isset($package) ? implode("\n", $package->includes ?? []) : '') }}</textarea>
        </label>

        <label>Image URL
            <input type="url" name="image" value="{{ old('image', $package->image ?? '') }}" required>
        </label>

        <label class="admin-check">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $package->is_featured ?? false))>
            Featured package
        </label>

        <div class="admin-form-actions">
            <button type="submit" class="admin-btn">{{ $isEdit ? 'Update' : 'Create' }}</button>
            <a href="{{ route('admin.packages.index') }}" class="admin-btn-secondary">Cancel</a>
        </div>
    </form>
</x-admin-layout>
