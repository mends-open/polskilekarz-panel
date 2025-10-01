<?php

namespace App\Jobs\Stripe;

use App\Models\StripeEvent;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Stripe\ApiResource;
use Stripe\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Util\ObjectTypes;

class FetchStripeObjectsFromEvents implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const BATCH_SIZE = 25;
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
        $jobs = [];
        $resourceClass = $this->resolveResourceClass();

        do {
            $objects = $this->retrieveObjects($stripe, $resourceClass, $startingAfter);

            foreach ($objects->data as $object) {
                $objectId = (string) ($object->id ?? '');

                if ($objectId === '' || isset($this->processedObjectIds[$objectId])) {
                    continue;
                }

                $this->processedObjectIds[$objectId] = true;

                $objectArray = $object->toArray();
                $objectArray['metadata'] = array_merge($objectArray['metadata'] ?? [], [
                    'fetched_via_events_sync' => $objectArray['created'] ?? now()->timestamp,
                ]);

                $eventPayload = $this->buildSyntheticEvent($objectArray);
                $stripeEvent = $this->storeEvent($eventPayload);
                $jobs[] = new ProcessEvent($stripeEvent);

                if (count($jobs) >= self::BATCH_SIZE) {
                    $this->dispatchBatch($jobs);
                    $jobs = [];
                }
            }

            $lastObject = $objects->has_more ? Arr::last($objects->data) : null;
            $startingAfter = $lastObject->id ?? null;
        } while ($startingAfter !== null);

        if ($jobs !== []) {
            $this->dispatchBatch($jobs);
        }
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

    private function storeEvent(array $event): StripeEvent
    {
        $existing = StripeEvent::query()
            ->where('data->>id', $event['id'])
            ->first();

        if ($existing !== null) {
            $existing->update(['data' => $event]);

            Log::info('Updated Stripe event fetched via job', [
                'id' => $existing->id,
                'stripe_id' => $event['id'],
                'stripe_object_id' => $event['data']['object']['id'] ?? null,
                'object_type' => $this->objectType,
            ]);

            return $existing;
        }

        $created = StripeEvent::create(['data' => $event]);

        Log::info('Stored Stripe event fetched via job', [
            'id' => $created->id,
            'stripe_id' => $event['id'],
            'stripe_object_id' => $event['data']['object']['id'] ?? null,
            'object_type' => $this->objectType,
        ]);

        return $created;
    }

    /**
     * @param array<int, ProcessEvent> $jobs
     */
    private function dispatchBatch(array $jobs): void
    {
        Bus::batch($jobs)
            ->name("Process Stripe events for {$this->objectType}")
            ->dispatch();
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

    private function buildSyntheticEvent(array $object): array
    {
        $created = $object['created'] ?? now()->timestamp;
        $eventId = sprintf('evt_sync_%s', $object['id']);

        return [
            'id' => $eventId,
            'object' => 'event',
            'type' => "{$this->objectType}.synced",
            'created' => $created,
            'livemode' => $object['livemode'] ?? false,
            'pending_webhooks' => 0,
            'request' => null,
            'api_version' => Stripe::getApiVersion(),
            'metadata' => [
                'fetched_via_events_sync' => $created,
            ],
            'data' => [
                'object' => $object,
            ],
        ];
    }
}
