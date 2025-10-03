<?php

namespace App\Console\Commands\Chatwoot;

use App\Jobs\Stripe\SyncChatwootContactIdentifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncContactIdentifiers extends Command
{
    protected $signature = 'chatwoot:sync-contact-identifiers {account : The Chatwoot account ID}
        {--chunk=50 : Number of contacts queued per batch}';

    protected $description = 'Synchronise Stripe customer identifiers for all Chatwoot contacts in the specified account.';

    public function handle(): int
    {
        $accountId = (int) $this->argument('account');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $this->info(sprintf('Starting identifier sync for Chatwoot account %d.', $accountId));

        $page = 1;
        $pendingJobs = [];
        $batchCount = 0;

        do {
            $response = $this->fetchContacts($accountId, $page);

            if ($response === null) {
                return self::FAILURE;
            }

            $contacts = \data_get($response, 'payload', []);

            if (! is_array($contacts)) {
                $contacts = [];
            }

            foreach ($contacts as $contact) {
                $contactId = \data_get($contact, 'id');

                if (! is_numeric($contactId)) {
                    continue;
                }

                $pendingJobs[] = new SyncChatwootContactIdentifier($accountId, (int) $contactId);

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

    private function fetchContacts(int $accountId, int $page): ?array
    {
        try {
            return \chatwoot()
                ->application()
                ->contacts()
                ->list($accountId, ['page' => $page]);
        } catch (Throwable $exception) {
            $this->error('Failed to fetch contacts from Chatwoot. Check the logs for more details.');

            Log::error('Chatwoot contact fetch failed during identifier sync.', [
                'account_id' => $accountId,
                'page' => $page,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function nextPage(array $response): ?int
    {
        $nextPage = \data_get($response, 'meta.next_page');

        if (is_numeric($nextPage)) {
            $value = (int) $nextPage;

            return $value > 0 ? $value : null;
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
