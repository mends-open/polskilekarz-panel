@script
<script>
    const CHATWOOT_FETCH_EVENT = 'chatwoot-dashboard-app:fetch-info';
    const isEmbeddedInChatwoot = window.parent && window.parent !== window;

    if (! isEmbeddedInChatwoot) {
        console.warn('Chatwoot context listener loaded outside an embed. Skipping.');

        return;
    }

    // Listen for messages from the Chatwoot iframe
    window.addEventListener('message', function (event) {
        if (! event || typeof event.data === 'undefined') {
            return;
        }

        try {
            const payload = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;

            if (! payload || typeof payload !== 'object') {
                return;
            }

            // Forward the raw data payload to the Livewire backend
            $wire.dispatch('chatwoot.post-context', payload);
        } catch (error) {
            console.error('Failed to parse Chatwoot message payload.', error);
        }
    });

    // When the backend triggers a context request
    $wire.on('chatwoot.get-context', () => {
        // Ask the parent (Chatwoot dashboard) for the full context object
        // Replace '*' with the expected origin for better security
        window.parent.postMessage(CHATWOOT_FETCH_EVENT, '*');
    });
</script>
@endscript
<div></div>
