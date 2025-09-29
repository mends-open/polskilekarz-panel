<?php

namespace App\Filament\Widgets\Stripe;

use App\Jobs\Chatwoot\CreateInvoiceShortLink;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Livewire\Attributes\On;
use Phiki\Phast\Text;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Stripe\Exception\ApiErrorException;

class InvoicesTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Invoices';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->getCustomerInvoices())
            ->emptyState(view('empty-state'))
            ->columns([
                TextColumn::make('id')
                    ->fontFamily(FontFamily::Mono),
                TextColumn::make('number'),
                TextColumn::make('lines.data.*.description'),
                TextColumn::make('total')
                    ->state(fn ($record) => $record['total'] / 100)
                    ->badge()
                    ->money(fn ($record) => $record['currency'])
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
                TextColumn::make('created')
                    ->since(),
            ])
            ->filters([])
            ->headerActions([
                Action::make('create'),
                Action::make('duplicateLatest')
                    ->disabled(fn () => $this->getCustomerInvoices() == []),
                Action::make('sendLatest')
                    ->disabled(fn () => $this->getCustomerInvoices() == []),
            ])
            ->recordActions([
                Action::make('duplicateInvoice')
                    ->label('Duplicate')
                    ->icon(Heroicon::OutlinedChevronDoubleUp),
                Action::make('sendInvoiceShortUrl')
                    ->action(fn ($record) => $this->sendShortUrl($record['hosted_invoice_url']))
                    ->label('Send')
                    ->icon(Heroicon::OutlinedArrowUpRight)
                    ->requiresConfirmation(),
                Action::make('openInvoiceUrl')
                    ->url(fn ($record) => $record['hosted_invoice_url'])
                    ->openUrlInNewTab()
                    ->label('Open')
                    ->icon(Heroicon::OutlinedEnvelopeOpen),
            ])
            ->toolbarActions([]);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws RequestException
     * @throws ConnectionException
     */
    private function sendShortUrl(string $url): void
    {
        $account = session()->get('chatwoot.account_id');
        $user = session()->get('chatwoot.current_user_id');
        $conversation = session()->get('chatwoot.conversation_id');

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
        if (! session()->has('stripe.customer_id')) {
            return [];
        }

        $customer = session()->get('stripe.customer_id');

        return stripe()->invoices->all([
            'customer' => $customer,
        ])->toArray()['data'];
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->resetTable();
    }
}
