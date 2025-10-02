<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseSchemaWidget;
use App\Jobs\Stripe\SyncCustomerFromChatwootContact;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Stripe\Exception\ApiErrorException;

class CustomerInfolist extends BaseSchemaWidget
{
    use InteractsWithDashboardContext;

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
     * @throws ApiErrorException
     */
    #[Computed(persist: true)]
    public function stripeCustomer(): array
    {
        $customerId = $this->stripeContext()->customerId;

        return $customerId ? stripe()->customers->retrieve($customerId)->toArray() : [];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ApiErrorException
     * @throws NotFoundExceptionInterface
     */
    public function schema(Schema $schema): Schema
    {
        $contactReady = $this->chatwootContext()->hasContact();

        return $schema
            ->state($this->stripeCustomer)
            ->components([
                Section::make('customer')
                    ->headerActions([
                        Action::make('fetchFromContact')
                            ->outlined()
                            ->color($contactReady ? 'primary' : 'gray')
                            ->disabled(! $contactReady)
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
                        TextEntry::make('created')
                            ->inlineLabel()
                            ->placeholder('No created')
                            ->since(),
                        TextEntry::make('email')
                            ->inlineLabel()
                            ->placeholder('No email'),
                        TextEntry::make('phone')
                            ->inlineLabel()
                            ->placeholder('No phone'),
                        TextEntry::make('currency')
                            ->state(fn ($record) => Str::upper(Arr::get($record, 'currency')))
                            ->inlineLabel()
                            ->badge()
                            ->placeholder('No currency'),
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

        if (! $customerId) {
            Notification::make()
                ->title('Missing Stripe context')
                ->body('We could not find the Stripe customer to update. Please select a customer first.')
                ->danger()
                ->send();

            return;
        }

        if (! $accountId || ! $contactId || ! $impersonatorId) {
            Notification::make()
                ->title('Missing Chatwoot context')
                ->body('We could not find the Chatwoot contact details. Please open this widget from a Chatwoot conversation.')
                ->danger()
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
