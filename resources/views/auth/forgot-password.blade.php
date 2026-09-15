<x-guest-layout>
    <h1 class="auth-title">Forgot password</h1>
    <p class="auth-lead">Enter your email and we’ll send a 6-digit code so you can choose a new password.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="mt-5 w-full justify-center">
            {{ __('Send reset code') }}
        </x-primary-button>
    </form>

    <p class="mt-5 text-center text-sm text-slate-500">
        Remember it?
        <a class="font-semibold text-brand-700 hover:underline" href="{{ route('login') }}">Back to login</a>
    </p>
</x-guest-layout>
