<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Arr;

class ChatwootContext
{
    public function __construct(
        public readonly ?int $accountId,
        public readonly ?int $conversationId,
        public readonly ?int $inboxId,
        public readonly ?int $contactId,
        public readonly ?int $assignedUserId,
        public readonly ?int $currentUserId,
    ) {
    }

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
            self::intOrNull($data['account_id'] ?? null),
            self::intOrNull($data['conversation_id'] ?? null),
            self::intOrNull($data['inbox_id'] ?? null),
            self::intOrNull($data['contact_id'] ?? null),
            self::intOrNull($data['assigned_user_id'] ?? null),
            self::intOrNull($data['current_user_id'] ?? null),
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
        ];
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
}
