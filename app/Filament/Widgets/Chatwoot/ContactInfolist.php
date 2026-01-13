<?php

namespace App\Filament\Widgets\Chatwoot;

use App\Filament\Widgets\BaseSchemaWidget;
use App\Jobs\Stripe\SyncCustomerFromChatwootContact;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use App\Support\Dashboard\Concerns\RefreshesDashboardContextOnBoot;
use App\Support\Dashboard\StripeContext;
use App\Support\Metadata\Metadata;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Stripe\Exception\ApiErrorException;

class ContactInfolist extends BaseSchemaWidget
{
    use InteractsWithDashboardContext;
    use RefreshesDashboardContextOnBoot;

    /**
     * @throws RequestException
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

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady(
            fn (): bool => $this->chatwootContext()->canImpersonate(),
        );
    }

    #[On('reset')]
    public function resetComponent(): void
    {
        $this->reset();
    }

    /**
     * @throws ConnectionException
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
                Section::make(__('filament.widgets.chatwoot.contact_infolist.section.title'))
                    ->headerActions([
                        Action::make('syncCustomerFromContact')
                            ->label(__('filament.widgets.chatwoot.contact_infolist.actions.sync_customer_from_contact.label'))
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
                            ->label(__('filament.widgets.chatwoot.contact_infolist.fields.id.label'))
                            ->placeholder(__('filament.widgets.chatwoot.contact_infolist.fields.id.placeholder'))
                            ->badge()
                            ->color('gray')
                            ->inlineLabel(),
                        TextEntry::make('name')
                            ->label(__('filament.widgets.chatwoot.contact_infolist.fields.name.label'))
                            ->inlineLabel()
                            ->placeholder(__('filament.widgets.common.placeholders.name')),
                        TextEntry::make('created_at')
                            ->label(__('filament.widgets.chatwoot.contact_infolist.fields.created_at.label'))
                            ->inlineLabel()
                            ->placeholder(__('filament.widgets.common.placeholders.created_at'))
                            ->since(),
                        TextEntry::make('email')
                            ->label(__('filament.widgets.chatwoot.contact_infolist.fields.email.label'))
                            ->inlineLabel()
                            ->placeholder(__('filament.widgets.common.placeholders.email')),
                        TextEntry::make('phone_number')
                            ->label(__('filament.widgets.chatwoot.contact_infolist.fields.phone_number.label'))
                            ->inlineLabel()
                            ->placeholder(__('filament.widgets.common.placeholders.phone')),
                        TextEntry::make('additional_attributes.country_code')
                            ->label(__('filament.widgets.chatwoot.contact_infolist.fields.country_code.label'))
                            ->inlineLabel()
                            ->badge()
                            ->placeholder(__('filament.widgets.common.placeholders.country')),
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
                ->title(__('notifications.chatwoot.contact_infolist.missing_context.title'))
                ->body(__('notifications.chatwoot.contact_infolist.missing_context.body'))
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
                ->title(__('notifications.chatwoot.contact_infolist.load_failed.title'))
                ->body(__('notifications.chatwoot.contact_infolist.load_failed.body'))
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

        $metadata = $this->chatwootMetadata(
            $customerId ? [Metadata::KEY_STRIPE_CUSTOMER_ID => $customerId] : [],
        );

        if ($metadata !== []) {
            $payload['metadata'] = $metadata;
        }

        if (! $customerId) {
            try {
                $customer = stripe()->customers->create($payload);
            } catch (ApiErrorException $exception) {
                report($exception);

                Notification::make()
                    ->title(__('notifications.chatwoot.contact_infolist.create_customer_failed.title'))
                    ->body(__('notifications.chatwoot.contact_infolist.create_customer_failed.body'))
                    ->danger()
                    ->send();

                return;
            }

            $this->dashboardContext()->storeStripe(new StripeContext($customer->id));

            Notification::make()
                ->title(__('notifications.chatwoot.contact_infolist.customer_created.title'))
                ->body(__('notifications.chatwoot.contact_infolist.customer_created.body'))
                ->success()
                ->send();

            $this->reset();

            return;
        }

        if ($payload === []) {
            Notification::make()
                ->title(__('notifications.chatwoot.contact_infolist.nothing_to_sync.title'))
                ->body(__('notifications.chatwoot.contact_infolist.nothing_to_sync.body'))
                ->warning()
                ->send();

            return;
        }

        SyncCustomerFromChatwootContact::dispatch(
            accountId: $accountId,
            contactId: $contactId,
            impersonatorId: $impersonatorId,
            customerId: $customerId,
            metadata: $this->chatwootMetadata([
                Metadata::KEY_STRIPE_CUSTOMER_ID => $customerId,
            ]),
            notifiableId: auth()->id(),
        );

        Notification::make()
            ->title(__('notifications.chatwoot.contact_infolist.syncing.title'))
            ->body(__('notifications.chatwoot.contact_infolist.syncing.body'))
            ->info()
            ->send();

        $this->reset();
    }
}
