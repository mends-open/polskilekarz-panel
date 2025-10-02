<?php

namespace App\Jobs\Chatwoot;

use App\Models\User;
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

class SendCustomerPortalLinkMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $shortUrl,
        public readonly int|string $accountId,
        public readonly int|string $conversationId,
        public readonly int|string $impersonatorId,
        public readonly ?int $notifiableId,
    ) {}

    public function handle(): void
    {
        chatwoot()
            ->platform()
            ->impersonate($this->impersonatorId)
            ->messages()
            ->create($this->accountId, $this->conversationId, [
                'content' => $this->shortUrl,
            ]);

        $this->notify(
            title: 'Customer portal link sent',
            body: 'The customer portal link was sent to the Chatwoot conversation.',
            status: 'success',
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Failed to send customer portal link message', [
            'short_url' => $this->shortUrl,
            'account_id' => $this->accountId,
            'conversation_id' => $this->conversationId,
            'impersonator_id' => $this->impersonatorId,
            'exception' => $exception,
        ]);

        $this->notify(
            title: 'Failed to send customer portal link',
            body: 'We were unable to send the customer portal link to the Chatwoot conversation. Please try again.',
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
