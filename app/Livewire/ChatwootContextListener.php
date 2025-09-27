<?php

namespace App\Livewire;

use Arr;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        Context::add('ready', false);
        Log::info('context not ready');
        $this->dispatch('chatwoot.get-context');
    }

    #[On('chatwoot.post-context')]
    public function logChatwootContext($data): void
    {
        Context::add('chatwoot', [
            'account_id' => $data['conversation']['account_id'] ?? null,
            'conversation_id' => $data['conversation']['id'] ?? null,
            'sender_id' => $data['conversation']['meta']['sender']['id'] ?? null,
            'sender_type' => $data['conversation']['meta']['sender']['type'] ?? null,
            'last_message_id' => Arr::last($data['conversation']['messages'])['id'] ?? null,
            'contact_id' => $data['contact']['id'] ?? null,
            'user_id' => $data['currentAgent']['id'] ?? null,
        ]);
        Context::add('auth', [
            'id' => auth()->id(),
            'type' => get_class(auth()->user())
        ]);
        Context::add('ready', true);
        Log::info('context ready');
    }
}
