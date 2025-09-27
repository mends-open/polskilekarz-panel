@script
<script>
    // Listen for messages from the Chatwoot iframe
    window.addEventListener("message", function (event) {
        // Forward the raw data payload to the Livewire backend
        $wire.dispatch('chatwoot.post-context', JSON.parse(event.data));
        console.log(event.data);
    });

    // When the backend triggers a context request
    $wire.on('chatwoot.get-context', () => {
        // Ask the parent (Chatwoot dashboard) for the full context object
        // Replace '*' with the expected origin for better security
        window.parent.postMessage('chatwoot-dashboard-app:fetch-info', '*');
    });
</script>
@endscript
<div></div>
