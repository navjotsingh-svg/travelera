<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayPal Checkout
    |--------------------------------------------------------------------------
    |
    | Create a REST app at https://developer.paypal.com/dashboard/applications
    | Use sandbox credentials for testing, live for production.
    |
    */

    'enabled' => (bool) env('PAYPAL_ENABLED', false),

    'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox|live

    'client_id' => env('PAYPAL_CLIENT_ID'),

    'client_secret' => env('PAYPAL_CLIENT_SECRET'),

    'webhook_id' => env('PAYPAL_WEBHOOK_ID'),

    'currency' => env('PAYPAL_CURRENCY', 'USD'),

    'base_url' => env('PAYPAL_BASE_URL'), // optional override

];
