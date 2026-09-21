<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flight agent chat
    |--------------------------------------------------------------------------
    |
    | Advise-only assistant (phase 2): search, filter/refine (nonstop, budget,
    | time window), rank cheapest/fastest, confirm selection, and hand off to
    | the booking page. Never charges or captures payment.
    |
    */

    'enabled' => (bool) env('AGENT_ENABLED', true),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 20),
    ],

    'max_results' => (int) env('AGENT_MAX_RESULTS', 3),

];
