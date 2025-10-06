<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use App\Jobs\Chatwoot\CreateInvoiceShortLink;
use Filament\Notifications\Notification;
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
            ->map(fn (array $line): array => $this->normalizeStripeInvoiceLine($line))
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

    /**
     * @throws ApiErrorException
     */
    protected function latestStripeInvoiceLines(string $invoiceId, array $parameters = []): array
    {
        $parameters = array_merge([
            'limit' => $parameters['limit'] ?? 100,
        ], $parameters);

        $response = stripe()->invoices->allLines($invoiceId, $parameters);

        $lines = [];

        if (method_exists($response, 'autoPagingIterator')) {
            foreach ($response->autoPagingIterator() as $line) {
                $lines[] = $this->normalizeStripeInvoiceLine($line);
            }

            return $lines;
        }

        foreach ($response->data ?? [] as $line) {
            $lines[] = $this->normalizeStripeInvoiceLine($line);
        }

        return $lines;
    }

    /**
     * @throws ApiErrorException
     */
    protected function latestStripeInvoicePayments(string $invoiceId, array $parameters = []): array
    {
        $parameters = array_merge([
            'invoice' => $invoiceId,
            'limit' => $parameters['limit'] ?? 100,
        ], $parameters);

        $response = stripe()->invoicePayments->all($parameters);

        $payments = [];

        if (method_exists($response, 'autoPagingIterator')) {
            foreach ($response->autoPagingIterator() as $payment) {
                $payments[] = $this->normalizeStripeObject($payment);
            }

            return $payments;
        }

        foreach ($response->data ?? [] as $payment) {
            $payments[] = $this->normalizeStripeObject($payment);
        }

        return $payments;
    }

    protected function normalizeStripeInvoiceLine(mixed $line): array
    {
        $line = $this->normalizeStripeObject($line);

        $line['description'] = $line['description']
            ?? data_get($line, 'price.product.name')
            ?? data_get($line, 'price.nickname')
            ?? data_get($line, 'price.id');

        $line['amount'] = $line['amount']
            ?? data_get($line, 'amount_excluding_tax')
            ?? data_get($line, 'price.unit_amount');

        return $line;
    }

    protected function sendHostedInvoiceLink(?array $invoice): void
    {
        $invoiceUrl = data_get($invoice ?? [], 'hosted_invoice_url');

        if (blank($invoiceUrl)) {
            Notification::make()
                ->title('Invoice link unavailable')
                ->body('We could not find a hosted invoice URL on the invoice.')
                ->warning()
                ->send();

            return;
        }

        $this->sendInvoiceLinkToChatwoot($invoiceUrl);
    }

    protected function sendInvoiceLinkToChatwoot(string $url): void
    {
        $context = $this->chatwootContext();

        $accountId = $context->accountId;
        $userId = $context->currentUserId;
        $conversationId = $context->conversationId;

        if (! $accountId || ! $userId || ! $conversationId) {
            Notification::make()
                ->title('Missing Chatwoot context')
                ->body('Unable to send the invoice link because the Chatwoot context is incomplete.')
                ->danger()
                ->send();

            return;
        }

        CreateInvoiceShortLink::dispatch(
            url: $url,
            accountId: $accountId,
            conversationId: $conversationId,
            impersonatorId: $userId,
            notifiableId: auth()->id(),
        );

        Notification::make()
            ->title('Sending invoice link')
            ->body('We are preparing the invoice link and will send it to the conversation shortly.')
            ->info()
            ->send();
    }
}
