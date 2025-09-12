<?php

namespace App\Http\Controllers;

use App\Jobs\Stripe\ProcessEvent;
use App\Models\StripeEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeEventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $event = StripeEvent::create(['data' => $request->all()]);

        Log::info('Stored Stripe event', ['id' => $event->id]);

        ProcessEvent::dispatch($event);

        Log::info('Dispatched Stripe event for processing', ['id' => $event->id]);

        return response()->json(['id' => $event->id]);
    }
}
