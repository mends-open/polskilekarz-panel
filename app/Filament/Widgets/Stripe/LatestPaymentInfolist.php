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

class LatestPaymentInfolist extends BaseSchemaWidget
{
    use InteractsWithDashboardContext;
    use InterpretsStripeAmounts;

    /**
     * @throws ApiErrorException
     */
    #[Computed(persist: true)]
    protected function latestPayment(): array
    {
        $customerId = (string) $this->stripeContext()->customerId;

        if ($customerId === '') {
            return [];
        }

        $response = stripe()->paymentIntents->all([
            'customer' => $customerId,
            'limit' => 1,
            'expand' => ['data.latest_charge'],
        ]);

        $payment = $response->data[0] ?? null;

        if ($payment instanceof StripeObject) {
            return $payment->toArray();
        }

        return is_array($payment) ? $payment : [];
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
        $payment = $this->latestPayment();
        $receiptUrl = Arr::get($payment, 'charges.data.0.receipt_url');

        return $schema
            ->state($payment)
            ->components([
                Section::make('latest payment')
                    ->headerActions([
                        Action::make('openReceipt')
                            ->label('Open receipt')
                            ->icon(Heroicon::OutlinedEnvelopeOpen)
                            ->url($receiptUrl)
                            ->openUrlInNewTab()
                            ->hidden(blank($receiptUrl)),
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
                                'succeeded' => 'success',
                                'processing' => 'warning',
                                'requires_payment_method',
                                'requires_capture',
                                'requires_action',
                                'requires_confirmation' => 'danger',
                                'canceled' => 'gray',
                                default => 'secondary',
                            })
                            ->inlineLabel()
                            ->placeholder('No status'),
                        TextEntry::make('amount')
                            ->label('Amount')
                            ->state(fn (?array $record): ?float => $this->extractStripeAmount($record, 'amount'))
                            ->money(fn (?array $record): ?string => $this->resolveStripeCurrency($record))
                            ->inlineLabel()
                            ->placeholder('No amount'),
                        TextEntry::make('amount_received')
                            ->label('Amount received')
                            ->state(fn (?array $record): ?float => $this->extractStripeAmount($record, 'amount_received'))
                            ->money(fn (?array $record): ?string => $this->resolveStripeCurrency($record))
                            ->inlineLabel()
                            ->placeholder('No amount received'),
                        TextEntry::make('amount_capturable')
                            ->label('Amount capturable')
                            ->state(fn (?array $record): ?float => $this->extractStripeAmount($record, 'amount_capturable'))
                            ->money(fn (?array $record): ?string => $this->resolveStripeCurrency($record))
                            ->inlineLabel()
                            ->placeholder('No amount capturable'),
                        TextEntry::make('payment_method_types')
                            ->label('Payment method types')
                            ->state(fn (?array $record): ?string => value(function () use ($record): ?string {
                                $types = Arr::get($record ?? [], 'payment_method_types');

                                if (! is_array($types) || $types === []) {
                                    return null;
                                }

                                return implode(', ', $types);
                            }))
                            ->inlineLabel()
                            ->placeholder('No payment methods'),
                        TextEntry::make('created')
                            ->inlineLabel()
                            ->placeholder('No created date')
                            ->since(),
                    ]),
            ]);
    }
}
