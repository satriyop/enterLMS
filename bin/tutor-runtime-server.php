<?php

/**
 * Sidecar for Valet/Herd PHP-FPM: runs as the developer user so Hermes can spawn.
 * Start with: php artisan tutor:serve
 */

use App\Domain\Tutor\Services\TutorRuntime;
use App\Models\Conversation;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

config(['tutor.runtime_url' => '']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$secret = (string) config('tutor.runtime_secret');
$given = (string) ($_SERVER['HTTP_X_TUTOR_RUNTIME_SECRET'] ?? '');

if ($secret === '' || ! hash_equals($secret, $given)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$conversationId = (int) ($payload['conversation_id'] ?? 0);
$message = trim((string) ($payload['message'] ?? ''));

if ($conversationId < 1 || $message === '') {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid']);
    exit;
}

$conversation = Conversation::query()->with(['turns', 'enrollment'])->find($conversationId);

if ($conversation === null) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'not_found']);
    exit;
}

try {
    $reply = $app->make(TutorRuntime::class)->completeTurnViaCli($conversation, $message);
} catch (Throwable $e) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'runtime_failed']);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['reply' => $reply]);
