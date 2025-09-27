@script
<script>
    const CHATWOOT_FETCH_EVENT = 'chatwoot-dashboard-app:fetch-info';
    const CHATWOOT_CONTEXT_EVENT = 'appContext';
    const isEmbeddedInChatwoot = window.parent && window.parent !== window;

    const parseMessage = (raw) => {
        if (typeof raw === 'string') {
            try {
                return JSON.parse(raw);
            } catch (error) {
                console.error('Failed to parse Chatwoot message payload.', error);

                return null;
            }
        }

        if (raw && typeof raw === 'object') {
            return raw;
        }

        return null;
    };

    if (! isEmbeddedInChatwoot) {
        console.warn('Chatwoot context listener loaded outside an embed. Skipping.');
    } else {
        window.addEventListener('message', (event) => {
            if (event.source !== window.parent || typeof event.data === 'undefined') {
                return;
            }

            const payload = parseMessage(event.data);

            if (! payload || payload.event !== CHATWOOT_CONTEXT_EVENT || typeof payload.data !== 'object') {
                return;
            }

            $wire.dispatch('chatwoot.post-context', payload.data);
        });

        $wire.on('chatwoot.get-context', () => {
            window.parent.postMessage({ event: CHATWOOT_FETCH_EVENT }, '*');
        });
    }
</script>
@endscript
<div></div>
