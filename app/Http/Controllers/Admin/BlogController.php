<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        Blog::query()->create($validated);

        return redirect()->route('admin.blogs.index')->with('status', 'Blog post created.');
    }

    public function edit(Blog $blog): View
    {
        return view('admin.blogs.edit', compact('blog'));
    }

    public function update(Request $request, Blog $blog): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['is_published'] = $request->boolean('is_published');

        if ($validated['is_published'] && ! $blog->published_at) {
            $validated['published_at'] = now();
        }

        if (! $validated['is_published']) {
            $validated['published_at'] = null;
        }

        $blog->update($validated);

        return redirect()->route('admin.blogs.index')->with('status', 'Blog post updated.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $blog->delete();

        return redirect()->route('admin.blogs.index')->with('status', 'Blog post deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'is_published' => ['sometimes', 'boolean'],
        ]);
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
