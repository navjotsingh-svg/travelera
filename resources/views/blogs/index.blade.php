<x-public-layout title="Travel blog">
    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <p class="section-kicker">INSIGHTS</p>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900 sm:text-4xl">Travelera blog</h1>
        <p class="mt-2 max-w-2xl text-slate-500">Tips, destination guides and booking advice from the Travelera desk.</p>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($blogs as $blog)
                <a href="{{ route('blogs.show', $blog) }}" class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100 transition hover:-translate-y-0.5">
                    @if ($blog->coverImageSrc())
                        <img src="{{ $blog->coverImageSrc() }}" alt="" class="h-44 w-full object-cover">
                    @else
                        <div class="flex h-44 items-center justify-center bg-brand-50 text-brand-700">Travelera</div>
                    @endif
                    <div class="p-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">{{ optional($blog->published_at)->format('d M Y') }}</p>
                        <h2 class="mt-2 text-lg font-bold text-slate-900 group-hover:text-brand-700">{{ $blog->title }}</h2>
                        <p class="mt-2 line-clamp-3 text-sm text-slate-500">{{ $blog->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($blog->body), 120) }}</p>
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-2xl bg-white p-10 text-center text-slate-500 ring-1 ring-slate-100">No posts published yet.</div>
            @endforelse
        </div>

        <div class="mt-8">{{ $blogs->links() }}</div>
    </section>
</x-public-layout>
