<?php

namespace App\Support\Dashboard\Concerns;

trait RefreshesDashboardContextOnBoot
{
    public function boot(): void
    {
        $this->dispatch('chatwoot.fetch-context');
    }
}
