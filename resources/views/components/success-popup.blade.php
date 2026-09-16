@props([
    'title' => 'Thank you',
    'message' => '',
    'kind' => 'contact',
])

@php
    $eyebrow = match ($kind) {
        'enquiry' => 'Private trip desk',
        'visa' => 'Visa assistance',
        'newsletter' => 'Travelera updates',
        default => 'Travelera support',
    };
@endphp

<div
    x-data="{ open: true }"
    x-show="open"
    x-cloak
    class="success-popup-root"
    role="dialog"
    aria-modal="true"
    aria-labelledby="success-popup-title"
    @keydown.escape.window="open = false"
>
    <div
        class="success-popup-backdrop"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
    ></div>

    <div class="success-popup-center">
        <div
            class="success-popup"
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-6 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            @click.stop
        >
            <button type="button" class="success-popup-close" @click="open = false" aria-label="Close">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="success-popup-glow" aria-hidden="true"></div>

            <div class="success-popup-icon" aria-hidden="true">
                <svg class="success-popup-check" viewBox="0 0 52 52" fill="none">
                    <circle class="success-popup-check-circle" cx="26" cy="26" r="24" stroke="currentColor" stroke-width="2.5"/>
                    <path class="success-popup-check-mark" d="M15 27.2l7.2 7.2L37.5 18" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <p class="success-popup-eyebrow">{{ $eyebrow }}</p>
            <h2 id="success-popup-title" class="success-popup-title">{{ $title }}</h2>
            <p class="success-popup-copy">{{ $message }}</p>

            <div class="success-popup-actions">
                <button type="button" class="success-popup-btn" @click="open = false">Got it</button>
                <a href="tel:+18886526415" class="success-popup-link">Call +1 888 652 6415</a>
            </div>
        </div>
    </div>
</div>
