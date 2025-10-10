<?php

namespace App\Filament\Widgets\Cloudflare\Concerns;

use App\Models\CloudflareLink;
use App\Support\Metadata\Metadata;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait InteractsWithCloudflareLinks
{
    protected ?Collection $cloudflareLinksCache = null;

    protected function cloudflareLinks(): Collection
    {
        if ($this->cloudflareLinksCache !== null) {
            return $this->cloudflareLinksCache;
        }

        $contactId = $this->chatwootContext()->contactId;

        if ($contactId === null) {
            return $this->cloudflareLinksCache = collect();
        }

        return $this->cloudflareLinksCache = CloudflareLink::query()
            ->whereRaw("metadata->>'chatwoot_contact_id' = ?", [(string) $contactId])
            ->latest()
            ->get();
    }

    protected function resetCloudflareLinksCache(): void
    {
        $this->cloudflareLinksCache = null;
    }

    /**
     * @param array<string, string> $metadata
     */
    protected function resolveEntityType(array $metadata): string
    {
        if (array_key_exists(Metadata::KEY_STRIPE_INVOICE_ID, $metadata)) {
            return 'invoice';
        }

        if (array_key_exists(Metadata::KEY_STRIPE_BILLING_PORTAL_SESSION, $metadata)) {
            return 'billing_portal';
        }

        if (array_key_exists(Metadata::KEY_STRIPE_CUSTOMER_ID, $metadata)) {
            return 'customer';
        }

        return 'link';
    }

    /**
     * @param array<string, string> $metadata
     */
    protected function resolveEntityIdentifier(array $metadata, string $entityType): ?string
    {
        return match ($entityType) {
            'invoice' => $metadata[Metadata::KEY_STRIPE_INVOICE_ID] ?? null,
            'billing_portal' => $metadata[Metadata::KEY_STRIPE_BILLING_PORTAL_SESSION] ?? null,
            'customer' => $metadata[Metadata::KEY_STRIPE_CUSTOMER_ID] ?? null,
            default => $this->fallbackEntityIdentifier($metadata),
        };
    }

    /**
     * @param array<string, string> $metadata
     */
    protected function fallbackEntityIdentifier(array $metadata): ?string
    {
        $ordered = Metadata::prepare($metadata);

        if ($ordered === []) {
            return null;
        }

        return Arr::first($ordered);
    }
}
