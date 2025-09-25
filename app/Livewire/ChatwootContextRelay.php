<?php

namespace App\Livewire;

use App\Events\ChatwootContextReceived;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatwootContextRelay extends Component
{
    #[On('chatwoot-dashboard.context-received')]
    public function captureContext(array $payload): void
    {
        $context = $payload['context'] ?? [];

        if (! is_array($context)) {
            return;
        }

        Log::info('Chatwoot dashboard context received.', [
            'context' => $context,
        ]);

        ChatwootContextReceived::dispatch($context);
    }

    public function render(): View
    {
        return view('livewire.chatwoot-context-relay');
    }
}
