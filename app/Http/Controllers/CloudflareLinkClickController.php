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
            'request' => ['required'],
            'response' => ['required'],
        ]);

        $reqData = $validated['request'];
        if (is_string($reqData)) {
            $decoded = json_decode($reqData, true);
            $reqData = $decoded === null ? [] : $decoded;
        }

        $resData = $validated['response'];
        if (is_string($resData)) {
            $decoded = json_decode($resData, true);
            $resData = $decoded === null ? [] : $decoded;
        }

        $link = CloudflareLink::where('key', $validated['key'])->first();

        if (! $link) {
            Log::warning('Unknown Cloudflare link key', [
                'key' => $validated['key'],
                'request' => $reqData,
                'response' => $resData,
            ]);

            return response()->json(['error' => 'Unknown key'], Response::HTTP_BAD_REQUEST);
        }

        $click = $link->clicks()->create([
            'request' => $reqData,
            'response' => $resData,
        ]);

        Log::info('Stored Cloudflare link click', [
            'id' => $click->id,
            'cloudflare_link_id' => $link->id,
            'key' => $validated['key'],
            'request' => $reqData,
            'response' => $resData,
        ]);

        return response()->json(['id' => $click->id]);
    }
}

