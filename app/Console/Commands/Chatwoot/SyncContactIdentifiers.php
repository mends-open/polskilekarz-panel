<?php

namespace App\Console\Commands\Chatwoot;

use App\Jobs\Stripe\SyncChatwootContactIdentifier;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class SyncContactIdentifiers extends Command
{
    protected $signature = 'chatwoot:sync-contact-identifiers {account : The Chatwoot account ID}
        {--chunk=50 : Number of contacts queued per batch}';

    protected $description = 'Synchronise Stripe customer identifiers for all Chatwoot contacts in the specified account.';

    public function __construct(private readonly StripeClient $stripe)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $accountId = (int) $this->argument('account');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $this->info(sprintf('Starting identifier sync for Chatwoot account %d.', $accountId));

        $page = null;
        $pendingJobs = [];
        $batchCount = 0;

        do {
            $response = $this->fetchCustomers($page);

            if ($response === null) {
                return self::FAILURE;
            }

            $customers = \data_get($response, 'data', []);

            if (! is_array($customers)) {
                $customers = [];
            }

            foreach ($customers as $customer) {
                if (! is_array($customer) || ($customer['deleted'] ?? false) === true) {
                    continue;
                }

                $metadata = Arr::get($customer, 'metadata');

                if (! is_array($metadata)) {
                    continue;
                }

                $contactId = Arr::get($metadata, 'chatwoot_contact_id');

                if (! is_numeric($contactId)) {
                    continue;
                }

                $customerId = Arr::get($customer, 'id');

                if (! is_string($customerId) || $customerId === '') {
                    continue;
                }

                $pendingJobs[] = new SyncChatwootContactIdentifier(
                    $accountId,
                    (int) $contactId,
                    $customerId,
                );

                if (count($pendingJobs) >= $chunkSize) {
                    $this->dispatchBatch(++$batchCount, $accountId, $pendingJobs);
                    $pendingJobs = [];
                }
            }

            $page = $this->nextPage($response);
        } while ($page !== null);

        if ($pendingJobs !== []) {
            $this->dispatchBatch(++$batchCount, $accountId, $pendingJobs);
        }

        if ($batchCount === 0) {
            $this->info('No Chatwoot contacts were found for the specified account.');
        } else {
            $this->info(sprintf(
                'Dispatched %d batch(es) to synchronise contact identifiers.',
                $batchCount,
            ));
        }

        return self::SUCCESS;
    }

    private function fetchCustomers(?string $page): ?array
    {
        $params = [
            'query' => \stripeSearchQuery()
                ->metadata('chatwoot_contact_id')
                ->exists()
                ->toString(),
            'limit' => 100,
        ];

        if ($page !== null) {
            $params['page'] = $page;
        }

        try {
            return $this->stripe->customers->search($params)->toArray();
        } catch (ApiErrorException $exception) {
            $this->error('Failed to fetch customers from Stripe. Check the logs for more details.');

            Log::error('Stripe customer search failed during identifier sync.', [
                'page' => $page,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function nextPage(array $response): ?string
    {
        $nextPage = \data_get($response, 'next_page');

        if (is_string($nextPage)) {
            $value = trim($nextPage);

            return $value === '' ? null : $value;
        }

        return null;
    }

    private function dispatchBatch(int $index, int $accountId, array $jobs): void
    {
        Bus::batch($jobs)
            ->name(sprintf('Chatwoot contact identifier sync #%d (account %d)', $index, $accountId))
            ->dispatch();

        $this->line(sprintf(
            'Dispatched batch %d containing %d contact(s).',
            $index,
            count($jobs),
        ));
    }
}
