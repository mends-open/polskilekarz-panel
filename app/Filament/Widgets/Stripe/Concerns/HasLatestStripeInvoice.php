<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use Livewire\Attributes\Computed;
use Stripe\StripeObject;

use function rescue;

trait HasLatestStripeInvoice
{
    use InteractsWithStripeInvoices;

    #[Computed(persist: true)]
    protected function latestInvoice(): ?StripeObject
    {
        $customerId = (string) data_get($this->stripeContext(), 'customerId', '');

        if ($customerId === '') {
            return null;
        }

        return rescue(fn () => $this->latestStripeInvoice($customerId), null, report: true);
    }

    #[Computed(persist: true)]
    protected function latestInvoiceLines(): array
    {
        $invoice = $this->latestInvoice;

        $invoiceId = $invoice instanceof StripeObject
            ? (string) ($invoice->id ?? '')
            : '';

        if ($invoiceId === '') {
            return [];
        }

        return rescue(fn () => $this->latestStripeInvoiceLines($invoiceId), [], report: true);
    }

    #[Computed(persist: true)]
    protected function latestInvoicePayments(): array
    {
        $invoice = $this->latestInvoice;

        $invoiceId = $invoice instanceof StripeObject
            ? (string) ($invoice->id ?? '')
            : '';

        if ($invoiceId === '') {
            return [];
        }

        return rescue(fn () => $this->latestStripeInvoicePayments($invoiceId), [], report: true);
    }

    protected function clearLatestInvoiceCache(): void
    {
        unset($this->latestInvoice, $this->latestInvoiceLines, $this->latestInvoicePayments);
    }
}
