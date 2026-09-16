<x-public-layout :title="$blog->title">
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        <a href="{{ route('blogs.index') }}" class="text-sm font-semibold text-brand-700">← All posts</a>
        <p class="mt-6 text-xs font-semibold uppercase tracking-widest text-slate-400">{{ optional($blog->published_at)->format('d M Y') }} · {{ $blog->author?->name ?? 'Travelera' }}</p>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900 sm:text-4xl">{{ $blog->title }}</h1>
        @if ($blog->excerpt)
            <p class="mt-4 text-lg text-slate-500">{{ $blog->excerpt }}</p>
        @endif
        @if ($blog->coverImageSrc())
            <img src="{{ $blog->coverImageSrc() }}" alt="" class="mt-8 w-full rounded-2xl object-cover">
        @endif
        <div class="prose prose-slate mt-8 max-w-none whitespace-pre-line text-[15px] leading-7 text-slate-700">{{ $blog->body }}</div>
    </article>
</x-public-layout>
