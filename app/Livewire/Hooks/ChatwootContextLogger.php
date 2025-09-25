<?php

namespace App\Livewire\Hooks;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Livewire\ComponentHook;

class ChatwootContextLogger extends ComponentHook
{
    /**
     * @var array|null
     */
    protected static ?array $snapshot = null;

    protected static bool $handled = false;

    public static function snapshot(): ?array
    {
        return static::$snapshot;
    }

    public function hydrate($memo): void
    {
        if (static::$handled) {
            return;
        }

        static::$handled = true;

        $request = request();

        if (! $request instanceof Request) {
            return;
        }

        $context = $this->resolveContext($request);

        if (! $context) {
            $this->logUnavailable($request);

            return;
        }

        static::$snapshot = $context;

        $summary = Arr::get($context, 'summary', []);

        if (! empty($summary)) {
            $request->attributes->set('chatwoot.dashboard.summary', $summary);
        }

        $logContext = $this->buildLogContext($summary, Arr::get($context, 'received_at'));

        Log::info('Chatwoot dashboard context for Livewire request', array_filter([
            'url' => $request->fullUrl(),
            'route' => optional($request->route())->getName(),
            'component' => $this->component::class,
            'chatwoot_context' => $logContext,
        ]));
    }

    protected function resolveContext(Request $request): ?array
    {
        $context = $this->extractContextFromRequest($request);

        if (is_array($context)) {
            if ($request->hasSession()) {
                $request->session()->put('chatwoot.dashboard.context', $context);
            }

            return $context;
        }

        if (! $request->hasSession()) {
            return null;
        }

        $stored = $request->session()->get('chatwoot.dashboard.context');

        return is_array($stored) ? $stored : null;
    }

    protected function extractContextFromRequest(Request $request): ?array
    {
        $payload = $request->input('chatwoot_context');

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $payload = $decoded;
            }
        }

        if (! is_array($payload)) {
            return null;
        }

        $summary = Arr::get($payload, 'summary');

        if (! is_array($summary) || empty($summary)) {
            return null;
        }

        return array_filter([
            'summary' => $summary,
            'meta' => Arr::get($payload, 'meta'),
            'data' => Arr::get($payload, 'raw'),
            'received_at' => Arr::get($payload, 'received_at') ?? now()->toIso8601String(),
        ], function ($value) {
            return is_array($value) ? ! empty($value) : ! is_null($value);
        });
    }

    protected function buildLogContext(array $summary, ?string $receivedAt): array
    {
        return array_filter([
            ...$summary,
            'chatwoot_context_received_at' => $receivedAt,
        ], fn ($value) => $value !== null && $value !== '');
    }

    protected function logUnavailable(Request $request): void
    {
        Log::debug('Chatwoot dashboard context unavailable for Livewire request', array_filter([
            'url' => $request->fullUrl(),
            'route' => optional($request->route())->getName(),
            'component' => $this->component::class,
        ]));
    }
}
