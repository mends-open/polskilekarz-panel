<?php

namespace App\Filament\Widgets\Chatwoot;

use App\Filament\Widgets\BaseSchemaWidget;
use App\Jobs\Stripe\SyncCustomerFromChatwootContact;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use App\Support\Dashboard\StripeContext;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Stripe\Exception\ApiErrorException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContactInfolist extends BaseSchemaWidget
{
    use InteractsWithDashboardContext;

    /**
     * @throws NotFoundExceptionInterface
     * @throws RequestException
     * @throws ContainerExceptionInterface
     * @throws ConnectionException
     */
    #[Computed(persist: true)]
    protected function chatwootContact(): array
    {
        $context = $this->chatwootContext();

        if (! $context->canImpersonate()) {
            return [];
        }

        return chatwoot()
            ->platform()
            ->impersonate($context->currentUserId)
            ->contacts()
            ->get($context->accountId, $context->contactId)['payload'];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    #[On('reset')]
    public function resetComponent(): void
    {
        $this->reset();
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ConnectionException
     * @throws ContainerExceptionInterface
     * @throws RequestException
     */
    public function schema(Schema $schema): Schema
    {
        $chatwootContext = $this->chatwootContext();

        $syncReady = $chatwootContext->accountId !== null
            && $chatwootContext->contactId !== null
            && $chatwootContext->currentUserId !== null;

        return $schema
            ->state($this->chatwootContact())
            ->components([
                Section::make('contact')
                    ->headerActions([
                        Action::make('syncCustomerFromContact')
                            ->label('Sync customer')
                            ->icon(Heroicon::OutlinedArrowDownOnSquareStack)
                            ->outlined()
                            ->color($syncReady ? 'primary' : 'gray')
                            ->disabled(! $syncReady)
                            ->action(fn () => $this->syncCustomerFromChatwootContact()),
                        Action::make('reset')
                            ->action(fn () => $this->reset())
                            ->hiddenLabel()
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->link(),
                    ])
                    ->schema([
                        TextEntry::make('id')
                            ->badge()
                            ->color('gray')
                            ->inlineLabel(),
                        TextEntry::make('name')
                            ->inlineLabel()
                            ->placeholder('No name'),
                        TextEntry::make('created_at')
                            ->inlineLabel()
                            ->placeholder('No created')
                            ->since(),
                        TextEntry::make('email')
                            ->inlineLabel()
                            ->placeholder('No email'),
                        TextEntry::make('phone_number')
                            ->inlineLabel()
                            ->placeholder('No phone'),
                        TextEntry::make('additional_attributes.country_code')
                            ->inlineLabel()
                            ->badge()
                            ->placeholder('No created'),
                    ]),
            ]);
    }

    protected function syncCustomerFromChatwootContact(): void
    {
        $stripeContext = $this->stripeContext();
        $chatwootContext = $this->chatwootContext();

        $customerId = $stripeContext->customerId;
        $accountId = $chatwootContext->accountId;
        $contactId = $chatwootContext->contactId;
        $impersonatorId = $chatwootContext->currentUserId;

        if (! $accountId || ! $contactId || ! $impersonatorId) {
            Notification::make()
                ->title('Missing Chatwoot context')
                ->body('We could not find the Chatwoot contact details. Please open this widget from a Chatwoot conversation.')
                ->danger()
                ->send();

            return;
        }

        try {
            $contact = chatwoot()
                ->platform()
                ->impersonate($impersonatorId)
                ->contacts()
                ->get($accountId, $contactId)['payload'] ?? [];
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            Notification::make()
                ->title('Failed to load Chatwoot contact')
                ->body('We were unable to load the Chatwoot contact details. Please try again.')
                ->danger()
                ->send();

            return;
        }

        $payload = array_filter([
            'name' => data_get($contact, 'name'),
            'email' => data_get($contact, 'email'),
            'phone' => data_get($contact, 'phone_number'),
        ], fn ($value) => filled($value));

        $country = Str::upper((string) data_get($contact, 'additional_attributes.country_code', ''));

        if ($country !== '') {
            $payload['address'] = ['country' => $country];
        }

        if (! $customerId) {
            $metadata = [
                'chatwoot_account_id' => (string) $accountId,
                'chatwoot_contact_id' => (string) $contactId,
            ];

            if ($metadata !== []) {
                $payload['metadata'] = $metadata;
            }

            try {
                $customer = stripe()->customers->create($payload);
            } catch (ApiErrorException $exception) {
                report($exception);

                Notification::make()
                    ->title('Failed to create Stripe customer')
                    ->body('We were unable to create a Stripe customer from the Chatwoot contact. Please try again.')
                    ->danger()
                    ->send();

                return;
            }

            $this->dashboardContext()->storeStripe(new StripeContext($customer->id));

            Notification::make()
                ->title('Stripe customer created')
                ->body('A Stripe customer was created from the Chatwoot contact details.')
                ->success()
                ->send();

            $this->reset();

            return;
        }

        if ($payload === []) {
            Notification::make()
                ->title('No contact details to sync')
                ->body('The Chatwoot contact does not have any details to copy to the Stripe customer.')
                ->warning()
                ->send();

            return;
        }

        SyncCustomerFromChatwootContact::dispatch(
            accountId: $accountId,
            contactId: $contactId,
            impersonatorId: $impersonatorId,
            customerId: $customerId,
            notifiableId: auth()->id(),
        );

        Notification::make()
            ->title('Syncing customer details')
            ->body('We are fetching the Chatwoot contact details and updating the Stripe customer.')
            ->info()
            ->send();

        $this->reset();
    }
}
