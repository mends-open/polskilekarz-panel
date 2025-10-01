<?php

namespace App\Jobs\Stripe;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
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

    public function __construct(
        private readonly string $objectType,
        private readonly ?string $startingAfter = null,
    ) {
    }

    /**
     * @throws ApiErrorException
     */
    public function handle(StripeClient $stripe): void
    {
        $resourceClass = $this->resolveResourceClass();

        $objects = $this->retrieveObjects($stripe, $resourceClass, $this->startingAfter);

        $jobs = $this->prepareMetadataJobs($objects, $resourceClass);

        if ($jobs !== []) {
            Bus::batch($jobs)
                ->name(sprintf('stripe-%s-events-sync', $this->objectType))
                ->allowFailures()
                ->dispatch();
        }

        if ($objects->has_more && ! empty($objects->data)) {
            $lastObject = end($objects->data);
            $nextCursor = $lastObject->id ?? null;

            if ($nextCursor !== null) {
                self::dispatch($this->objectType, $nextCursor);
            }
        }
    }

    /**
     * @param  class-string<ApiResource>  $resourceClass
     * @return TagStripeObjectMetadataJob[]
     */
    private function prepareMetadataJobs(Collection $objects, string $resourceClass): array
    {
        $jobs = [];

        foreach ($objects->data as $object) {
            $objectId = (string) ($object->id ?? '');

            if ($objectId === '') {
                continue;
            }

            $metadata = $this->prepareMetadata($object);
            $timestamp = (int) ($object->created ?? now()->timestamp);

            $jobs[] = new TagStripeObjectMetadataJob(
                objectType: $this->objectType,
                resourceClass: $resourceClass,
                objectId: $objectId,
                metadata: $metadata,
                timestamp: $timestamp,
            );
        }

        return $jobs;
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
