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
    public function capture(array $payload = []): void
    {
        $context = $this->extractContext($payload);

        if ($context === null) {
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
        $conversation = data_get($payload, 'conversation');

        if (! is_array($conversation) || ! isset($conversation['id'], $conversation['account_id'])) {
            Log::debug('Chatwoot context payload missing required conversation identifiers.');

            return null;
        }

        $messages = Arr::wrap($conversation['messages'] ?? []);

        $lastMessageId = data_get($conversation, 'last_non_activity_message.id');
        if ($lastMessageId === null && $messages !== []) {
            $lastMessageId = data_get(Arr::last($messages), 'id');
        }

        return array_filter([
            'account_id' => $conversation['account_id'],
            'inbox_id' => $conversation['inbox_id'] ?? null,
            'conversation_id' => $conversation['id'],
            'sender_id' => data_get($conversation, 'meta.sender.id'),
            'sender_type' => data_get($conversation, 'meta.sender.type'),
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
