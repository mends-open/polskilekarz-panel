<?php

namespace App\Jobs\Stripe;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\ApiResource;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class TagStripeObjectMetadataJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  class-string<ApiResource>  $resourceClass
     * @param  array<string, string>  $metadata
     */
    public function __construct(
        private readonly string $objectType,
        private readonly string $resourceClass,
        private readonly string $objectId,
        private readonly array $metadata,
        private readonly int $timestamp,
    ) {
    }

    public function handle(StripeClient $stripe): void
    {
        $metadata = $this->metadata;
        $metadata['fetched_via_events_sync'] = (string) $this->timestamp;

        try {
            /** @var class-string<ApiResource> $resourceClass */
            $stripe->request('post', sprintf('%s/%s', $this->resourceClass::classUrl(), $this->objectId), [
                'metadata' => $metadata,
            ], []);

            Log::info('Updated Stripe object metadata to trigger event dispatch', [
                'object_type' => $this->objectType,
                'object_id' => $this->objectId,
                'metadata_key' => 'fetched_via_events_sync',
                'metadata_value' => $this->timestamp,
            ]);
        } catch (ApiErrorException $exception) {
            Log::warning('Failed to update Stripe object metadata for events sync', [
                'object_type' => $this->objectType,
                'object_id' => $this->objectId,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
