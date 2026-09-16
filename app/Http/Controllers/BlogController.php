<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $blogs = Blog::query()
            ->published()
            ->with('author')
            ->latest('published_at')
            ->paginate(9);

        return view('blogs.index', compact('blogs'));
    }

    public function show(Blog $blog): View
    {
        abort_unless($blog->is_published && $blog->published_at, 404);

        return view('blogs.show', compact('blog'));
    }
}
