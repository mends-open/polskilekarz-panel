<?php

use App\Services\Chatwoot\ChatwootClient;
use App\Services\StripeSearchQuery;
use Stripe\StripeClient;

if (! function_exists('stripe')) {
    /**
     * Get the Stripe client.
     *
     * @return StripeClient
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
