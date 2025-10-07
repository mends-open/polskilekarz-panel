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
                Section::make(__('filament.widgets.chatwoot.contact_infolist.heading'))
                    ->headerActions([
                        Action::make('syncCustomerFromContact')
                            ->label(__('filament.widgets.chatwoot.contact_infolist.actions.sync_customer'))
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
                            ->placeholder(__('filament.widgets.chatwoot.contact_infolist.placeholders.name')),
                        TextEntry::make('created_at')
                            ->inlineLabel()
                            ->placeholder(__('filament.widgets.chatwoot.contact_infolist.placeholders.created_at'))
                            ->since(),
                        TextEntry::make('email')
                            ->inlineLabel()
                            ->placeholder(__('filament.widgets.chatwoot.contact_infolist.placeholders.email')),
                        TextEntry::make('phone_number')
                            ->inlineLabel()
                            ->placeholder(__('filament.widgets.chatwoot.contact_infolist.placeholders.phone_number')),
                        TextEntry::make('additional_attributes.country_code')
                            ->inlineLabel()
                            ->badge()
                            ->placeholder(__('filament.widgets.chatwoot.contact_infolist.placeholders.country_code')),
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
                ->title(__('filament.widgets.chatwoot.contact_infolist.notifications.missing_context.title'))
                ->body(__('filament.widgets.chatwoot.contact_infolist.notifications.missing_context.body'))
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
                ->title(__('filament.widgets.stripe.notifications.create_customer.load_contact_failed.title'))
                ->body(__('filament.widgets.stripe.notifications.create_customer.load_contact_failed.body'))
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
                    ->title(__('filament.widgets.stripe.notifications.create_customer.failed.title'))
                    ->body(__('filament.widgets.stripe.notifications.create_customer.failed.body'))
                    ->danger()
                    ->send();

                return;
            }

            $this->dashboardContext()->storeStripe(new StripeContext($customer->id));

            Notification::make()
                ->title(__('filament.widgets.stripe.notifications.create_customer.success.title'))
                ->body(__('filament.widgets.stripe.notifications.create_customer.success.body'))
                ->success()
                ->send();

            $this->reset();

            return;
        }

        if ($payload === []) {
            Notification::make()
                ->title(__('filament.widgets.chatwoot.contact_infolist.notifications.no_contact_details.title'))
                ->body(__('filament.widgets.chatwoot.contact_infolist.notifications.no_contact_details.body'))
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
            ->title(__('filament.widgets.chatwoot.contact_infolist.notifications.syncing_customer.title'))
            ->body(__('filament.widgets.chatwoot.contact_infolist.notifications.syncing_customer.body'))
            ->info()
            ->send();

        $this->reset();
    }
}
