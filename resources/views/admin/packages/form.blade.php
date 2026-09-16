@php
    $isEdit = isset($package);
    $action = $isEdit ? route('admin.packages.update', $package) : route('admin.packages.store');
    $currentImage = old('image', $package->image ?? '');
    $defaultMode = filled($currentImage) && ! str_starts_with((string) $currentImage, 'http') && ! str_starts_with((string) $currentImage, '//')
        ? 'upload'
        : 'url';
@endphp

<x-admin-layout :title="$isEdit ? 'Edit package' : 'Add package'">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="admin-panel admin-form" x-data="{ imageMode: '{{ old('image_mode', $defaultMode) }}' }">
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

        <div class="admin-image-field">
            <span class="admin-image-label">Package image</span>
            <input type="hidden" name="image_mode" :value="imageMode">

            <div class="admin-image-tabs">
                <button type="button" class="admin-btn-secondary" :class="imageMode === 'url' && 'is-active'" @click="imageMode = 'url'">Image link</button>
                <button type="button" class="admin-btn-secondary" :class="imageMode === 'upload' && 'is-active'" @click="imageMode = 'upload'">Upload file</button>
            </div>

            <div x-show="imageMode === 'url'" x-cloak>
                <label>Image URL
                    <input type="url" name="image" value="{{ $currentImage }}">
                </label>
                <p class="admin-muted">Paste a full image URL (https://…).</p>
            </div>

            <div x-show="imageMode === 'upload'" x-cloak>
                <label>Upload image
                    <input type="file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif">
                </label>
                <p class="admin-muted">JPG, PNG, WEBP or GIF up to 4 MB.{{ $isEdit ? ' Leave empty to keep the current image.' : '' }}</p>
            </div>

            @if ($isEdit && filled($package->image))
                <div class="admin-image-preview">
                    <img src="{{ $package->imageSrc() }}" alt="Current package image">
                    <span class="admin-muted">Current image</span>
                </div>
            @endif

            <x-input-error :messages="$errors->get('image')" class="mt-2" />
            <x-input-error :messages="$errors->get('image_file')" class="mt-2" />
        </div>

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
