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
use Illuminate\Support\Js;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Stripe\Exception\ApiErrorException;

class CustomerInfolist extends BaseSchemaWidget
{
    use InteractsWithDashboardContext;

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady(
            fn (): bool => $this->chatwootContext()->hasContact(),
        );
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
                Section::make(__('filament.widgets.stripe.customer_infolist.section.title'))
                    ->headerActions([
                        Action::make('sendPortalLink')
                            ->label(__('filament.widgets.stripe.customer_infolist.actions.send_portal_link.label'))
                            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                            ->outlined()
                            ->requiresConfirmation()
                            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
                            ->modalHeading(__('filament.widgets.stripe.customer_infolist.actions.send_portal_link.modal.heading'))
                            ->modalDescription(__('filament.widgets.stripe.customer_infolist.actions.send_portal_link.modal.description'))
                            ->color($portalReady ? 'warning' : 'gray')
                            ->disabled(! $portalReady)
                            ->action(fn () => $this->sendCustomerPortalLink()),
                        Action::make('openPortal')
                            ->label(__('filament.widgets.stripe.customer_infolist.actions.open_portal.label'))
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->outlined()
                            ->color($customerReady ? 'primary' : 'gray')
                            ->disabled(! $customerReady)
                            ->action(fn () => $this->openCustomerPortal()),
                    ])
                    ->schema([
                        TextEntry::make('id')
                            ->label(__('filament.widgets.stripe.customer_infolist.fields.id.label'))
                            ->badge()
                            ->color('gray')
                            ->inlineLabel(),
                        TextEntry::make('name')
                            ->label(__('filament.widgets.stripe.customer_infolist.fields.name.label'))
                            ->inlineLabel()
                            ->placeholder(__('filament.widgets.common.placeholders.name')),
                        TextEntry::make('created')
                            ->label(__('filament.widgets.stripe.customer_infolist.fields.created.label'))
                            ->inlineLabel()
                            ->placeholder(__('filament.widgets.common.placeholders.created_at'))
                            ->since(),
                        TextEntry::make('email')
                            ->label(__('filament.widgets.stripe.customer_infolist.fields.email.label'))
                            ->inlineLabel()
                            ->placeholder(__('filament.widgets.common.placeholders.email')),
                        TextEntry::make('phone')
                            ->label(__('filament.widgets.stripe.customer_infolist.fields.phone.label'))
                            ->inlineLabel()
                            ->placeholder(__('filament.widgets.common.placeholders.phone')),
                        TextEntry::make('address.country')
                            ->label(__('filament.widgets.stripe.customer_infolist.fields.address_country.label'))
                            ->inlineLabel()
                            ->badge()
                            ->placeholder(__('filament.widgets.common.placeholders.country')),
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
                ->title(__('notifications.stripe.customer_infolist.missing_customer.title'))
                ->body(__('notifications.stripe.customer_infolist.missing_customer.body'))
                ->danger()
                ->send();

            return;
        }

        if ($accountId === null || $conversationId === null || $impersonatorId === null) {
            Notification::make()
                ->title(__('notifications.stripe.customer_infolist.missing_chatwoot_context.title'))
                ->body(__('notifications.stripe.customer_infolist.missing_chatwoot_context.body'))
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
            ->title(__('notifications.stripe.customer_infolist.generating_portal_link.title'))
            ->body(__('notifications.stripe.customer_infolist.generating_portal_link.body'))
            ->info()
            ->send();

        $this->reset();
    }

    protected function openCustomerPortal(): void
    {
        $url = $this->createCustomerPortalSessionUrl();

        if (! $url) {
            return;
        }

        $this->js(sprintf("window.open(%s, '_blank')", Js::from($url)));
    }

    protected function createCustomerPortalSessionUrl(): ?string
    {
        $customerId = $this->stripeContext()->customerId;

        if (! $customerId) {
            Notification::make()
                ->title(__('notifications.stripe.customer_infolist.missing_customer.title'))
                ->body(__('notifications.stripe.customer_infolist.missing_customer.body'))
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
                ->title(__('notifications.stripe.customer_infolist.open_portal_failed.title'))
                ->body(__('notifications.stripe.customer_infolist.open_portal_failed.body'))
                ->danger()
                ->send();

            return null;
        }

        return $session->url;
    }
}
