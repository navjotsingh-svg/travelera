@props([
    'intent' => 'contact',
    'submitLabel' => 'Submit',
    'compact' => false,
    'showTitle' => false,
    'title' => 'Send us a message',
])

<form method="POST" action="{{ route('contact.store') }}" {{ $attributes->merge(['class' => $compact ? 'site-form grid gap-3' : 'site-form']) }}>
    @csrf
    <input type="hidden" name="intent" value="{{ $intent }}">

    @if ($showTitle)
        <p class="font-bold text-slate-900">{{ $title }}</p>
    @endif

    @if ($compact)
        <input name="name" required placeholder="Full Name" value="{{ old('name') }}" class="rounded-xl border-slate-200 text-sm">
        <input name="phone" placeholder="Phone" value="{{ old('phone') }}" class="rounded-xl border-slate-200 text-sm">
        <input type="email" name="email" required placeholder="Email" value="{{ old('email') }}" class="rounded-xl border-slate-200 text-sm">
        <textarea name="message" rows="2" placeholder="Tell us about the trip" class="rounded-xl border-slate-200 text-sm">{{ old('message') }}</textarea>
        <button class="mt-1 rounded-full bg-brand-700 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-800">{{ $submitLabel }}</button>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-slate-600">Full name</label>
                <input name="name" required value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Your name">
            </div>
            <div>
                <label class="text-sm font-medium text-slate-600">Phone</label>
                <input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="+1 888 652 6415">
            </div>
        </div>
        <div class="mt-4">
            <label class="text-sm font-medium text-slate-600">Email</label>
            <input type="email" name="email" required value="{{ old('email') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="you@example.com">
        </div>
        <div class="mt-4">
            <label class="text-sm font-medium text-slate-600">Message</label>
            <textarea name="message" rows="5" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="How can we help?">{{ old('message') }}</textarea>
        </div>
        <button class="mt-5 w-full rounded-full bg-brand-700 py-3 text-sm font-semibold text-white hover:bg-brand-800">{{ $submitLabel }}</button>
    @endif
</form>
