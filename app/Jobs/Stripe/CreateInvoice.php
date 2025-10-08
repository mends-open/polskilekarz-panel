<?php

namespace App\Jobs\Stripe;

use App\Models\User;
use App\Support\Metadata\MetadataPayload;
use App\Support\Stripe\Currency;
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

    public function __construct(
        public readonly string $customerId,
        public readonly ?string $currency,
        /** @var array<int, array{price: string, quantity: int}> */
        public readonly array $lineItems,
        /** @var array<string, string> */
        public readonly array $metadata = [],
        public readonly ?int $notifiableId,
    ) {}

    /**
     * @throws ApiErrorException
     */
    public function handle(StripeClient $stripe): void
    {
        if ($this->lineItems === []) {
            return;
        }

        $metadata = MetadataPayload::from($this->metadata);
        $metadataArray = $metadata->toArray();

        $payload = [
            'customer' => $this->customerId,
            'pending_invoice_items_behavior' => 'exclude',
            'collection_method' => 'send_invoice',
            'days_until_due' => 0,
            'auto_advance' => true,
        ];

        if ($this->currency) {
            $payload['currency'] = $this->currency;
        }

        if ($metadataArray !== []) {
            $payload['metadata'] = $metadataArray;
        }

        $invoice = $stripe->invoices->create($payload);

        $invoiceMetadata = $metadata;
        $invoiceMetadataArray = $metadataArray;

        if (is_string($invoice->id) && $invoice->id !== '') {
            $invoiceMetadata = $invoiceMetadata->with([
                MetadataPayload::KEY_STRIPE_INVOICE_ID => $invoice->id,
            ]);

            $invoiceMetadataArray = $invoiceMetadata->toArray();

            if ($invoiceMetadataArray !== $metadataArray) {
                $stripe->invoices->update($invoice->id, [
                    'metadata' => $invoiceMetadataArray,
                ]);
            }

            $metadataArray = $invoiceMetadataArray;
        }

        foreach ($this->lineItems as $lineItem) {
            $priceId = $lineItem['price'] ?? null;
            $quantity = (int) ($lineItem['quantity'] ?? 1);

            if (! is_string($priceId) || $priceId === '') {
                continue;
            }

            if ($quantity < 1) {
                $quantity = 1;
            }

            $invoiceItemPayload = [
                'customer' => $this->customerId,
                'invoice' => $invoice->id,
                'quantity' => $quantity,
                'pricing' => [
                    'price' => $priceId,
                ],
            ];

            if ($metadataArray !== []) {
                $invoiceItemPayload['metadata'] = $metadataArray;
            }

            $stripe->invoiceItems->create($invoiceItemPayload);
        }

        $finalized = $stripe->invoices->finalizeInvoice($invoice->id);

        $formattedTotal = $this->formatInvoiceTotal($finalized->total ?? null, $finalized->currency ?? null);
        $invoiceReference = $finalized->number ?? $finalized->id;

        $body = $formattedTotal
            ? __('notifications.jobs.stripe.create_invoice.success.with_total.body', [
                'invoice' => $invoiceReference,
                'amount' => $formattedTotal,
            ])
            : __('notifications.jobs.stripe.create_invoice.success.body', [
                'invoice' => $invoiceReference,
            ]);

        $this->notify(
            title: __('notifications.jobs.stripe.create_invoice.success.title'),
            body: $body,
            status: 'success',
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Failed to create Stripe invoice', [
            'customer_id' => $this->customerId,
            'currency' => $this->currency,
            'line_items' => $this->lineItems,
            'metadata' => $this->metadata,
            'exception' => $exception,
        ]);

        $this->notify(
            title: __('notifications.jobs.stripe.create_invoice.failed.title'),
            body: __('notifications.jobs.stripe.create_invoice.failed.body'),
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
        $divisor = Currency::divisor($currency);

        return Number::currency($amount / $divisor, $currency);
    }

}
