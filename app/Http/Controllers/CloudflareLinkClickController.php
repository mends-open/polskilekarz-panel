<?php

namespace App\Http\Controllers;

use App\Models\CloudflareLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CloudflareLinkClickController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'request' => ['required', 'array'],
            'response' => ['required', 'array'],
        ]);

        $link = CloudflareLink::where('key', $validated['key'])->first();

        if (! $link) {
            Log::warning('Unknown Cloudflare link key', [
                'key' => $validated['key'],
                'request' => $validated['request'],
                'response' => $validated['response'],
            ]);

            return response()->json(['error' => 'Unknown key'], Response::HTTP_BAD_REQUEST);
        }

        $click = $link->clicks()->create([
            'request' => $validated['request'],
            'response' => $validated['response'],
        ]);

        Log::info('Stored Cloudflare link click', [
            'id' => $click->id,
            'cloudflare_link_id' => $link->id,
            'key' => $validated['key'],
            'request' => $validated['request'],
            'response' => $validated['response'],
        ]);

        return response()->json(['id' => $click->id]);
    }
}

