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

    protected function dashboardContextIsReady(): bool
    {
        return $this->dashboardContext()->isReady();
    }
}
