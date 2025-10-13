@script
<script>
    (() => {
        const REQUEST_EVENT = 'chatwoot.fetch-context';
        const CUSTOM_EVENT = 'chatwoot:fetch-context';
        const MIN_REQUEST_INTERVAL = 1000;

        let lastRequestAt = 0;

        const requestContext = () => {
            const now = Date.now();

            if (now - lastRequestAt < MIN_REQUEST_INTERVAL) {
                return;
            }

            lastRequestAt = now;

            // Immediately ask for context (no DOMContentLoaded delay)
            window.parent.postMessage('chatwoot-dashboard-app:fetch-info', '*');
            console.log("🔵 Sent request: chatwoot-dashboard-app:fetch-info");
        };

        const registerLivewireListener = () => {
            if (!window.Livewire || typeof window.Livewire.on !== 'function') {
                return;
            }

            window.Livewire.on(REQUEST_EVENT, requestContext);
        };

        if (document.readyState === 'loading') {
            document.addEventListener('livewire:init', registerLivewireListener, { once: true });
        } else {
            registerLivewireListener();
        }

        window.addEventListener(CUSTOM_EVENT, requestContext);

        window.addEventListener('focus', requestContext);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                requestContext();
            }
        });

        window.ChatwootDashboardApp = window.ChatwootDashboardApp || {};
        window.ChatwootDashboardApp.fetchContext = requestContext;

        requestContext();

        // Listen for messages back from Chatwoot
        window.addEventListener("message", function (event) {
            console.log("🟡 Received raw event:", event);

            // Safety: Only accept messages from the Chatwoot app
            if (!event.data) return;

            try {
                let payload = typeof event.data === "string"
                    ? JSON.parse(event.data)
                    : event.data;

                console.log("🟢 Parsed payload:", payload);

                // Dispatch to Livewire backend
                $wire.dispatch('chatwoot.post-context', payload);

            } catch (e) {
                console.error("🔴 Failed to parse Chatwoot context:", e, event.data);

                requestContext();
            }
        });
    })();
</script>
@endscript

<div style="display:none"></div>
