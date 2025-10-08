<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Arr;

readonly class ChatwootContext
{
    public function __construct(
        public ?int $accountId,
        public ?int $conversationId,
        public ?int $inboxId,
        public ?int $contactId,
        public ?int $assignedUserId,
        public ?int $currentUserId,
    ) {}

    public static function empty(): self
    {
        return new self(null, null, null, null, null, null);
    }

    public static function fromArray(?array $data): self
    {
        if (empty($data)) {
            return self::empty();
        }

        return new self(
            self::resolveContextValue($data, 'account_id', 'chatwoot_account_id'),
            self::resolveContextValue($data, 'conversation_id', 'chatwoot_conversation_id'),
            self::resolveContextValue($data, 'inbox_id', 'chatwoot_inbox_id'),
            self::resolveContextValue($data, 'contact_id', 'chatwoot_contact_id'),
            self::intOrNull($data['assigned_user_id'] ?? null),
            self::resolveContextValue($data, 'current_user_id', 'chatwoot_user_id'),
        );
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            self::intOrNull(Arr::get($payload, 'conversation.account_id')),
            self::intOrNull(Arr::get($payload, 'conversation.id')),
            self::intOrNull(Arr::get($payload, 'conversation.inbox_id')),
            self::intOrNull(Arr::get($payload, 'contact.id')),
            self::intOrNull(Arr::get($payload, 'conversation.meta.assignee.id')),
            self::intOrNull(Arr::get($payload, 'currentAgent.id')),
        );
    }

    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'conversation_id' => $this->conversationId,
            'inbox_id' => $this->inboxId,
            'contact_id' => $this->contactId,
            'assigned_user_id' => $this->assignedUserId,
            'current_user_id' => $this->currentUserId,
            'chatwoot_account_id' => $this->accountId,
            'chatwoot_conversation_id' => $this->conversationId,
            'chatwoot_inbox_id' => $this->inboxId,
            'chatwoot_contact_id' => $this->contactId,
            'chatwoot_user_id' => $this->currentUserId,
        ];
    }

    public function metadata(): array
    {
        return array_filter([
            'chatwoot_account_id' => $this->stringOrNull($this->accountId),
            'chatwoot_inbox_id' => $this->stringOrNull($this->inboxId),
            'chatwoot_conversation_id' => $this->stringOrNull($this->conversationId),
            'chatwoot_contact_id' => $this->stringOrNull($this->contactId),
            'chatwoot_user_id' => $this->stringOrNull($this->currentUserId),
        ], static fn (?string $value): bool => $value !== null);
    }

    public function isEmpty(): bool
    {
        return $this->accountId === null
            && $this->conversationId === null
            && $this->inboxId === null
            && $this->contactId === null
            && $this->assignedUserId === null
            && $this->currentUserId === null;
    }

    public function hasContact(): bool
    {
        return $this->contactId !== null;
    }

    public function canImpersonate(): bool
    {
        return $this->accountId !== null
            && $this->contactId !== null
            && $this->currentUserId !== null;
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function resolveContextValue(array $data, string $baseKey, string $chatwootKey): ?int
    {
        $value = Arr::get($data, $chatwootKey, Arr::get($data, $baseKey));

        return self::intOrNull($value);
    }

    private function stringOrNull(?int $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
