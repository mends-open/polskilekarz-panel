import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
const port = import.meta.env.VITE_REVERB_PORT ?? (scheme === 'https' || scheme === 'wss' ? 443 : 80);

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? import.meta.env.REVERB_HOST,
    wsPort: port,
    wssPort: port,
    forceTLS: scheme === 'https' || scheme === 'wss',
    enabledTransports: ['ws', 'wss'],
    scheme,
});
