<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class StripeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function ($app) {
            $apiKey = (string) config('services.stripe.api_key');

            if ($apiKey === '') {
                return new StripeClient([]);
            }

            return new StripeClient($apiKey);
        });

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
