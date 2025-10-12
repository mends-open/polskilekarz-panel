<?php

namespace App\Livewire;

use App\Jobs\Stripe\SyncChatwootContactIdentifier;
use App\Support\Dashboard\ChatwootContext;
use App\Support\Dashboard\DashboardContext;
use App\Support\Dashboard\StripeContext;
use App\Support\Dashboard\StripeCustomerFinder;
use Illuminate\Support\Arr;
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
        $this->dashboardContext->reset();
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

        $identifier = $this->normaliseIdentifier(Arr::get($payload, 'contact.identifier'));

        $this->synchroniseStripeContext($chatwootContext, $identifier);
    }

    protected function synchroniseStripeContext(ChatwootContext $chatwootContext, ?string $identifier): void
    {
        if (! $chatwootContext->hasContact()) {
            $this->dashboardContext->storeStripe(StripeContext::empty());
            $this->dashboardContext->markReady(false);
            $this->dispatch('reset');

            return;
        }

        $stripeContext = new StripeContext($identifier);

        $this->dashboardContext->storeStripe($stripeContext);

        $customerId = $identifier;

        if ($customerId === null) {
            $customerId = $this->stripeCustomerFinder->findFallback($chatwootContext->contactId);

            if ($customerId !== null) {
                $stripeContext = new StripeContext($customerId);

                $this->dashboardContext->storeStripe($stripeContext);

                SyncChatwootContactIdentifier::dispatch(
                    $chatwootContext->accountId,
                    $chatwootContext->contactId,
                    $customerId,
                );
            }
        }

        $this->dashboardContext->markReady(
            $this->shouldWidgetsBeReady($chatwootContext),
        );
        $this->dispatch('reset');
    }

    private function normaliseIdentifier(mixed $identifier): ?string
    {
        if (! is_string($identifier)) {
            return null;
        }

        $identifier = trim($identifier);

        return $identifier === '' ? null : $identifier;
    }

    public static function getContext(): array
    {
        return app(DashboardContext::class)->chatwoot()->toArray();
    }

    public static function hasContext(): bool
    {
        return ! app(DashboardContext::class)->chatwoot()->isEmpty();
    }

    private function shouldWidgetsBeReady(ChatwootContext $chatwootContext): bool
    {
        if ($chatwootContext->isEmpty()) {
            return false;
        }

        if (! $chatwootContext->hasContact()) {
            return false;
        }

        return true;
    }
}
