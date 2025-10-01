<?php

namespace App\Jobs\Stripe;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\ApiResource;
use Stripe\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Util\ObjectTypes;

class FetchStripeObjectsFromEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const PAGE_SIZE = 100;

    /** @var array<string, bool> */
    private array $processedObjectIds = [];

    public function __construct(private readonly string $objectType)
    {
    }

    /**
     * @throws ApiErrorException
     */
    public function handle(StripeClient $stripe): void
    {
        $startingAfter = null;
        $resourceClass = $this->resolveResourceClass();

        do {
            $objects = $this->retrieveObjects($stripe, $resourceClass, $startingAfter);

            foreach ($objects->data as $object) {
                $objectId = (string) ($object->id ?? '');

                if ($objectId === '' || isset($this->processedObjectIds[$objectId])) {
                    continue;
                }

                $this->processedObjectIds[$objectId] = true;

                $this->tagObjectMetadata($stripe, $resourceClass, $object, $objectId);
            }

            $lastObject = $objects->has_more ? end($objects->data) : null;
            $startingAfter = $lastObject->id ?? null;
        } while ($startingAfter !== null);
    }

    /**
     * @throws ApiErrorException
     */
    private function retrieveObjects(StripeClient $stripe, string $resourceClass, ?string $startingAfter): Collection
    {
        $params = ['limit' => self::PAGE_SIZE];

        if ($startingAfter !== null) {
            $params['starting_after'] = $startingAfter;
        }

        /** @var class-string<ApiResource> $resourceClass */
        return $stripe->requestCollection('get', $resourceClass::classUrl(), $params, []);
    }

    private function tagObjectMetadata(StripeClient $stripe, string $resourceClass, ApiResource $object, string $objectId): void
    {
        $metadata = $this->prepareMetadata($object);

        $timestamp = (int) ($object->created ?? now()->timestamp);
        $metadata['fetched_via_events_sync'] = (string) $timestamp;

        try {
            $stripe->request('post', sprintf('%s/%s', $resourceClass::classUrl(), $objectId), [
                'metadata' => $metadata,
            ]);

            Log::info('Updated Stripe object metadata to trigger event dispatch', [
                'object_type' => $this->objectType,
                'object_id' => $objectId,
                'metadata_key' => 'fetched_via_events_sync',
                'metadata_value' => $timestamp,
            ]);
        } catch (ApiErrorException $exception) {
            Log::warning('Failed to update Stripe object metadata for events sync', [
                'object_type' => $this->objectType,
                'object_id' => $objectId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return class-string<ApiResource>
     */
    private function resolveResourceClass(): string
    {
        $mapping = ObjectTypes::mapping;

        if (! isset($mapping[$this->objectType])) {
            throw new \InvalidArgumentException("Unsupported Stripe object type [{$this->objectType}]");
        }

        return $mapping[$this->objectType];
    }

    /**
     * @return array<string, string>
     */
    private function prepareMetadata(ApiResource $object): array
    {
        $metadata = [];
        $objectMetadata = $object->metadata ?? [];

        if ($objectMetadata instanceof StripeObject) {
            $metadata = $objectMetadata->toArray();
        } elseif (is_array($objectMetadata)) {
            $metadata = $objectMetadata;
        }

        foreach ($metadata as $key => $value) {
            if (! is_string($value)) {
                $metadata[$key] = (string) $value;
            }
        }

        return $metadata;
    }
}
