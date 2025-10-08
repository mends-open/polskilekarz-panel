<?php

namespace App\Filament\Widgets\Stripe\Concerns;

use App\Jobs\Chatwoot\CreateInvoiceShortLink;
use App\Support\Metadata\MetadataPayload;
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

            return [$invoice];
        }

        $invoices = [];

        foreach ($response->autoPagingIterator() as $invoice) {
            $invoices[] = $invoice;
        }

        return $invoices;
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

        return $invoices[0] ?? null;
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
                $lines[] = $line;
            }

            return $lines;
        }

        foreach ($response->data ?? [] as $line) {
            $lines[] = $line;
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
                $payments[] = $payment;
            }

            return $payments;
        }

        foreach ($response->data ?? [] as $payment) {
            $payments[] = $payment;
        }

        return $payments;
    }

    protected function sendHostedInvoiceLink(StripeObject|array|null $invoice): void
    {
        $payload = $invoice instanceof StripeObject
            ? $invoice->toArray()
            : ($invoice ?? []);

        $invoiceUrl = data_get($payload, 'hosted_invoice_url');

        if (blank($invoiceUrl)) {
            Notification::make()
                ->title(__('notifications.stripe.interacts_with_invoices.invoice_link_unavailable.title'))
                ->body(__('notifications.stripe.interacts_with_invoices.invoice_link_unavailable.body'))
                ->warning()
                ->send();

            return;
        }

        $metadata = $this->chatwootMetadata([
            MetadataPayload::KEY_STRIPE_INVOICE_ID => data_get($payload, 'id'),
            MetadataPayload::KEY_STRIPE_CUSTOMER_ID => data_get($payload, 'customer'),
        ]);

        $this->sendInvoiceLinkToChatwoot($invoiceUrl, $metadata);
    }

    /**
     * @param array<string, string> $metadata
     */
    protected function sendInvoiceLinkToChatwoot(string $url, array $metadata = []): void
    {
        $context = $this->chatwootContext();

        $accountId = $context->accountId;
        $userId = $context->currentUserId;
        $conversationId = $context->conversationId;

        if (! $accountId || ! $userId || ! $conversationId) {
            Notification::make()
                ->title(__('notifications.stripe.interacts_with_invoices.missing_chatwoot_context.title'))
                ->body(__('notifications.stripe.interacts_with_invoices.missing_chatwoot_context.body'))
                ->danger()
                ->send();

            return;
        }

        CreateInvoiceShortLink::dispatch(
            url: $url,
            accountId: $accountId,
            conversationId: $conversationId,
            impersonatorId: $userId,
            metadata: $metadata,
            notifiableId: auth()->id(),
        );

        Notification::make()
            ->title(__('notifications.stripe.interacts_with_invoices.sending_invoice_link.title'))
            ->body(__('notifications.stripe.interacts_with_invoices.sending_invoice_link.body'))
            ->info()
            ->send();
    }
}
