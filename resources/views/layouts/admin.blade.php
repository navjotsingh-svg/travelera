@props(['title' => 'Admin'])

<x-admin-layout :title="$title">
    {{ $slot }}
</x-admin-layout>
