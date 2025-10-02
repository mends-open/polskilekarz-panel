<?php

namespace App\Jobs\Stripe;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        $visibleSinceKey = config('services.stripe.visible_since_key');

        if ($visibleSinceKey !== null && $visibleSinceKey !== '') {
            $metadata[$visibleSinceKey] = (string) $this->timestamp;
        }

        $metadata['events_sync_nonce'] = (string) Str::uuid();

        try {
            /** @var class-string<ApiResource> $resourceClass */
            $stripe->request('post', sprintf('%s/%s', $this->resourceClass::classUrl(), $this->objectId), [
                'metadata' => $metadata,
            ], []);

            Log::info('Updated Stripe object metadata to trigger event dispatch', [
                'object_type' => $this->objectType,
                'object_id' => $this->objectId,
                'metadata_key' => $visibleSinceKey,
                'metadata_value' => $visibleSinceKey !== null && $visibleSinceKey !== '' ? $metadata[$visibleSinceKey] : null,
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
