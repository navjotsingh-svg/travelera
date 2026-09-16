<?php

return [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'currency' => env('STRIPE_CURRENCY', 'inr'),
    /*
     | When false, bookings confirm without Stripe (local/dev fallback).
     | Set STRIPE_ENABLED=true once keys are present.
     */
    'enabled' => (bool) env('STRIPE_ENABLED', false),
];
