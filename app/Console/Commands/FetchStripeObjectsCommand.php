<?php

namespace App\Console\Commands;

use App\Jobs\Stripe\FetchStripeObjectsFromEvents;
use Illuminate\Console\Command;

class FetchStripeObjectsCommand extends Command
{
    protected $signature = 'stripe:fetch-objects {objectType : The Stripe object type to fetch via events}';

    protected $description = 'Dispatch a job to fetch Stripe objects through the events API and queue them for processing.';

    public function handle(): int
    {
        $objectType = (string) $this->argument('objectType');

        FetchStripeObjectsFromEvents::dispatch($objectType);

        $this->info("Queued fetch job for Stripe object type [{$objectType}].");

        return self::SUCCESS;
    }
}
