<?php

namespace App\Jobs\Stripe;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

class CreateInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $priceIds
     */
    public function __construct(
        public readonly string $customerId,
        public readonly array $priceIds,
        public readonly ?int $notifiableId,
    ) {}

    /**
     * @throws ApiErrorException
     */
    public function handle(StripeClient $stripe): void
    {
        if ($this->priceIds === []) {
            return;
        }

        $invoice = $stripe->invoices->create([
            'customer' => $this->customerId,
            'pending_invoice_items_behavior' => 'exclude',
            'collection_method' => 'send_invoice',
            'days_until_due' => 0,
            'auto_advance' => true,
        ]);

        foreach ($this->priceIds as $priceId) {
            $stripe->invoiceItems->create([
                'customer' => $this->customerId,
                'invoice' => $invoice->id,
                'pricing' => [
                    'price' => $priceId
                ],
            ]);
        }

        $finalized = $stripe->invoices->finalizeInvoice($invoice->id);

        $formattedTotal = $this->formatInvoiceTotal($finalized->total ?? null, $finalized->currency ?? null);
        $invoiceReference = $finalized->number ?? $finalized->id;

        $this->notify(
            title: 'Invoice created',
            body: $formattedTotal
                ? sprintf('Invoice %s for %s has been created.', $invoiceReference, $formattedTotal)
                : sprintf('Invoice %s has been created.', $invoiceReference),
            status: 'success',
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Failed to create Stripe invoice', [
            'customer_id' => $this->customerId,
            'price_ids' => $this->priceIds,
            'exception' => $exception,
        ]);

        $this->notify(
            title: 'Failed to create invoice',
            body: 'We were unable to create the invoice in Stripe. Please try again.',
            status: 'danger',
        );
    }

    protected function notify(string $title, string $body, string $status): void
    {
        $user = $this->resolveNotifiable();

        if (! $user) {
            return;
        }

        Auth::setUser($user);

        $guard = Filament::auth();

        if (method_exists($guard, 'setUser')) {
            $guard->setUser($user);
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->status($status)
            ->broadcast($user);
    }

    protected function resolveNotifiable(): ?User
    {
        if (! $this->notifiableId) {
            return null;
        }

        return User::find($this->notifiableId);
    }

    private function formatInvoiceTotal(?int $amount, ?string $currency): ?string
    {
        if ($amount === null || blank($currency)) {
            return null;
        }

        $currency = strtoupper($currency);
        $divisor = $this->isZeroDecimalCurrency($currency) ? 1 : 100;

        return Number::currency($amount / $divisor, $currency);
    }

    private function isZeroDecimalCurrency(string $currency): bool
    {
        return in_array($currency, [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG',
            'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
        ], true);
    }
}
