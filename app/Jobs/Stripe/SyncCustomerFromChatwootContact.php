<?php

namespace App\Jobs\Stripe;

use App\Models\User;
use App\Support\Metadata\MetadataPayload;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncCustomerFromChatwootContact implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int|string $accountId,
        public readonly int|string $contactId,
        public readonly int|string $impersonatorId,
        public readonly string $customerId,
        /** @var array<string, string> */
        public readonly array $metadata = [],
        public readonly ?int $notifiableId,
    ) {}

    public function handle(): void
    {
        $contact = chatwoot()
            ->platform()
            ->impersonate($this->impersonatorId)
            ->contacts()
            ->get($this->accountId, $this->contactId)['payload'];

        $payload = array_filter([
            'name' => data_get($contact, 'name'),
            'email' => data_get($contact, 'email'),
            'phone' => data_get($contact, 'phone_number'),
        ], fn ($value) => filled($value));

        $country = Str::upper((string) data_get($contact, 'additional_attributes.country_code', ''));

        if ($country !== '') {
            $payload['address'] = ['country' => $country];
        }

        $metadata = MetadataPayload::from($this->metadata);

        $metadataArray = $metadata->toArray();

        if (! array_key_exists('stripe_customer_id', $metadataArray) && is_string($this->customerId) && $this->customerId !== '') {
            $metadata = $metadata->with([
                'stripe_customer_id' => $this->customerId,
            ]);

            $metadataArray = $metadata->toArray();
        }

        if ($metadataArray !== []) {
            $payload['metadata'] = $metadataArray;
        }

        if ($payload === []) {
            $this->notify(
                title: __('notifications.chatwoot.contact_infolist.nothing_to_sync.title'),
                body: __('notifications.chatwoot.contact_infolist.nothing_to_sync.body'),
                status: 'warning',
            );

            return;
        }

        stripe()->customers->update($this->customerId, $payload);

        $this->notify(
            title: __('notifications.jobs.stripe.sync_customer_from_chatwoot_contact.updated.title'),
            body: __('notifications.jobs.stripe.sync_customer_from_chatwoot_contact.updated.body'),
            status: 'success',
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Failed to sync Stripe customer from Chatwoot contact', [
            'account_id' => $this->accountId,
            'contact_id' => $this->contactId,
            'impersonator_id' => $this->impersonatorId,
            'customer_id' => $this->customerId,
            'metadata' => $this->metadata,
            'exception' => $exception,
        ]);

        $this->notify(
            title: __('notifications.jobs.stripe.sync_customer_from_chatwoot_contact.failed.title'),
            body: __('notifications.jobs.stripe.sync_customer_from_chatwoot_contact.failed.body'),
            status: 'danger',
        );
    }

    protected function notify(string $title, string $body, string $status): void
    {
        $user = $this->resolveNotifiable();

        if (! $user) {
            return;
        }

        Auth::setUser($user);

        $guard = Filament::auth();

        if (method_exists($guard, 'setUser')) {
            $guard->setUser($user);
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->status($status)
            ->broadcast($user);
    }

    protected function resolveNotifiable(): ?User
    {
        if (! $this->notifiableId) {
            return null;
        }

        return User::find($this->notifiableId);
    }

}
