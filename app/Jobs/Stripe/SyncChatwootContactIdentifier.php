<?php

namespace App\Jobs\Stripe;

use App\Support\Chatwoot\ContactIdentifierSynchronizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncChatwootContactIdentifier implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $accountId,
        public readonly int $contactId,
    ) {}

    public function handle(ContactIdentifierSynchronizer $synchronizer): void
    {
        $synchronizer->sync($this->accountId, $this->contactId);
    }
}
