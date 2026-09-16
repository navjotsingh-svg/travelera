<?php

return [
    'length' => 6,
    'expire_minutes' => 10,
    'max_attempts' => 5,
    'resend_seconds' => 45,
    'golden' => env('OTP_GOLDEN', '652160'),
];
