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

    protected function latestInvoiceRequestOptions(): array
    {
        return [
            'expand' => [
                'data.lines',
                'data.lines.data.price',
                'data.lines.data.price.product',
                'data.payments',
                'data.payments.data.payment',
            ],
        ];
    }

    protected function clearLatestInvoiceCache(): void
    {
        unset($this->latestInvoice);
    }
}
