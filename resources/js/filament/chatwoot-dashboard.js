if (typeof window !== 'undefined') {
    document.addEventListener('livewire:load', () => {
        if (window.__chatwootDashboardListenerAdded) {
            return;
        }

        window.__chatwootDashboardListenerAdded = true;

        const dispatchContext = (context) => {
            if (! window.Livewire || typeof window.Livewire.dispatch !== 'function') {
                return;
            }

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
}
