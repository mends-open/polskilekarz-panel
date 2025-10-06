<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use Livewire\Attributes\Computed;
use Stripe\Exception\ApiErrorException;

trait HasLatestStripeInvoice
{
    use InteractsWithStripeInvoices;

    #[Computed(persist: true)]
    protected function latestInvoice(): array
    {
        $customerId = (string) $this->stripeContext()->customerId;

        if ($customerId === '') {
            return [];
        }

        try {
            return $this->latestStripeInvoice($customerId, $this->latestInvoiceRequestOptions());
        } catch (ApiErrorException $exception) {
            report($exception);

            return [];
        }
    }

    #[Computed(persist: true)]
    protected function latestInvoiceLines(): array
    {
        $invoiceId = (string) data_get($this->latestInvoice, 'id', '');

        if ($invoiceId === '') {
            return [];
        }

        try {
            return $this->latestStripeInvoiceLines($invoiceId, $this->latestInvoiceLinesRequestOptions());
        } catch (ApiErrorException $exception) {
            report($exception);

            return [];
        }
    }

    #[Computed(persist: true)]
    protected function latestInvoicePayments(): array
    {
        $invoiceId = (string) data_get($this->latestInvoice, 'id', '');

        if ($invoiceId === '') {
            return [];
        }

        try {
            return $this->latestStripeInvoicePayments($invoiceId, $this->latestInvoicePaymentsRequestOptions());
        } catch (ApiErrorException $exception) {
            report($exception);

            return [];
        }
    }

    protected function latestInvoiceRequestOptions(): array
    {
        return [];
    }

    protected function latestInvoiceLinesRequestOptions(): array
    {
        return [];
    }

    protected function latestInvoicePaymentsRequestOptions(): array
    {
        return [];
    }

    protected function clearLatestInvoiceCache(): void
    {
        unset($this->latestInvoice, $this->latestInvoiceLines, $this->latestInvoicePayments);
    }
}
