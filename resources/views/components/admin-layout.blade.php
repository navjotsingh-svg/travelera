@props(['title' => 'Admin'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Travelera</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|roboto:400,500,700,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/css/custom.css', 'resources/js/app.js'])
</head>
<body class="admin-body font-sans antialiased">
    <div class="admin-shell" x-data="{ open: false }">
        <aside class="admin-sidebar" :class="open && 'is-open'">
            <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                <img src="{{ asset('images/favicon.png') }}" alt="" class="h-8 w-8 rounded-full">
                <span>Travelera Admin</span>
            </a>
            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">Dashboard</a>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">Users</a>
                <a href="{{ route('admin.bookings.index') }}" class="{{ request()->routeIs('admin.bookings.*') ? 'is-active' : '' }}">Bookings</a>
                <a href="{{ route('admin.searches.index') }}" class="{{ request()->routeIs('admin.searches.*') ? 'is-active' : '' }}">Searches</a>
                <a href="{{ route('admin.abandoned.index') }}" class="{{ request()->routeIs('admin.abandoned.*') ? 'is-active' : '' }}">Abandoned payments</a>
                <a href="{{ route('admin.packages.index') }}" class="{{ request()->routeIs('admin.packages.*') ? 'is-active' : '' }}">Packages</a>
                <a href="{{ route('admin.blogs.index') }}" class="{{ request()->routeIs('admin.blogs.*') ? 'is-active' : '' }}">Blogs</a>
            </nav>
            <div class="admin-sidebar-foot">
                <a href="{{ route('home') }}">← Website</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Log out</button>
                </form>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <button type="button" class="admin-menu-btn" @click="open = ! open" aria-label="Menu">☰</button>
                <div>
                    <p class="admin-topbar-title">{{ $title }}</p>
                    <p class="admin-topbar-sub">{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
                </div>
            </header>

            <div class="admin-content">
                @if (session('status'))
                    <div class="admin-flash">{{ session('status') }}</div>
                @endif
                {{ $slot }}
            </div>
        </div>

        <div class="admin-backdrop" x-show="open" x-cloak @click="open = false"></div>
    </div>
</body>
</html>
