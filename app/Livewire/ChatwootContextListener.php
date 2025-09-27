<?php

namespace App\Livewire;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatwootContextListener extends Component
{
    public function render(): View
    {
        return view('livewire.chatwoot-context-listener');
    }

    public function mount(): void
    {
        $this->dispatch('chatwoot.get-context');
    }

    #[On('chatwoot.post-context')]
    public function capture(array $payload): void
    {
        $context = $this->extractContext($payload);

        if ($context === null) {
            Log::warning('Chatwoot context payload is missing required conversation data.', [
                'payload_keys' => array_keys($payload),
            ]);

            return;
        }

        Context::add('chatwoot', $context);

        if ($user = $this->resolveAuthenticatedUser()) {
            Context::add('auth', $user);
        }

        Log::debug('Chatwoot context stored.', Arr::only($context, [
            'account_id',
            'conversation_id',
            'contact_id',
            'last_message_id',
        ]));
    }

    private function extractContext(array $payload): ?array
    {
        $validator = validator($payload, [
            'conversation' => ['required', 'array'],
            'conversation.id' => ['required', 'integer'],
            'conversation.account_id' => ['required', 'integer'],
            'conversation.inbox_id' => ['nullable', 'integer'],
            'conversation.meta.sender.id' => ['nullable', 'integer'],
            'conversation.meta.sender.type' => ['nullable', 'string'],
            'conversation.last_non_activity_message.id' => ['nullable', 'integer'],
            'conversation.messages' => ['nullable', 'array'],
            'conversation.messages.*.id' => ['integer'],
            'contact.id' => ['nullable', 'integer'],
            'currentAgent.id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            Log::debug('Chatwoot context payload validation failed.', [
                'errors' => $validator->errors()->all(),
            ]);

            return null;
        }

        $messages = Arr::wrap(data_get($payload, 'conversation.messages', []));

        $lastMessageId = data_get($payload, 'conversation.last_non_activity_message.id');
        if ($lastMessageId === null && $messages !== []) {
            $lastMessage = Arr::last($messages) ?? [];
            $lastMessageId = $lastMessage['id'] ?? null;
        }

        return array_filter([
            'account_id' => data_get($payload, 'conversation.account_id'),
            'inbox_id' => data_get($payload, 'conversation.inbox_id'),
            'conversation_id' => data_get($payload, 'conversation.id'),
            'sender_id' => data_get($payload, 'conversation.meta.sender.id'),
            'sender_type' => data_get($payload, 'conversation.meta.sender.type'),
            'last_message_id' => $lastMessageId,
            'contact_id' => data_get($payload, 'contact.id'),
            'user_id' => data_get($payload, 'currentAgent.id'),
        ], static fn ($value) => $value !== null);
    }

    private function resolveAuthenticatedUser(): ?array
    {
        if (! auth()->check()) {
            return null;
        }

        $user = auth()->user();

        if (! $user instanceof Authenticatable) {
            return null;
        }

        return array_filter([
            'id' => $user->getAuthIdentifier(),
            'type' => method_exists($user, 'getMorphClass') ? $user->getMorphClass() : $user::class,
        ]);
    }
}
