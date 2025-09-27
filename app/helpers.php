<?php

use App\Services\Chatwoot\ChatwootClient;
use App\Services\Cloudflare\CloudflareClient;
use App\Services\Cloudflare\LinkShortener;
use App\Services\StripeSearchQuery;
use Stripe\StripeClient;

if (! function_exists('stripe')) {
    /**
     * Get the Stripe client.
     */
    function stripe(): StripeClient
    {
        return app(StripeClient::class);
    }

}

if (! function_exists('stripeSearchQuery')) {
    /**
     * Create a new Stripe search query builder instance.
     */
    function stripeSearchQuery(?string $clause = null): StripeSearchQuery
    {
        return new StripeSearchQuery($clause);
    }
}

if (! function_exists('chatwoot')) {
    function chatwoot(): ChatwootClient
    {
        return app(ChatwootClient::class);
    }
}

if (! function_exists('cloudflare')) {
    function cloudflare(): CloudflareClient
    {
        return app(CloudflareClient::class);
    }
}

if (! function_exists('cloudflareShortener')) {
    function cloudflareShortener(): LinkShortener
    {
        return cloudflare()->shortener();
    }
}
