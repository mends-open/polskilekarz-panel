@script
<script>
    // Immediately ask for context (no DOMContentLoaded delay)
    window.parent.postMessage('chatwoot-dashboard-app:fetch-info', '*');
    console.log("🔵 Sent request: chatwoot-dashboard-app:fetch-info");

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
        }
    });
</script>
@endscript

<div style="display:none"></div>
