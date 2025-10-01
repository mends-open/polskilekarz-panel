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
use Stripe\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class FetchStripeObjectsFromEvents implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const BATCH_SIZE = 25;
    private const PAGE_SIZE = 100;

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

        do {
            $events = $this->retrieveEvents($stripe, $startingAfter);

            foreach ($events->data as $event) {
                $eventArray = $event->toArray();
                $stripeObjectType = Arr::get($eventArray, 'data.object.object');

                if ($stripeObjectType !== $this->objectType) {
                    continue;
                }

                $eventArray['metadata'] = array_merge($eventArray['metadata'] ?? [], [
                    'fetched_via_events_sync' => $eventArray['created'] ?? now()->timestamp,
                ]);

                $stripeEvent = $this->storeEvent($eventArray);
                $jobs[] = new ProcessEvent($stripeEvent);

                if (count($jobs) >= self::BATCH_SIZE) {
                    $this->dispatchBatch($jobs);
                    $jobs = [];
                }
            }

            $startingAfter = $events->has_more ? Arr::last($events->data)?->id : null;
        } while ($startingAfter !== null);

        if ($jobs !== []) {
            $this->dispatchBatch($jobs);
        }
    }

    /**
     * @return Collection<\Stripe\Event>
     *
     * @throws ApiErrorException
     */
    private function retrieveEvents(StripeClient $stripe, ?string $startingAfter): Collection
    {
        $params = ['limit' => self::PAGE_SIZE];

        if ($startingAfter !== null) {
            $params['starting_after'] = $startingAfter;
        }

        return $stripe->events->all($params);
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
                'object_type' => $this->objectType,
            ]);

            return $existing;
        }

        $created = StripeEvent::create(['data' => $event]);

        Log::info('Stored Stripe event fetched via job', [
            'id' => $created->id,
            'stripe_id' => $event['id'],
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
}
