<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$accountId = 1;
$userId = 1;
$conversationId = 4310;
$message = 'Test message sent at ' . now()->toDateTimeString();

$config = config('services.chatwoot');
$endpoint = rtrim((string) ($config['endpoint'] ?? ''), '/');
$token = (string) ($config['platform_access_token'] ?? $config['api_access_token'] ?? '');

if ($endpoint === '') {
    fwrite(STDERR, "Chatwoot endpoint is not configured.\n");
    exit(1);
}

if ($token === '') {
    fwrite(STDERR, "Chatwoot platform/api access token is not configured.\n");
    exit(1);
}

$response = Http::baseUrl($endpoint)
    ->acceptJson()
    ->asJson()
    ->withHeaders([
        'api_access_token' => $token,
    ])
    ->post(sprintf('api/v1/accounts/%d/conversations/%d/messages', $accountId, $conversationId), [
        'content' => $message,
        'message_type' => 'outgoing',
        'private' => false,
        'sender_id' => $userId,
    ]);

if ($response->failed()) {
    fwrite(STDERR, sprintf(
        "Request failed with status %d: %s\n",
        $response->status(),
        $response->body()
    ));
    exit(1);
}

echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
