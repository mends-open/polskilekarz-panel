<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseTableWidget;
use App\Jobs\Chatwoot\CreateInvoiceShortLink;
use App\Support\Dashboard\DashboardContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Livewire\Attributes\On;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Stripe\Exception\ApiErrorException;

class InvoicesTable extends BaseTableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Invoices';

    protected DashboardContext $dashboardContext;

    public function boot(DashboardContext $dashboardContext): void
    {
        $this->dashboardContext = $dashboardContext;
    }
    public function isReady(): bool
    {
        return $this->dashboardContext->isReady();
    }

    #[On('reset')]
    public function resetComponent(): void
    {
        $this->reset();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->getCustomerInvoices())
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('id')
                            ->color('gray')
                            ->badge(),
                        TextColumn::make('number')
                            ->badge(),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('total')
                            ->badge()
                            ->money(fn ($record) => $record['currency'], 100)
                            ->color(fn ($record) => match ($record['status']) {
                                'paid' => 'success',                     // ✅ money in
                                'open', 'draft', 'uncollectible' => 'danger', // ❌ not collected
                                'void' => 'gray',                        // ⚪ cancelled
                                default => 'secondary',
                            }),
                        TextColumn::make('status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'paid' => 'success',        // green
                                'open' => 'info',           // blue
                                'draft' => 'secondary',     // neutral
                                'uncollectible' => 'danger',// red
                                'void' => 'gray',           // gray
                                default => 'secondary',
                            }),
                    ])->space(2),
                    TextColumn::make('created')
                        ->since(),
                ]),
                Panel::make([
                    Split::make([
                        TextColumn::make('lines.data.*.description')
                            ->listWithLineBreaks(),
                        TextColumn::make('lines.data.*.quantity')
                            ->prefix('x')
                            ->listWithLineBreaks(),
                        TextColumn::make('lines.data.*.amount')
                            ->listWithLineBreaks()
                            ->money(fn ($record) => $record['currency'], 100)
                            ->badge(),
                    ]),
                ])->collapsible(),
            ])
            ->filters([])
            ->headerActions([
                Action::make('create')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->color('success')
                    ->outlined(),
                Action::make('duplicateLatest')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->outlined()
                    ->color(fn () => $this->getCustomerInvoices() == [] ? 'gray' : 'primary')
                    ->disabled(fn () => $this->getCustomerInvoices() == []),
                Action::make('sendLatest')
                    ->outlined()
                    ->color(fn () => $this->getCustomerInvoices() == [] ? 'gray' : 'primary')
                    ->disabled(fn () => $this->getCustomerInvoices() == [])
                    ->action(fn () => $this->sendLatestInvoice()),
                Action::make('reset')
                    ->action(fn () => $this->reset())
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->link(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('duplicateInvoice')
                        ->label('Duplicate')
                        ->icon(Heroicon::OutlinedDocumentDuplicate),
                    Action::make('sendInvoiceShortUrl')
                        ->action(fn ($record) => $this->sendShortUrl($record['hosted_invoice_url']))
                        ->label('Send')
                        ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                        ->requiresConfirmation(),
                    Action::make('openInvoiceUrl')
                        ->url(fn ($record) => $record['hosted_invoice_url'])
                        ->openUrlInNewTab()
                        ->label('Open')
                        ->icon(Heroicon::OutlinedEnvelopeOpen),
                ]),
            ])
            ->toolbarActions([]);
    }

    private function sendShortUrl(string $url): void
    {
        $context = $this->dashboardContext->chatwoot();

        $account = $context->accountId;
        $user = $context->currentUserId;
        $conversation = $context->conversationId;

        if (! $account || ! $user || ! $conversation) {
            Notification::make()
                ->title('Missing Chatwoot context')
                ->body('Unable to send the invoice link because the Chatwoot context is incomplete.')
                ->danger()
                ->send();

            return;
        }

        CreateInvoiceShortLink::dispatch(
            url: $url,
            accountId: $account,
            conversationId: $conversation,
            impersonatorId: $user,
            notifiableId: auth()->id(),
        );

        Notification::make()
            ->title('Sending invoice link')
            ->body('We are preparing the invoice link and will send it to the conversation shortly.')
            ->info()
            ->send();

    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ApiErrorException
     */
    private function getCustomerInvoices(): array
    {
        $customerId = $this->dashboardContext->stripe()->customerId;

        return $customerId ? stripe()->invoices->all(['customer' => $customerId])->toArray()['data'] : [];
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->resetTable();
    }

    private function sendLatestInvoice(): void
    {
        $invoices = $this->getCustomerInvoices();

        if ($invoices === []) {
            return;
        }

        $latest = collect($invoices)
            ->sortByDesc('created')
            ->first();

        $invoiceUrl = data_get($latest, 'hosted_invoice_url');

        if (blank($invoiceUrl)) {
            Notification::make()
                ->title('Invoice link unavailable')
                ->body('We could not find a hosted invoice URL on the latest invoice.')
                ->warning()
                ->send();

            return;
        }

        $this->sendShortUrl($invoiceUrl);
    }
}
