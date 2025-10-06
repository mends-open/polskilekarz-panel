<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseSchemaWidget;
use App\Filament\Widgets\Stripe\Concerns\InterpretsStripeAmounts;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeObject;

class LatestInvoiceInfolist extends BaseSchemaWidget
{
    use InteractsWithDashboardContext;
    use InterpretsStripeAmounts;

    /**
     * @throws ApiErrorException
     */
    #[Computed(persist: true)]
    protected function latestInvoice(): array
    {
        $customerId = (string) $this->stripeContext()->customerId;

        if ($customerId === '') {
            return [];
        }

        $response = stripe()->invoices->all([
            'customer' => $customerId,
            'limit' => 1,
            'expand' => ['data.payment_intent'],
        ]);

        $invoice = $response->data[0] ?? null;

        if ($invoice instanceof StripeObject) {
            return $invoice->toArray();
        }

        return is_array($invoice) ? $invoice : [];
    }

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    #[On('reset')]
    public function resetComponent(): void
    {
        $this->reset();
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->reset();
    }

    /**
     * @throws ApiErrorException
     */
    public function schema(Schema $schema): Schema
    {
        $invoice = $this->latestInvoice();
        $hostedUrl = Arr::get($invoice, 'hosted_invoice_url');

        return $schema
            ->state($invoice)
            ->components([
                Section::make('latest invoice')
                    ->headerActions([
                        Action::make('create')
                            ->label('Create new')
                            ->icon(Heroicon::OutlinedDocumentPlus)
                            ->color('success')
                            ->outlined()
                            ->action(fn () => $this->dispatch('stripe.invoices.mount-action', 'create')),
                        Action::make('duplicateLatest')
                            ->label('Duplicate')
                            ->icon(Heroicon::OutlinedDocumentDuplicate)
                            ->outlined()
                            ->color(blank($invoice) ? 'gray' : 'primary')
                            ->disabled(blank($invoice))
                            ->action(fn () => $this->dispatch('stripe.invoices.mount-action', 'duplicateLatest')),
                        Action::make('sendLatest')
                            ->label('Send')
                            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                            ->outlined()
                            ->color(blank($invoice) ? 'gray' : 'warning')
                            ->disabled(blank($invoice))
                            ->action(fn () => $this->dispatch('stripe.invoices.mount-action', 'sendLatest')),
                        Action::make('openInvoice')
                            ->label('Open')
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->url($hostedUrl)
                            ->openUrlInNewTab()
                            ->hidden(blank($hostedUrl)),
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
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state) => match ($state) {
                                'draft' => 'gray',
                                'open' => 'warning',
                                'paid' => 'success',
                                'uncollectible' => 'danger',
                                'void' => 'gray',
                                default => 'secondary',
                            })
                            ->inlineLabel()
                            ->placeholder('No status'),
                        TextEntry::make('created')
                            ->inlineLabel()
                            ->placeholder('No created date')
                            ->since(),
                        TextEntry::make('total')
                            ->label('Total')
                            ->state(fn (?array $record): ?float => $this->extractStripeAmount($record, 'total'))
                            ->money(fn (?array $record): ?string => $this->resolveStripeCurrency($record))
                            ->inlineLabel()
                            ->placeholder('No total'),
                        TextEntry::make('amount_paid')
                            ->label('Amount paid')
                            ->state(fn (?array $record): ?float => $this->extractStripeAmount($record, 'amount_paid'))
                            ->money(fn (?array $record): ?string => $this->resolveStripeCurrency($record))
                            ->inlineLabel()
                            ->placeholder('No amount paid'),
                        TextEntry::make('amount_remaining')
                            ->label('Amount remaining')
                            ->state(fn (?array $record): ?float => $this->extractStripeAmount($record, 'amount_remaining'))
                            ->money(fn (?array $record): ?string => $this->resolveStripeCurrency($record))
                            ->inlineLabel()
                            ->placeholder('No amount remaining'),
                        TextEntry::make('collection_method')
                            ->inlineLabel()
                            ->placeholder('No collection method'),
                        TextEntry::make('customer_email')
                            ->inlineLabel()
                            ->placeholder('No customer email'),
                        TextEntry::make('hosted_invoice_url')
                            ->label('Hosted invoice URL')
                            ->inlineLabel()
                            ->placeholder('No hosted URL')
                            ->url(fn (?array $record): ?string => Arr::get($record ?? [], 'hosted_invoice_url'))
                            ->openUrlInNewTab(),
                    ]),
            ]);
    }
}
