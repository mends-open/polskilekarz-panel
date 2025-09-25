<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class StoreChatwootDashboardContextController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string'],
            'data' => ['required', 'array'],
        ]);

        $summary = $this->summarize($validated['data']);

        $context = [
            'event' => $validated['event'],
            'received_at' => now()->toIso8601String(),
            'data' => $validated['data'],
            'summary' => $summary,
        ];

        $request->session()->put('chatwoot.dashboard.context', $context);

        Log::info('Chatwoot dashboard context stored from dashboard app event', [
            'chatwoot_context' => $summary,
        ]);

        return response()->json([
            'stored' => true,
            'summary' => $summary,
        ]);
    }

    protected function summarize(array $data): array
    {
        $conversation = Arr::get($data, 'conversation', []);
        $contact = Arr::get($data, 'contact', []);
        $agent = Arr::get($data, 'currentAgent', []);
        $messages = collect(Arr::get($conversation, 'messages', []));
        $latestMessage = $messages->sortByDesc(function ($message) {
            return Arr::get($message, 'created_at') ?? Arr::get($message, 'id');
        })->first();

        return array_filter([
            'chatwoot_account_id' => Arr::get($conversation, 'account_id'),
            'chatwoot_inbox_id' => Arr::get($conversation, 'inbox_id'),
            'chatwoot_conversation_id' => Arr::get($conversation, 'id'),
            'chatwoot_conversation_status' => Arr::get($conversation, 'status'),
            'chatwoot_conversation_priority' => Arr::get($conversation, 'priority'),
            'chatwoot_conversation_channel' => Arr::get($conversation, 'channel'),
            'chatwoot_conversation_assignee_id' => Arr::get($conversation, 'assignee_id') ?? Arr::get($conversation, 'meta.assignee.id'),
            'chatwoot_contact_id' => Arr::get($contact, 'id'),
            'chatwoot_contact_email' => Arr::get($contact, 'email'),
            'chatwoot_contact_phone_number' => Arr::get($contact, 'phone_number'),
            'chatwoot_user_id' => Arr::get($agent, 'id'),
            'chatwoot_user_email' => Arr::get($agent, 'email'),
            'chatwoot_user_name' => Arr::get($agent, 'name'),
            'chatwoot_message_id' => Arr::get($latestMessage, 'id'),
            'chatwoot_message_created_at' => Arr::get($latestMessage, 'created_at'),
            'chatwoot_message_type' => Arr::get($latestMessage, 'message_type'),
            'chatwoot_messages_count' => $messages->count() ?: null,
        ], fn ($value) => ! is_null($value) && $value !== '');
    }
}
