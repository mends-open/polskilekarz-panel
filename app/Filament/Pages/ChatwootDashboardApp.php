<?php

namespace App\Filament\Pages;

use App\Events\ChatwootContextReceived;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;

class ChatwootDashboardApp extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'chatwoot/dashboard-app';

    protected string $view = 'filament.pages.chatwoot-dashboard-app';

    public array $context = [];

    #[On('chatwoot-dashboard.context-received')]
    public function captureContext(array $payload): void
    {
        $context = $payload['context'] ?? [];

        if (! is_array($context)) {
            return;
        }

        $this->context = $context;

        ChatwootContextReceived::dispatch($context);
    }
}
