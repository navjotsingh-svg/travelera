@props(['amount', 'currency' => 'INR'])

@php
    $currency = strtoupper((string) $currency);
    $formatted = number_format((float) $amount, 2);
    $symbol = match ($currency) {
        'INR' => '₹',
        'USD' => '$',
        'GBP' => '£',
        'EUR' => '€',
        default => $currency.' ',
    };
@endphp

<span {{ $attributes }}>{{ $symbol }}{{ $formatted }}</span>
