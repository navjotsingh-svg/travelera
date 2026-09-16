@props(['title' => 'Admin'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Travelera Admin</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|roboto:400,500,700,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/css/custom.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
    <div class="admin-shell" x-data="{ open: false }" @keydown.escape.window="open = false">
        <aside class="admin-sidebar" :class="open && 'is-open'">
            <div class="admin-brand-wrap">
                <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                    <img src="{{ asset('images/favicon.png') }}" alt="Travelera">
                    <div>
                        <strong>Travelera</strong>
                        <span>Admin console</span>
                    </div>
                </a>
            </div>

            <nav class="admin-nav">
                <p class="admin-nav-label">Overview</p>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                    <span class="admin-nav-ico">⌂</span> Dashboard
                </a>

                <p class="admin-nav-label">Operations</p>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
                    <span class="admin-nav-ico">👤</span> Users
                </a>
                <a href="{{ route('admin.bookings.index') }}" class="{{ request()->routeIs('admin.bookings.*') ? 'is-active' : '' }}">
                    <span class="admin-nav-ico">✈</span> Bookings
                </a>
                <a href="{{ route('admin.searches.index') }}" class="{{ request()->routeIs('admin.searches.*') ? 'is-active' : '' }}">
                    <span class="admin-nav-ico">⌕</span> Searches
                </a>
                <a href="{{ route('admin.abandoned.index') }}" class="{{ request()->routeIs('admin.abandoned.*') ? 'is-active' : '' }}">
                    <span class="admin-nav-ico">⚠</span> Abandoned
                </a>

                <p class="admin-nav-label">Content</p>
                <a href="{{ route('admin.packages.index') }}" class="{{ request()->routeIs('admin.packages.*') ? 'is-active' : '' }}">
                    <span class="admin-nav-ico">▣</span> Packages
                </a>
                <a href="{{ route('admin.blogs.index') }}" class="{{ request()->routeIs('admin.blogs.*') ? 'is-active' : '' }}">
                    <span class="admin-nav-ico">✎</span> Blogs
                </a>
            </nav>

            <div class="admin-sidebar-foot">
                <a href="{{ route('home') }}" class="admin-foot-link">View website</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="admin-foot-link admin-foot-logout">Log out</button>
                </form>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <button type="button" class="admin-menu-btn" @click="open = ! open" aria-label="Open menu">
                    <span></span><span></span><span></span>
                </button>
                <div class="admin-topbar-copy">
                    <h1>{{ $title }}</h1>
                    <p>{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('home') }}" class="admin-topbar-link">Website</a>
            </header>

            <main class="admin-content">
                @if (session('status'))
                    <div class="admin-flash">{{ session('status') }}</div>
                @endif
                {{ $slot }}
            </main>
        </div>

        <div class="admin-backdrop" x-show="open" x-cloak @click="open = false"></div>
    </div>
</body>
</html>
