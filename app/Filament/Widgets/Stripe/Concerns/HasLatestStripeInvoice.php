<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use Livewire\Attributes\Computed;
use Stripe\StripeObject;

use function rescue;

trait HasLatestStripeInvoice
{
    use InteractsWithStripeInvoices;

    protected ?array $latestInvoicePayloadCache = null;

    #[Computed(persist: true)]
    protected function latestInvoice(): ?StripeObject
    {
        return $this->latestInvoicePayload()['invoice'];
    }

    #[Computed(persist: true)]
    protected function latestInvoiceLines(): array
    {
        return $this->latestInvoicePayload()['lines'];
    }

    #[Computed(persist: true)]
    protected function latestInvoicePayments(): array
    {
        return $this->latestInvoicePayload()['payments'];
    }

    protected function clearLatestInvoiceCache(): void
    {
        $this->latestInvoicePayloadCache = null;
        unset($this->latestInvoice, $this->latestInvoiceLines, $this->latestInvoicePayments);
    }

    protected function latestInvoicePayload(): array
    {
        if (is_array($this->latestInvoicePayloadCache)) {
            return $this->latestInvoicePayloadCache;
        }

        $empty = [
            'invoice' => null,
            'lines' => [],
            'payments' => [],
        ];

        $customerId = (string) data_get($this->stripeContext(), 'customerId', '');

        if ($customerId === '') {
            return $this->latestInvoicePayloadCache = $empty;
        }

        return $this->latestInvoicePayloadCache = rescue(function () use ($customerId, $empty) {
            $invoice = $this->latestStripeInvoice($customerId);

            if (! $invoice instanceof StripeObject) {
                return $empty;
            }

            $invoiceId = (string) data_get($invoice, 'id', '');

            if ($invoiceId === '') {
                return array_replace($empty, ['invoice' => $invoice]);
            }

            return [
                'invoice' => $invoice,
                'lines' => $this->latestStripeInvoiceLines($invoiceId),
                'payments' => $this->latestStripeInvoicePayments($invoiceId),
            ];
        }, $empty, report: true);
    }
}
