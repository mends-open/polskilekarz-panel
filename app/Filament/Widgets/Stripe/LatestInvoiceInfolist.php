<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseSchemaWidget;
use App\Filament\Widgets\Stripe\Concerns\InterpretsStripeAmounts;
use App\Filament\Widgets\Stripe\Concerns\HasStripeInvoiceForm;
use App\Filament\Widgets\Stripe\Concerns\InteractsWithStripeInvoices;
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

class LatestInvoiceInfolist extends BaseSchemaWidget
{
    use InteractsWithDashboardContext;
    use InterpretsStripeAmounts;
    use HasStripeInvoiceForm;
    use InteractsWithStripeInvoices;

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

        try {
            return $this->latestStripeInvoice($customerId, [
                'expand' => ['data.payment_intent'],
            ]);
        } catch (ApiErrorException $exception) {
            report($exception);

            return [];
        }
    }

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady();
    }

    protected function afterInvoiceFormHandled(): void
    {
        $this->refreshLatestInvoice();
    }

    #[On('stripe.invoices.refresh')]
    public function refreshLatestInvoice(): void
    {
        unset($this->latestInvoice);
        unset($this->stripePriceCollection, $this->stripeProductCollection);
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->refreshLatestInvoice();
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
                        $this->configureInvoiceFormAction(
                            Action::make('duplicateLatest')
                                ->label('Duplicate')
                                ->icon(Heroicon::OutlinedDocumentDuplicate)
                                ->outlined()
                                ->color(blank($invoice) ? 'gray' : 'primary')
                                ->disabled(blank($invoice))
                                ->modalIcon(Heroicon::OutlinedDocumentDuplicate)
                                ->modalHeading('Duplicate latest invoice')
                        )
                            ->fillForm(fn () => $this->getInvoiceFormDefaults(blank($invoice) ? null : $invoice)),
                        Action::make('sendLatest')
                            ->label('Send')
                            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                            ->outlined()
                            ->color(blank($invoice) ? 'gray' : 'warning')
                            ->disabled(blank($invoice))
                            ->action(fn () => $this->sendHostedInvoiceLink($invoice)),
                        Action::make('openInvoice')
                            ->label('Open')
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->url($hostedUrl)
                            ->openUrlInNewTab()
                            ->hidden(blank($hostedUrl)),
                        Action::make('reset')
                            ->action(fn () => $this->refreshLatestInvoice())
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
