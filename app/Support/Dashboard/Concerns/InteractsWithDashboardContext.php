<?php

namespace App\Support\Dashboard\Concerns;

use App\Support\Dashboard\ChatwootContext;
use App\Support\Dashboard\DashboardContext;
use App\Support\Dashboard\StripeContext;
use App\Support\Metadata\MetadataPayload;

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
            'chatwoot_account_id' => $context->accountId,
            'chatwoot_conversation_id' => $context->conversationId,
            'chatwoot_inbox_id' => $context->inboxId,
            'chatwoot_contact_id' => $context->contactId,
            'chatwoot_user_id' => $context->currentUserId,
        ];

        $userId = auth()->id();

        if ($userId !== null && $userId !== '') {
            $metadata['user_id'] = (string) $userId;
        }

        $payload = MetadataPayload::from($metadata);

        if ($additional !== []) {
            $payload = $payload->with($additional);
        }

        return $payload->toArray();
    }
}
