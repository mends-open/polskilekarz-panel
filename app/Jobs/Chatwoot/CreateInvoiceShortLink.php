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
        public readonly ?int $notifiableId,
    ) {
    }

    public function handle(LinkShortener $shortener): void
    {
        $shortUrl = $shortener->shorten($this->url);

        SendInvoiceShortLinkMessage::dispatch(
            shortUrl: $shortUrl,
            accountId: $this->accountId,
            conversationId: $this->conversationId,
            impersonatorId: $this->impersonatorId,
            notifiableId: $this->notifiableId,
        );

        $this->notify(
            title: 'Invoice link shortened',
            body: 'The invoice link has been shortened and will be delivered shortly.',
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
            'exception' => $exception,
        ]);

        $this->notify(
            title: 'Failed to shorten invoice link',
            body: 'We were unable to create a short link for the invoice. Please try again.',
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
            ->sendToDatabase($user, isEventDispatched: true)
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
