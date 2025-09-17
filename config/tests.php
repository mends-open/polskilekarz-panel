<?php

return [
    'stripe' => [
        'api_key' => env('STRIPE_TEST_API_KEY'),
        'webhook_secret' => env('STRIPE_TEST_WEBHOOK_SECRET'),
    ],
];
