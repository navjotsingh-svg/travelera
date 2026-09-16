@props([
    'name',
    'label' => 'From',
    'value' => '',
    'placeholder' => 'Going to?',
    'hint' => 'Search by place / airport',
    'variant' => 'compact',
])

@php
    $isHero = $variant === 'hero';
@endphp

<div {{ $attributes->class($isHero ? 'booking-field relative min-w-0' : 'relative min-w-0 px-4 py-3') }}>
    <div
        class="relative"
        x-data="airportPicker({{ \Illuminate\Support\Js::from(old($name, $value)) }})"
        data-suggest-url="{{ route('airports.suggest') }}"
        @click.outside="open = false"
    >
        <label class="block">
            <span class="{{ $isHero ? 'booking-label' : 'text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400' }}">{{ $label }}</span>
            <input
                type="text"
                name="{{ $name }}"
                x-model="query"
                @input.debounce.250ms="search(); hintText = ''"
                @focus="open = suggestions.length > 0"
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.enter="if (open && suggestions.length) { $event.preventDefault(); pickHighlighted() }"
                @keydown.escape="open = false"
                autocomplete="off"
                spellcheck="false"
                placeholder="{{ $placeholder }}"
                required
                @class([
                    'booking-value' => $isHero,
                    'mt-1 w-full border-0 bg-transparent p-0 text-[15px] font-semibold text-slate-900 placeholder:font-medium placeholder:text-slate-400 focus:ring-0' => ! $isHero,
                ])
            >
            @if ($isHero)
                <span class="booking-hint" x-text="hintText || '{{ $hint }}'"></span>
            @endif
        </label>
        <div
            x-show="open && suggestions.length"
            x-cloak
            x-transition
            class="airport-suggest absolute left-0 top-full z-50 mt-2 overflow-hidden rounded-xl bg-white py-1 shadow-2xl ring-1 ring-slate-100"
        >
            <template x-for="(item, index) in suggestions" :key="item.code + '-' + index">
                <button
                    type="button"
                    @mousedown.prevent="select(item)"
                    class="flex w-full items-start gap-3 px-3 py-2.5 text-left hover:bg-slate-50"
                    :class="index === active && 'bg-brand-50'"
                >
                    <span class="mt-0.5 shrink-0 rounded-md bg-brand-700 px-1.5 py-0.5 text-[10px] font-bold tracking-wide text-white" x-text="item.code"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold leading-snug text-slate-800" x-text="item.name"></span>
                        <span class="mt-0.5 block text-xs leading-snug text-slate-500" x-text="item.city"></span>
                    </span>
                </button>
            </template>
        </div>
    </div>
</div>
