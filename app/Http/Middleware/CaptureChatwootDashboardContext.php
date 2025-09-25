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

        $context = $request->session()->get('chatwoot.dashboard.context');

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
}
