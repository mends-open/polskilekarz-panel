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
use Stripe\Exception\ApiErrorException;

class CustomerInfolist extends BaseSchemaWidget
{
    use InteractsWithDashboardContext;

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
    protected function stripeCustomer(): array
    {
        $customerId = $this->stripeContext()->customerId;

        return $customerId
            ? stripe()->customers->retrieve($customerId)->toArray()
            : [];
    }

    public function schema(Schema $schema): Schema
    {
        $chatwootContext = $this->chatwootContext();
        $customerReady = $this->stripeContext()->hasCustomer();
        $portalReady = $customerReady
            && $chatwootContext->accountId !== null
            && $chatwootContext->conversationId !== null
            && $chatwootContext->currentUserId !== null;

        return $schema
            ->state($this->stripeCustomer)
            ->components([
                Section::make('customer')
                    ->headerActions([
                        Action::make('sendPortalLink')
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
                        Action::make('openPortal')
                            ->label('Open portal')
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->outlined()
                            ->color($customerReady ? 'primary' : 'gray')
                            ->disabled(! $customerReady)
                            ->url($this->getCustomerPortalLink())
                            ->openUrlInNewTab(),
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
                            ->label('Country')
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

    protected function getCustomerPortalLink(): ?string
    {
        $customerId = $this->stripeContext()->customerId;

        if (! $customerId) {
            Notification::make()
                ->title('Missing Stripe customer')
                ->body('We could not find the Stripe customer. Please select a customer first.')
                ->danger()
                ->send();

            return null;
        }

        $config = config('stripe.customer_portal', []);

        try {
            $session = stripe()->billingPortal->sessions->create(array_filter([
                'customer' => $customerId,
                'return_url' => Arr::get($config, 'return_url'),
                'locale' => Arr::get($config, 'locale', 'auto'),
            ], static fn ($value) => $value !== null && $value !== ''));
        } catch (ApiErrorException $exception) {
            report($exception);

            Notification::make()
                ->title('Failed to open portal')
                ->body('We were unable to open the customer portal. Please try again.')
                ->danger()
                ->send();

            return null;
        }

        return $session->url;
    }
}
