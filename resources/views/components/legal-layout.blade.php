@props([
    'title',
    'kicker' => 'TRAVEL ERA LLC',
    'effective' => 'November 1, 2025',
    'updated' => 'October 2, 2026',
])

@php
    $legalNav = [
        ['route' => 'legal.terms', 'label' => 'Terms'],
        ['route' => 'legal.cancellation', 'label' => 'Cancellations'],
        ['route' => 'legal.privacy', 'label' => 'Privacy'],
        ['route' => 'legal.disclaimer', 'label' => 'Disclaimer'],
    ];
@endphp

<x-public-layout :title="$title">
    <div class="legal-page">
        <section class="legal-hero">
            <img
                src="{{ asset('images/sky-with-clouds-sunset-with.webp') }}"
                alt=""
                class="legal-hero-photo"
            >
            <div class="legal-hero-veil"></div>
            <div class="relative mx-auto max-w-4xl px-4 pb-28 pt-16 sm:px-6 sm:pb-32 sm:pt-20">
                <p class="text-xs font-bold tracking-[0.28em] text-blue-200">{{ $kicker }}</p>
                <h1 class="mt-3 max-w-3xl text-4xl font-extrabold tracking-tight text-white sm:text-5xl">{{ $title }}</h1>
                <div class="mt-6 flex flex-wrap gap-2">
                    <span class="legal-chip">Effective {{ $effective }}</span>
                    <span class="legal-chip">Updated {{ $updated }}</span>
                </div>
                <p class="sr-only">Effective Date: {{ $effective }}. Last Updated: {{ $updated }}.</p>
            </div>
        </section>

        <div class="legal-sheet-wrap">
            <nav class="legal-switch" aria-label="Legal pages">
                @foreach ($legalNav as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        class="{{ request()->routeIs($item['route']) ? 'is-active' : '' }}"
                    >{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <article class="legal-doc">
                {{ $slot }}
            </article>
        </div>
    </div>
</x-public-layout>
