<?php

namespace App\Jobs\Chatwoot;

use App\Models\User;
use App\Services\Cloudflare\LinkShortener;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateInvoiceShortLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $url,
        public readonly int|string $accountId,
        public readonly int|string $conversationId,
        public readonly int|string $impersonatorId,
        /** @var array<string, string> */
        public readonly array $metadata = [],
        public readonly ?int $notifiableId,
    ) {}

    public function handle(LinkShortener $shortener): void
    {
        $shortUrl = $shortener->shorten($this->url, $this->metadata);

        SendInvoiceShortLinkMessage::dispatch(
            shortUrl: $shortUrl,
            accountId: $this->accountId,
            conversationId: $this->conversationId,
            impersonatorId: $this->impersonatorId,
            notifiableId: $this->notifiableId,
        );

        $this->notify(
            title: __('notifications.jobs.chatwoot.create_invoice_short_link.success.title'),
            body: __('notifications.jobs.chatwoot.create_invoice_short_link.success.body'),
            status: 'success',
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Failed to create invoice short link', [
            'url' => $this->url,
            'account_id' => $this->accountId,
            'conversation_id' => $this->conversationId,
            'impersonator_id' => $this->impersonatorId,
            'metadata' => $this->metadata,
            'exception' => $exception,
        ]);

        $this->notify(
            title: __('notifications.jobs.chatwoot.create_invoice_short_link.failed.title'),
            body: __('notifications.jobs.chatwoot.create_invoice_short_link.failed.body'),
            status: 'danger',
        );
    }

    protected function notify(string $title, string $body, string $status): void
    {
        $user = $this->resolveNotifiable();

        if (! $user) {
            return;
        }

        Auth::setUser($user);

        $guard = Filament::auth();

        if (method_exists($guard, 'setUser')) {
            $guard->setUser($user);
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->status($status)
            ->broadcast($user);
    }

    protected function resolveNotifiable(): ?User
    {
        if (! $this->notifiableId) {
            return null;
        }

        return User::find($this->notifiableId);
    }
}
