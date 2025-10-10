<?php

namespace App\Filament\Widgets\Cloudflare\Concerns;

use App\Models\CloudflareLink;
use App\Support\Metadata\Metadata;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
    protected function summariseMetadata(array $metadata): string
    {
        if ($metadata === []) {
            return '';
        }

        $parts = [];

        foreach ($metadata as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $label = $this->metadataLabel((string) $key);
            $parts[] = sprintf('%s: %s', $label, (string) $value);
        }

        return implode(', ', $parts);
    }

    protected function metadataLabel(string $key): string
    {
        $translationKey = 'filament.widgets.cloudflare.metadata_keys.' . $key;
        $translation = __($translationKey);

        if ($translation === $translationKey) {
            return Str::title(str_replace('_', ' ', $key));
        }

        return $translation;
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
}
