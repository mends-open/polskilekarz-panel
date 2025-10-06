<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeObject;

trait InteractsWithStripeInvoices
{
    /**
     * @throws ApiErrorException
     */
    protected function fetchStripeInvoices(string $customerId, array $parameters = []): array
    {
        $parameters = array_merge([
            'customer' => $customerId,
            'limit' => $parameters['limit'] ?? 100,
        ], $parameters);

        $response = stripe()->invoices->all($parameters);

        if (($parameters['limit'] ?? null) === 1) {
            $invoice = $response->data[0] ?? null;

            if ($invoice === null) {
                return [];
            }

            return [$this->normalizeStripeInvoice($invoice)];
        }

        $invoices = [];

        foreach ($response->autoPagingIterator() as $invoice) {
            $invoices[] = $this->normalizeStripeInvoice($invoice);
        }

        return $invoices;
    }

    /**
     * @throws ApiErrorException
     */
    protected function latestStripeInvoice(?string $customerId, array $parameters = []): array
    {
        if (! is_string($customerId) || $customerId === '') {
            return [];
        }

        $parameters['limit'] = 1;

        $invoices = $this->fetchStripeInvoices($customerId, $parameters);

        return $invoices[0] ?? [];
    }

    protected function normalizeStripeInvoice(mixed $invoice): array
    {
        $normalized = $this->normalizeStripeObject($invoice);

        $normalized['lines']['data'] = collect(data_get($normalized, 'lines.data', []))
            ->map(function (array $line): array {
                $line['description'] = $line['description']
                    ?? data_get($line, 'price.product.name')
                    ?? data_get($line, 'price.nickname')
                    ?? data_get($line, 'price.id');

                $line['amount'] = $line['amount']
                    ?? data_get($line, 'amount_excluding_tax')
                    ?? data_get($line, 'price.unit_amount');

                return $line;
            })
            ->all();

        return $normalized;
    }

    protected function normalizeStripeObject(mixed $value): array
    {
        if ($value instanceof StripeObject) {
            $value = $value->toArray();
        }

        if (! is_array($value)) {
            return [];
        }

        foreach ($value as $key => $item) {
            if ($item instanceof StripeObject || is_array($item)) {
                $value[$key] = $this->normalizeStripeObject($item);
            }
        }

        return $value;
    }
}
