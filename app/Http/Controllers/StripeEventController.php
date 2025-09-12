<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeEventController extends Controller
{
    public function __construct(protected StripeService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        $event = $this->service->storeEvent($request->all());

        return response()->json(['id' => $event->id]);
    }
}
