<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseSchemaWidget;
use App\Filament\Widgets\Stripe\Concerns\HandlesCurrencyDecimals;
use App\Filament\Widgets\Stripe\Concerns\HasLatestStripeInvoice;
use App\Filament\Widgets\Stripe\Concerns\HasStripeInvoiceForm;
use App\Filament\Widgets\Stripe\Concerns\InterpretsStripeAmounts;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;

class LatestInvoiceInfolist extends BaseSchemaWidget
{
    use HandlesCurrencyDecimals;
    use HasLatestStripeInvoice;
    use HasStripeInvoiceForm;
    use InteractsWithDashboardContext;
    use InterpretsStripeAmounts;

    protected int|string|array $columnSpan = 'full';

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
        $this->clearLatestInvoiceCache();
        unset($this->stripePriceCollection, $this->stripeProductCollection);
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->refreshLatestInvoice();
    }

    public function schema(Schema $schema): Schema
    {
        $data = $this->latestInvoice;
        $currency = (string) data_get($data, 'currency');
        $divideBy = $currency === '' ? 100 : $this->currencyDivisor($currency);
        $decimalPlaces = $currency === '' ? 2 : $this->currencyDecimalPlaces($currency);

        return $schema
            ->state($data)
            ->components([
                Section::make('Latest Invoice')
                    ->columns(2)
                    ->headerActions([
                        $this->configureInvoiceFormAction(
                            Action::make('duplicateLatest')
                                ->label('Duplicate')
                                ->icon(Heroicon::OutlinedDocumentDuplicate)
                                ->outlined()
                                ->color(blank($data) ? 'gray' : 'primary')
                                ->disabled(blank($data))
                                ->modalHeading('Duplicate latest invoice')
                        )->fillForm(fn () => $this->getInvoiceFormDefaults(blank($data) ? null : $data)),
                        Action::make('sendLatest')
                            ->requiresConfirmation()
                            ->label('Send')
                            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                            ->outlined()
                            ->color(blank($data) ? 'gray' : 'warning')
                            ->disabled(blank($data))
                            ->action(fn () => $this->sendHostedInvoiceLink($data)),
                        Action::make('openInvoice')
                            ->label('Open')
                            ->outlined()
                            ->color(blank($data) ? 'gray' : 'primary')
                            ->disabled(blank($data))
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->url(data_get($data, 'hosted_invoice_url'))
                            ->openUrlInNewTab()
                            ->hidden(blank(data_get($data, 'hosted_invoice_url'))),
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
                            ->inlineLabel(),
                        TextEntry::make('created')
                            ->since()
                            ->inlineLabel(),
                        TextEntry::make('due_date')
                            ->since()
                            ->inlineLabel(),
                        TextEntry::make('total')
                            ->label('Total')
                            ->inlineLabel()
                            ->badge()
                            ->money(
                                currency: $currency,
                                divideBy: $divideBy,
                                locale: config('app.locale'),
                                decimalPlaces: $decimalPlaces,
                            ),
                        TextEntry::make('amount_paid')
                            ->label('Amount Paid')
                            ->inlineLabel()
                            ->color('success')
                            ->money(
                                currency: $currency,
                                divideBy: $divideBy,
                                locale: config('app.locale'),
                                decimalPlaces: $decimalPlaces,
                            )
                            ->badge(),
                        TextEntry::make('amount_remaining')
                            ->label('Amount Remaining')
                            ->color('danger')
                            ->inlineLabel()
                            ->money(
                                currency: $currency,
                                divideBy: $divideBy,
                                locale: config('app.locale'),
                                decimalPlaces: $decimalPlaces,
                            )
                            ->badge(),
                        TextEntry::make('collection_method')
                            ->inlineLabel()
                            ->badge()
                            ->color(fn (?string $state) => match ($state) {
                                'charge_automatically' => 'success',
                                'send_invoice' => 'warning',
                                default => 'secondary',
                            }),
                    ]),
            ]);
    }
}
