<?php

return [
    'customer_portal' => [
        'return_url' => env('STRIPE_CUSTOMER_PORTAL_RETURN_URL'),
        'locale' => env('STRIPE_CUSTOMER_PORTAL_LOCALE', 'auto'),
    ],
];
