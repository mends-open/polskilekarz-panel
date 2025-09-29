<?php

namespace App\Livewire;

use Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Session;
use Livewire\Attributes\On;
use Livewire\Component;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ChatwootContextListener extends Component
{
    /**
     * This property will be automatically persisted
     * in the session and restored on reload.
     */
    #[Session (key: 'chatwoot')]
    public array $chatwoot = [];

    #[Session (key: 'stripe')]
    public array $stripe = [];

    public function render(): View
    {
        return view('livewire.chatwoot-context-listener');
    }

    public function mount(): void
    {
        $this->chatwoot = [];
        $this->stripe = [];
        $this->dispatch('chatwoot.get-context');
    }

    #[On('chatwoot.post-context')]
    public function setChatwootContext($data): void
    {
        $context = is_string($data) ? json_decode($data, true) : (array) $data;

        $this->chatwoot = [
            'account_id' => $context['conversation']['account_id'] ?? null,
            'conversation_id' => $context['conversation']['id'] ?? null,
            'inbox_id' => $context['conversation']['inbox_id'] ?? null,
            'contact_id' => $context['contact']['id'] ?? null,
            'assigned_user_id' => $context['conversation']['meta']['assignee']['id'] ?? null,
            'current_user_id' => $context['currentAgent']['id'] ?? null,
        ];
        Log::info('chatwoot context set', $this->chatwoot);
        $this->dispatch('chatwoot.set-context');
    }

    #[On('chatwoot.set-context')]
    public function setStripeContext(): void
    {
        $customers = stripe()->customers->search([
            'query' => 'metadata["chatwoot_contact_id"]:"' . $this->chatwoot['contact_id'] . '"',
        ])->toArray()['data'];

        $this->stripe = [
            'customer_id' => $customers[0]['id'] ?? null,
            'previous_customer_ids' => collect($customers)->pluck('id')->slice(1)->values()->all(),
        ];

        $this->dispatch('stripe.set-context');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function getContext(): array
    {
        // This works because Livewire stores the property in session
        return session()->get('chatwoot', []);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function hasContext(): bool
    {
        return ! empty(self::getContext());
    }
}
