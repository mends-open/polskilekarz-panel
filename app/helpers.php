<?php

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
