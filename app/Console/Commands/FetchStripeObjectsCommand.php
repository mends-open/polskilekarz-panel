<?php

namespace App\Console\Commands;

use App\Jobs\Stripe\FetchStripeObjectsFromEvents;
use Illuminate\Console\Command;
use Stripe\Util\ObjectTypes;

class FetchStripeObjectsCommand extends Command
{
    protected $signature = 'stripe:fetch-objects {objectType : The Stripe object type to fetch via events}';

    protected $description = 'Dispatch a job to fetch Stripe objects through the events API and queue them for processing.';

    public function handle(): int
    {
        $objectType = (string) $this->argument('objectType');

        if (! isset(ObjectTypes::mapping[$objectType])) {
            $this->error("Unsupported Stripe object type [{$objectType}].");

            return self::INVALID;
        }

        FetchStripeObjectsFromEvents::dispatch($objectType);

        $this->info("Queued fetch job for Stripe object type [{$objectType}].");

        return self::SUCCESS;
    }
}
