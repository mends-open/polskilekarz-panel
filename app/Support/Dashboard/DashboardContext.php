<?php

namespace App\Support\Dashboard;

use Illuminate\Contracts\Session\Session;
use Illuminate\Session\SessionManager;

class DashboardContext
{
    private const string READY_KEY = 'dashboard.ready';

    private const string CHATWOOT_KEY = 'dashboard.chatwoot';

    private const string STRIPE_KEY = 'dashboard.stripe';

    public function __construct(private readonly SessionManager $sessionManager) {}

    private function session(): Session
    {
        return $this->sessionManager->driver();
    }

    public function storeChatwoot(ChatwootContext $context, bool $persist = true): void
    {
        $this->store(self::CHATWOOT_KEY, $context->toArray(), $persist);
    }

    public function chatwoot(): ChatwootContext
    {
        return ChatwootContext::fromArray($this->session()->get(self::CHATWOOT_KEY, []));
    }

    public function storeStripe(StripeContext $context, bool $persist = true): void
    {
        $this->store(self::STRIPE_KEY, $context->toArray(), $persist);
    }

    public function stripe(): StripeContext
    {
        return StripeContext::fromArray($this->session()->get(self::STRIPE_KEY, []));
    }

    public function markReady(bool $ready = true, bool $persist = true): void
    {
        $session = $this->session();
        $method = $persist ? 'put' : 'now';

        if (! $ready) {
            $session->{$method}(self::READY_KEY, false);

            return;
        }

        $session->{$method}(self::READY_KEY, $this->chatwootContextIsUsable());
    }

    public function isReady(): bool
    {
        $ready = (bool) $this->session()->get(self::READY_KEY, false);

        if ($ready && $this->chatwootContextIsUsable()) {
            return true;
        }

        if (! $ready && $this->chatwootContextIsUsable()) {
            $this->markReady();

            return true;
        }

        return false;
    }

    public function reset(): void
    {
        $this->session()->forget([self::READY_KEY, self::CHATWOOT_KEY, self::STRIPE_KEY]);
    }

    private function chatwootContextIsUsable(): bool
    {
        $chatwootContext = $this->chatwoot();

        if ($chatwootContext->isEmpty()) {
            return false;
        }

        return $chatwootContext->hasContact();
    }

    /**
     * @param array<string, mixed> $value
     */
    private function store(string $key, array $value, bool $persist = true): void
    {
        $session = $this->session();

        if ($persist) {
            $session->put($key, $value);

            return;
        }

        $session->now($key, $value);
    }
}
