@script
<script>
    const registerFetchListener = () => {
        if (!window.Livewire || typeof window.Livewire.on !== 'function') {
            return;
        }

        window.Livewire.on('chatwoot.fetch-context', () => {
            window.parent.postMessage('chatwoot-dashboard-app:fetch-info', '*');
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('livewire:init', registerFetchListener, { once: true });
    } else {
        registerFetchListener();
    }

    window.addEventListener('message', (event) => {
        if (!event.data) {
            return;
        }

        let payload = event.data;

        if (typeof payload === 'string') {
            try {
                payload = JSON.parse(payload);
            } catch (error) {
                console.error('Failed to parse Chatwoot context:', error, event.data);
                return;
            }
        }

        if (typeof $wire !== 'undefined' && typeof $wire.dispatch === 'function') {
            $wire.dispatch('chatwoot.post-context', payload);
        }
    });
</script>
@endscript

<div style="display:none"></div>
