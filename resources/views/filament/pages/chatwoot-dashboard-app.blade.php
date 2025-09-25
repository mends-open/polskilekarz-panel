<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                {{ __('Chatwoot dashboard app') }}
            </x-slot>

            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ __('When Chatwoot loads this app, the current context will be forwarded to the backend and logged.') }}
                </p>

                @if ($context !== [])
                    <div class="rounded-xl border border-gray-200 bg-white p-4 text-left text-xs shadow-sm dark:border-white/10 dark:bg-white/5">
                        <pre class="max-h-80 overflow-auto whitespace-pre-wrap">{{ json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Waiting for Chatwoot to send the dashboard context...') }}
                    </p>
                @endif
            </div>
        </x-filament::section>
    </div>

    @script
        document.addEventListener('livewire:load', () => {
            if (window.__chatwootDashboardListenerAdded) {
                return;
            }

            window.__chatwootDashboardListenerAdded = true;

            const dispatchContext = (context) => {
                window.Livewire.dispatch('chatwoot-dashboard.context-received', { context });
            };

            const handleDashboardEvent = (event) => {
                const payload = event?.data;

                if (! payload || typeof payload !== 'object') {
                    return;
                }

                if (payload.name === 'dashboard_app_context') {
                    dispatchContext(payload.data ?? {});
                }

                if (payload.name === 'dashboard_app:ping') {
                    window.parent?.postMessage({ name: 'dashboard_app:pong' }, '*');
                }
            };

            window.addEventListener('message', handleDashboardEvent, false);

            window.parent?.postMessage({ name: 'dashboard_app:ready' }, '*');
        });
    @endscript
</x-filament-panels::page>
