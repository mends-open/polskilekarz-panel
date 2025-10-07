<?php

namespace App\Support\Dashboard\Concerns;

use App\Support\Dashboard\ChatwootContext;
use App\Support\Dashboard\DashboardContext;
use App\Support\Dashboard\StripeContext;

trait InteractsWithDashboardContext
{
    protected function dashboardContext(): DashboardContext
    {
        return app(DashboardContext::class);
    }

    protected function chatwootContext(): ChatwootContext
    {
        return $this->dashboardContext()->chatwoot();
    }

    protected function stripeContext(): StripeContext
    {
        return $this->dashboardContext()->stripe();
    }

    protected function dashboardContextIsReady(callable ...$checks): bool
    {
        if (! $this->dashboardContext()->isReady()) {
            return false;
        }

        foreach ($checks as $check) {
            if ($check() !== true) {
                return false;
            }
        }

        return true;
    }
}
