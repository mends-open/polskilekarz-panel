<?php

namespace App\Livewire;

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
    public function logChatwootContext($data): void
    {
        $messages = $data['conversation']['messages'] ?? [];
        $lastNonActivityId = $data['conversation']['last_non_activity_message']['id'] ?? null;

        $lastMessageId = $lastNonActivityId;
        if ($lastMessageId === null && ! empty($messages)) {
            $lastMessageId = $messages[array_key_last($messages)]['id'] ?? null;
        }

        $chatwootContext = [
            'account_id' => $data['conversation']['account_id'] ?? null,
            'conversation_id' => $data['conversation']['id'] ?? null,
            'last_message_id' => $lastMessageId ?? null,
            'contact_id' => $data['contact']['id'] ?? null,
            'user_id' => $data['currentAgent']['id'] ?? null,
        ];

        Context::add('chatwoot', $chatwootContext);
        Context::add('auth', auth()->user());
        Log::info('context added');
    }
}
