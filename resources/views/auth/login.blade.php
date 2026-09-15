<x-guest-layout>
    <h1 class="auth-title">Log in</h1>
    <p class="auth-lead">We’ll email you a 6-digit code. No password needed to sign in.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-brand-700 shadow-sm focus:ring-brand-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <x-primary-button class="mt-5 w-full justify-center">
            {{ __('Send login code') }}
        </x-primary-button>
    </form>

    <p class="mt-5 text-center text-sm text-slate-500">
        <a class="font-semibold text-brand-700 hover:underline" href="{{ route('password.request') }}">Forgot password?</a>
        <span class="mx-2">·</span>
        New here?
        <a class="font-semibold text-brand-700 hover:underline" href="{{ route('register') }}">Sign up</a>
    </p>
</x-guest-layout>
