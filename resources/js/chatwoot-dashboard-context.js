const CONTEXT_STORAGE_KEY = 'ChatwootDashboardContext';

const state = {
    summary: null,
    raw: null,
    meta: null,
    receivedAt: null,
};

const normalizeContext = (payload) => {
    const conversation = payload?.conversation ?? {};
    const contact = payload?.contact ?? {};
    const currentAgent = payload?.currentAgent ?? {};

    const messages = Array.isArray(conversation?.messages) ? conversation.messages : [];
    const sortedMessages = [...messages].sort((a, b) => {
        const aTimestamp = a?.created_at ?? a?.id ?? 0;
        const bTimestamp = b?.created_at ?? b?.id ?? 0;

        if (aTimestamp < bTimestamp) {
            return 1;
        }

        if (aTimestamp > bTimestamp) {
            return -1;
        }

        return 0;
    });

    const latestMessage = sortedMessages.length > 0 ? sortedMessages[0] : null;

    const summary = {
        chatwoot_account_id: conversation?.account_id ?? null,
        chatwoot_inbox_id: conversation?.inbox_id ?? null,
        chatwoot_conversation_id: conversation?.id ?? null,
        chatwoot_conversation_status: conversation?.status ?? null,
        chatwoot_conversation_priority: conversation?.priority ?? null,
        chatwoot_conversation_channel: conversation?.channel ?? null,
        chatwoot_conversation_assignee_id:
            conversation?.assignee_id ?? conversation?.meta?.assignee?.id ?? null,
        chatwoot_contact_id: contact?.id ?? null,
        chatwoot_contact_email: contact?.email ?? null,
        chatwoot_contact_phone_number: contact?.phone_number ?? null,
        chatwoot_user_id: currentAgent?.id ?? null,
        chatwoot_user_email: currentAgent?.email ?? null,
        chatwoot_user_name: currentAgent?.name ?? null,
        chatwoot_message_id: latestMessage?.id ?? null,
        chatwoot_message_created_at: latestMessage?.created_at ?? null,
        chatwoot_message_type: latestMessage?.message_type ?? null,
        chatwoot_messages_count: messages.length || null,
    };

    Object.keys(summary).forEach((key) => {
        if (summary[key] === null || summary[key] === '') {
            delete summary[key];
        }
    });

    return {
        summary,
        meta: {
            latestMessage,
            currentAgent,
            conversation,
            contact,
        },
    };
};

const logContext = (label, summary, meta) => {
    if (!summary || Object.keys(summary).length === 0) {
        console.info(`[Chatwoot] ${label}: context unavailable`);
        return;
    }

    console.groupCollapsed(`%c[Chatwoot]%c ${label}`, 'color:#2563eb;font-weight:bold;', '');
    console.table(summary);
    if (meta) {
        console.log('Meta', meta);
    }
    console.groupEnd();
};

const handleContext = (payload) => {
    const { summary, meta } = normalizeContext(payload);

    state.summary = summary;
    state.raw = payload;
    state.meta = meta;
    state.receivedAt = new Date().toISOString();

    window[CONTEXT_STORAGE_KEY] = {
        summary,
        meta,
        receivedAt: state.receivedAt,
    };

    document.dispatchEvent(
        new CustomEvent('chatwoot:context-updated', {
            detail: {
                summary,
                meta,
                receivedAt: state.receivedAt,
            },
        }),
    );

    logContext('Context received', summary, meta);
};

const attachContextToLivewireRequests = () => {
    if (!window.Livewire || typeof window.Livewire.hook !== 'function') {
        return;
    }

    window.Livewire.hook('request', ({ options }) => {
        if (!state.summary && !state.raw) {
            return;
        }

        let body;

        try {
            body = JSON.parse(options.body ?? '{}');
        } catch (error) {
            console.warn('[Chatwoot] Unable to attach context to Livewire request payload', error);
            return;
        }

        body.chatwoot_context = {
            summary: state.summary ?? {},
            raw: state.raw ?? {},
            meta: state.meta ?? {},
            received_at: state.receivedAt ?? new Date().toISOString(),
        };

        options.body = JSON.stringify(body);
    });

    window.Livewire.hook('commit', ({ succeed }) => {
        succeed(() => {
            logContext('Livewire request context', state.summary);
        });
    });
};

window.addEventListener(
    'message',
    (event) => {
        const { data } = event;
        let payload = data;

        if (typeof data === 'string') {
            try {
                payload = JSON.parse(data);
            } catch (error) {
                return;
            }
        }

        if (!payload || payload.event !== 'appContext' || !payload.data) {
            return;
        }

        handleContext(payload.data);
    },
    false,
);

document.addEventListener('livewire:init', attachContextToLivewireRequests);

export {}; // ensure module scope
