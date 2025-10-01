<?php

namespace App\Support\Dashboard;

use Illuminate\Contracts\Session\Session;

class DashboardContext
{
    private const READY_KEY = 'ready';
    private const CHATWOOT_KEY = 'chatwoot';
    private const STRIPE_KEY = 'stripe';

    public function __construct(private readonly Session $session)
    {
    }

    public function storeChatwoot(ChatwootContext $context): void
    {
        $this->session->put(self::CHATWOOT_KEY, $context->toArray());
    }

    public function chatwoot(): ChatwootContext
    {
        return ChatwootContext::fromArray($this->session->get(self::CHATWOOT_KEY, []));
    }

    public function storeStripe(StripeContext $context): void
    {
        $this->session->put(self::STRIPE_KEY, $context->toArray());
    }

    public function stripe(): StripeContext
    {
        return StripeContext::fromArray($this->session->get(self::STRIPE_KEY, []));
    }

    public function markReady(bool $ready = true): void
    {
        $this->session->put(self::READY_KEY, $ready);
    }

    public function isReady(): bool
    {
        return (bool) $this->session->get(self::READY_KEY, false);
    }

    public function reset(): void
    {
        $this->session->forget([self::READY_KEY, self::CHATWOOT_KEY, self::STRIPE_KEY]);
    }
}
