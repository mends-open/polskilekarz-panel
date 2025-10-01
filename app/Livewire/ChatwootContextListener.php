<?php

namespace App\Livewire;

use App\Support\Dashboard\ChatwootContext;
use App\Support\Dashboard\DashboardContext;
use App\Support\Dashboard\StripeContext;
use App\Support\Dashboard\StripeCustomerFinder;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use JsonException;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatwootContextListener extends Component
{
    protected DashboardContext $dashboardContext;

    protected StripeCustomerFinder $stripeCustomerFinder;

    public function boot(DashboardContext $dashboardContext, StripeCustomerFinder $stripeCustomerFinder): void
    {
        $this->dashboardContext = $dashboardContext;
        $this->stripeCustomerFinder = $stripeCustomerFinder;
    }

    public function render(): View
    {
        return view('livewire.chatwoot-context-listener');
    }

    public function mount(): void
    {
        $this->dashboardContext->storeChatwoot(ChatwootContext::empty());
        $this->dashboardContext->storeStripe(StripeContext::empty());
        $this->dashboardContext->markReady(false);
    }

    #[On('chatwoot.post-context')]
    public function setChatwootContext($data): void
    {
        try {
            $payload = is_string($data)
                ? json_decode($data, true, 512, JSON_THROW_ON_ERROR)
                : (array) $data;
        } catch (JsonException $exception) {
            Log::warning('Failed to decode Chatwoot context payload', [
                'exception' => $exception->getMessage(),
                'payload' => $data,
            ]);

            $payload = [];
        }

        $chatwootContext = ChatwootContext::fromPayload($payload);

        $this->dashboardContext->storeChatwoot($chatwootContext);

        Log::info('chatwoot context set', $chatwootContext->toArray());

        $this->synchroniseStripeContext($chatwootContext);
    }

    protected function synchroniseStripeContext(ChatwootContext $chatwootContext): void
    {
        if (! $chatwootContext->hasContact()) {
            $this->dashboardContext->storeStripe(StripeContext::empty());
            $this->dashboardContext->markReady(false);
            $this->dispatch('reset');

            return;
        }

        $stripeContext = $this->stripeCustomerFinder->forChatwootContact($chatwootContext->contactId);

        $this->dashboardContext->storeStripe($stripeContext);
        $this->dashboardContext->markReady();

        $this->dispatch('reset');
    }

    public static function getContext(): array
    {
        return app(DashboardContext::class)->chatwoot()->toArray();
    }

    public static function hasContext(): bool
    {
        return ! app(DashboardContext::class)->chatwoot()->isEmpty();
    }
}
