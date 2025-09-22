<?php

use App\Services\Chatwoot\Platform;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$accountId = 1;
$userId = 1;
$conversationId = 4310;
$message = $argv[1] ?? ('Test message sent at ' . now()->toDateTimeString());

try {
    $response = $app->make(Platform::class)
        ->sendMessageAsUser($accountId, $userId, $conversationId, $message);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Sending message failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
