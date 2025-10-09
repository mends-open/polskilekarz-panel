<?php

namespace App\Support\Dashboard\Concerns;

use App\Support\Dashboard\ChatwootContext;
use App\Support\Dashboard\DashboardContext;
use App\Support\Dashboard\StripeContext;
use App\Support\Metadata\Metadata;

trait InteractsWithDashboardContext
{
    protected function dashboardContext(): DashboardContext
    {
        return app(DashboardContext::class);
    }

    protected function chatwootContext(): ChatwootContext
    {
        return $this->dashboardContext()->chatwoot();
    }

    protected function stripeContext(): StripeContext
    {
        return $this->dashboardContext()->stripe();
    }

    protected function dashboardContextIsReady(callable ...$checks): bool
    {
        if (! $this->dashboardContext()->isReady()) {
            return false;
        }

        foreach ($checks as $check) {
            if ($check() !== true) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $additional
     * @return array<string, string>
     */
    protected function chatwootMetadata(array $additional = []): array
    {
        $context = $this->chatwootContext();

        $metadata = [
            Metadata::KEY_CHATWOOT_ACCOUNT_ID => $context->accountId,
            Metadata::KEY_CHATWOOT_CONVERSATION_ID => $context->conversationId,
            Metadata::KEY_CHATWOOT_INBOX_ID => $context->inboxId,
            Metadata::KEY_CHATWOOT_CONTACT_ID => $context->contactId,
            Metadata::KEY_CHATWOOT_USER_ID => $context->currentUserId,
        ];

        $userId = auth()->id();

        if ($userId !== null && $userId !== '') {
            $metadata[Metadata::KEY_USER_ID] = (string) $userId;
        }

        $payload = Metadata::prepare($metadata);

        if ($additional !== []) {
            $payload = Metadata::extend($payload, $additional);
        }

        return $payload;
    }
}
