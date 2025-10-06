<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use App\Jobs\Chatwoot\CreateInvoiceShortLink;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
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

            return $invoice === null ? [] : [$invoice];
        }

        if (! method_exists($response, 'autoPagingIterator')) {
            return $response->data ?? [];
        }

        return iterator_to_array($response->autoPagingIterator());
    }

    /**
     * @throws ApiErrorException
     */
    protected function latestStripeInvoice(?string $customerId, array $parameters = []): ?StripeObject
    {
        if (! is_string($customerId) || $customerId === '') {
            return null;
        }

        $parameters['limit'] = 1;

        $invoices = $this->fetchStripeInvoices($customerId, $parameters);

        $invoice = $invoices[0] ?? null;

        return $invoice instanceof StripeObject ? $invoice : null;
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

        if (! method_exists($response, 'autoPagingIterator')) {
            return $response->data ?? [];
        }

        return iterator_to_array($response->autoPagingIterator());
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

        if (! method_exists($response, 'autoPagingIterator')) {
            return $response->data ?? [];
        }

        return iterator_to_array($response->autoPagingIterator());
    }

    protected function sendHostedInvoiceLink(mixed $invoice): void
    {
        $invoiceUrl = data_get($this->stripePayload($invoice), 'hosted_invoice_url');

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

    protected function stripePayload(mixed $value): array
    {
        if ($value instanceof StripeObject) {
            return $value->toArray();
        }

        return is_array($value) ? $value : Arr::wrap($value);
    }
}
