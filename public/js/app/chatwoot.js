import './bootstrap';

const echo = window.Echo;

if (!echo) {
    console.warn('Laravel Echo is not available. Chatwoot context messages will be ignored.');
}

const channel = echo?.private('chatwoot.context');
let subscribed = false;
const pendingPayloads = [];

const flushPendingPayloads = () => {
    if (!channel) {
        return;
    }

    while (pendingPayloads.length > 0) {
        const payload = pendingPayloads.shift();

        try {
            channel.whisper('context', payload);
        } catch (error) {
            console.error('Failed to whisper Chatwoot context payload', error);
        }
    }
};

channel?.subscribed(() => {
    subscribed = true;
    flushPendingPayloads();
});

window.addEventListener('message', (event) => {
    const { data } = event;

    if (!data || typeof data !== 'object') {
        return;
    }

    if (!channel) {
        console.warn('Chatwoot context payload received but Echo channel is unavailable');
        return;
    }

    if (subscribed) {
        try {
            channel.whisper('context', data);
        } catch (error) {
            console.error('Failed to whisper Chatwoot context payload', error);
        }

        return;
    }

    pendingPayloads.push(data);
});

window.parent.postMessage('chatwoot-dashboard-app:fetch-info', '*');
