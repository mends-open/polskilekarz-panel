<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseSchemaWidget;
use App\Jobs\Stripe\CreateCustomerPortalSessionLink;
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
use Stripe\Customer;
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
    protected function stripeCustomer(): ?Customer
    {
        $customerId = $this->stripeContext()->customerId;

        return $customerId
            ? stripe()->customers->retrieve($customerId)
            : null;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ApiErrorException
     * @throws NotFoundExceptionInterface
     */
    public function schema(Schema $schema): Schema
    {
        $chatwootContext = $this->chatwootContext();
        $portalReady = $this->stripeContext()->hasCustomer()
            && $chatwootContext->accountId !== null
            && $chatwootContext->conversationId !== null
            && $chatwootContext->currentUserId !== null;

        $customer = $this->stripeCustomer;

        return $schema
            ->state($customer?->toArray() ?? [])
            ->components([
                Section::make('customer')
                    ->headerActions([
                        Action::make('sendCustomerPortalLink')
                            ->label('Send portal link')
                            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                            ->outlined()
                            ->requiresConfirmation()
                            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
                            ->modalHeading('Send portal link?')
                            ->modalDescription('We will create a Stripe customer portal session and send the short link in Chatwoot.')
                            ->color($portalReady ? 'warning' : 'gray')
                            ->disabled(! $portalReady)
                            ->action(fn () => $this->sendCustomerPortalLink()),
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
                        TextEntry::make('address.country')
                            ->state(fn ($record) => Str::upper((string) Arr::get($record, 'address.country')))
                            ->inlineLabel()
                            ->badge()
                            ->placeholder('No country'),
                    ]),
            ]);
    }

    protected function sendCustomerPortalLink(): void
    {
        $stripeContext = $this->stripeContext();
        $chatwootContext = $this->chatwootContext();

        $customerId = $stripeContext->customerId;
        $accountId = $chatwootContext->accountId;
        $conversationId = $chatwootContext->conversationId;
        $impersonatorId = $chatwootContext->currentUserId;

        if (! $customerId) {
            Notification::make()
                ->title('Missing Stripe customer')
                ->body('We could not find the Stripe customer. Please select a customer first.')
                ->danger()
                ->send();

            return;
        }

        if ($accountId === null || $conversationId === null || $impersonatorId === null) {
            Notification::make()
                ->title('Missing Chatwoot context')
                ->body('We need a Chatwoot conversation to send the portal link. Please open this widget from a Chatwoot conversation.')
                ->danger()
                ->send();

            return;
        }

        CreateCustomerPortalSessionLink::dispatch(
            customerId: $customerId,
            accountId: $accountId,
            conversationId: $conversationId,
            impersonatorId: $impersonatorId,
            notifiableId: auth()->id(),
        );

        Notification::make()
            ->title('Generating portal link')
            ->body('We are generating a Stripe customer portal session and will send the link shortly.')
            ->info()
            ->send();

        $this->reset();
    }
}
