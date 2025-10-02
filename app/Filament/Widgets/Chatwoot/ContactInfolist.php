<?php

namespace App\Filament\Widgets\Chatwoot;

use App\Filament\Widgets\BaseSchemaWidget;
use App\Jobs\Stripe\SyncCustomerFromChatwootContact;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Notifications\Notification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Livewire\Attributes\On;
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
    protected function getChatwootContact(): array
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
        $stripeContext = $this->stripeContext();

        $syncReady = $chatwootContext->accountId !== null
            && $chatwootContext->contactId !== null
            && $chatwootContext->currentUserId !== null
            && $stripeContext->hasCustomer();

        return $schema
            ->state($this->getChatwootContact())
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
