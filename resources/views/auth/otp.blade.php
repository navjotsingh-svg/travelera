@php
    $purpose = session('otp.purpose');
    $email = session('otp.email');
    $heading = match ($purpose) {
        'register' => 'Verify your email',
        'password_reset' => 'Enter reset code',
        default => 'Check your email',
    };
@endphp

<x-guest-layout>
    <h1 class="auth-title">{{ $heading }}</h1>
    <p class="auth-lead">Enter the 6-digit code we sent to <strong>{{ $email }}</strong>.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('otp.verify') }}" class="auth-form">
        @csrf

        <div>
            <x-input-label for="otp" :value="__('Verification code')" />
            <x-text-input
                id="otp"
                class="auth-otp-input mt-1 block w-full"
                type="text"
                name="otp"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                autocomplete="one-time-code"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <x-primary-button class="mt-5 w-full justify-center">
            {{ __('Verify code') }}
        </x-primary-button>
    </form>

    <form method="POST" action="{{ route('otp.resend') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-semibold text-brand-700 hover:underline">Resend code</button>
    </form>
</x-guest-layout>
