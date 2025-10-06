<?php

namespace App\Jobs\Stripe;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncCustomerFromChatwootContact implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int|string $accountId,
        public readonly int|string $contactId,
        public readonly int|string $impersonatorId,
        public readonly ?string $customerId,
        public readonly ?int $notifiableId,
    ) {}

    public function handle(): void
    {
        $contact = chatwoot()
            ->platform()
            ->impersonate($this->impersonatorId)
            ->contacts()
            ->get($this->accountId, $this->contactId)['payload'];

        $payload = $this->prepareCustomerPayload($contact);

        if ($this->customerId) {
            stripe()->customers->update($this->customerId, $payload);

            $this->notify(
                title: 'Stripe customer updated',
                body: 'The Stripe customer was updated with the Chatwoot contact details.',
                status: 'success',
            );

            return;
        }

        stripe()->customers->create($payload);

        $this->notify(
            title: 'Stripe customer created',
            body: 'A new Stripe customer was created from the Chatwoot contact details.',
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
            'exception' => $exception,
        ]);

        $this->notify(
            title: 'Failed to update Stripe customer',
            body: 'We were unable to sync the Stripe customer with the Chatwoot contact. Please try again.',
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

    private function prepareCustomerPayload(array $contact): array
    {
        $metadata = array_filter([
            'chatwoot_account_id' => (string) $this->accountId,
            'chatwoot_contact_id' => (string) $this->contactId,
        ]);

        $country = $this->resolveCountryCode($contact);

        $payload = [
            'name' => data_get($contact, 'name'),
            'email' => data_get($contact, 'email'),
            'phone' => data_get($contact, 'phone_number'),
            'address' => $country ? ['country' => $country] : [],
            'metadata' => $metadata,
        ];

        return array_filter($payload, function ($value) {
            if (is_array($value)) {
                return array_filter($value) !== [];
            }

            return filled($value);
        });
    }

    private function resolveCountryCode(array $contact): ?string
    {
        $country = strtoupper((string) data_get($contact, 'additional_attributes.country_code', ''));

        return preg_match('/^[A-Z]{2}$/', $country) ? $country : null;
    }
}
