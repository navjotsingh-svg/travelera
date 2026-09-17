<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Support\PublicUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $blogs = Blog::query()
            ->with('author')
            ->latest()
            ->paginate(20);

        return view('admin.blogs.index', compact('blogs'));
    }

    public function create(): View
    {
        return view('admin.blogs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['user_id'] = $request->user()->id;
        $validated['slug'] = $this->uniqueSlug($validated['title']);
        $validated['is_published'] = $request->boolean('is_published');
        $validated['published_at'] = $validated['is_published'] ? now() : null;
        $validated['cover_image'] = $this->storeCoverImage($request);

        Blog::query()->create($validated);

        return redirect()->route('admin.blogs.index')->with('status', 'Blog post created.');
    }

    public function edit(Blog $blog): View
    {
        return view('admin.blogs.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog): RedirectResponse
    {
        $validated = $this->validated($request, $blog);
        $validated['is_published'] = $request->boolean('is_published');

        if ($validated['is_published'] && ! $blog->published_at) {
            $validated['published_at'] = now();
        }

        if (! $validated['is_published']) {
            $validated['published_at'] = null;
        }

        if ($request->hasFile('cover_image')) {
            $this->deleteStoredImage($blog->cover_image);
            $validated['cover_image'] = $this->storeCoverImage($request);
        } else {
            unset($validated['cover_image']);
        }

        if ($request->boolean('remove_cover_image') && ! $request->hasFile('cover_image')) {
            $this->deleteStoredImage($blog->cover_image);
            $validated['cover_image'] = null;
        }

        $blog->update($validated);

        return redirect()->route('admin.blogs.index')->with('status', 'Blog post updated.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $this->deleteStoredImage($blog->cover_image);
        $blog->delete();

        return redirect()->route('admin.blogs.index')->with('status', 'Blog post deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Blog $blog = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'remove_cover_image' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
        ]);
    }

    private function storeCoverImage(Request $request): ?string
    {
        if (! $request->hasFile('cover_image')) {
            return null;
        }

        return PublicUpload::store($request->file('cover_image'), 'blogs');
    }

    private function deleteStoredImage(?string $image): void
    {
        if (! $image) {
            return;
        }

        PublicUpload::delete($image);

        $path = $image;

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '//')) {
            $path = parse_url($image, PHP_URL_PATH) ?: '';
        }

        $path = ltrim((string) $path, '/');

        if (str_starts_with($path, 'uploads/')) {
            return;
        }

        if (str_contains($path, '/storage/')) {
            $path = preg_replace('#^.*?/storage/#', '', $path) ?? $path;
        }

        $path = str_replace('storage/', '', $path);
        $path = ltrim($path, '/');

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i = 1;

        while (
            Blog::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
