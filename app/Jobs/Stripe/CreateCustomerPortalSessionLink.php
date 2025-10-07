<?php

namespace App\Jobs\Stripe;

use App\Jobs\Chatwoot\SendCustomerPortalLinkMessage;
use App\Models\User;
use App\Services\Cloudflare\LinkShortener;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

class CreateCustomerPortalSessionLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $customerId,
        public readonly int|string $accountId,
        public readonly int|string $conversationId,
        public readonly int|string $impersonatorId,
        public readonly ?int $notifiableId,
    ) {}

    /**
     * @throws ApiErrorException
     */
    public function handle(LinkShortener $shortener): void
    {
        $config = config('stripe.customer_portal', []);

        $payload = array_filter([
            'customer' => $this->customerId,
            'return_url' => Arr::get($config, 'return_url'),
            'locale' => Arr::get($config, 'locale', 'auto'),
        ], static fn ($value) => $value !== null && $value !== '');

        $session = stripe()->billingPortal->sessions->create($payload);

        $shortUrl = $shortener->shorten($session->url);

        SendCustomerPortalLinkMessage::dispatch(
            shortUrl: $shortUrl,
            accountId: $this->accountId,
            conversationId: $this->conversationId,
            impersonatorId: $this->impersonatorId,
            notifiableId: $this->notifiableId,
        );

        $this->notify(
            title: __('notifications.jobs.stripe.create_customer_portal_session_link.success.title'),
            body: __('notifications.jobs.stripe.create_customer_portal_session_link.success.body'),
            status: 'success',
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Failed to create customer portal session link', [
            'customer_id' => $this->customerId,
            'account_id' => $this->accountId,
            'conversation_id' => $this->conversationId,
            'impersonator_id' => $this->impersonatorId,
            'exception' => $exception,
        ]);

        $this->notify(
            title: __('notifications.jobs.stripe.create_customer_portal_session_link.failed.title'),
            body: __('notifications.jobs.stripe.create_customer_portal_session_link.failed.body'),
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
