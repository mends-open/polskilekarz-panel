<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

class CaptureChatwootDashboardContext
{
    public function handle(Request $request, Closure $next)
    {
        if (! Livewire::isLivewireRequest()) {
            return $next($request);
        }

        $context = $this->extractContextFromRequest($request);

        if (! is_array($context)) {
            $context = $request->session()->get('chatwoot.dashboard.context');
        } else {
            $request->session()->put('chatwoot.dashboard.context', $context);
        }

        if (is_array($context)) {
            $summary = Arr::get($context, 'summary', []);
            $request->attributes->set('chatwoot.dashboard.summary', $summary);

            $logContext = array_filter(array_merge(
                $summary,
                [
                    'chatwoot_context_received_at' => Arr::get($context, 'received_at'),
                ],
            ));

            Log::info('Chatwoot dashboard context for Livewire request', array_filter([
                'url' => $request->fullUrl(),
                'route' => optional($request->route())->getName(),
                'chatwoot_context' => $logContext,
            ]));
        } else {
            Log::debug('Chatwoot dashboard context unavailable for Livewire request', [
                'url' => $request->fullUrl(),
            ]);
        }

        return $next($request);
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
        $raw = Arr::get($payload, 'raw');

        if (! is_array($summary)) {
            return null;
        }

        return array_filter([
            'received_at' => Arr::get($payload, 'received_at') ?? now()->toIso8601String(),
            'summary' => $summary,
            'data' => is_array($raw) ? $raw : null,
        ]);
    }
}
