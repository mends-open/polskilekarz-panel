<?php

namespace App\Http\Controllers;

use App\Jobs\Chatwoot\ProcessEvent;
use App\Models\ChatwootEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatwootEventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        $event = ChatwootEvent::create(['data' => $payload]);

        Log::info('Stored Chatwoot event', ['id' => $event->id]);

        ProcessEvent::dispatch($event);

        Log::info('Dispatched Chatwoot event for processing', ['id' => $event->id]);

        return response()->json(['id' => $event->id]);
    }
}
