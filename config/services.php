<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'chatwoot' => [
        'endpoint' => env('CHATWOOT_ENDPOINT'),
        'api_access_token' => env('CHATWOOT_API_ACCESS_TOKEN'),
        'account_id' => env('CHATWOOT_ACCOUNT_ID'),
    ],

    'european_medicines_agency' => [
        'endpoint' => env('EMA_ENDPOINT', 'https://www.ema.europa.eu/en/documents/other/article-57-product-data_en.xlsx'),
        'storage_dir' => env('EMA_STORAGE_DIR', 'ema'),
        'storage_disk' => env('EMA_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
        'chunk_size' => env('EMA_CHUNK_SIZE', 500),
    ],

    'cloudflare' => [
        'endpoint' => env('CLOUDFLARE_ENDPOINT', 'https://api.cloudflare.com/client/v4'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'kv_namespace_id' => env('CLOUDFLARE_KV_NAMESPACE_ID'),
    ],

    'stripe' => [
        'api_key' => env('STRIPE_API_KEY'),
        'metadata_key' => env('STRIPE_METADATA_KEY', 'pl_panel_visible'),
    ],

];
