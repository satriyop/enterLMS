<?php

/**
 * Local adapter for Valet/Herd PHP-FPM: speaks the official chat completions
 * shape so Laravel can POST /v1/chat/completions. Production target is the
 * Hermes API server, not this process.
 * Start with: php artisan tutor:serve
 */

use App\Domain\Tutor\Services\TutorRuntime;
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

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

if ($path !== '/v1/chat/completions') {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'not_found']);
    exit;
}

$expected = (string) (config('tutor.runtime_api_key') ?: config('tutor.runtime_secret'));
$authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
$given = str_starts_with($authorization, 'Bearer ') ? substr($authorization, 7) : '';

if ($expected === '' || ! hash_equals($expected, $given)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];

if ($messages === []) {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid']);
    exit;
}

$session = 'enterlms-conversation-http';
$lines = [];

foreach ($messages as $message) {
    if (! is_array($message)) {
        continue;
    }

    $role = (string) ($message['role'] ?? '');
    $content = trim((string) ($message['content'] ?? ''));

    if ($content === '') {
        continue;
    }

    if ($role === 'system' && preg_match('/Conversation id:\s*(\d+)/', $content, $match) === 1) {
        $session = 'enterlms-conversation-'.$match[1];
    }

    $label = match ($role) {
        'assistant' => 'Tutor',
        'system' => 'System',
        default => 'Learner',
    };

    $lines[] = $label.': '.$content;
}

$prompt = trim(implode("\n", $lines));

if ($prompt === '') {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid']);
    exit;
}

try {
    $reply = $app->make(TutorRuntime::class)->completeTurnFromPrompt($session, $prompt);
} catch (Throwable $e) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'runtime_failed']);
    exit;
}

header('Content-Type: application/json');
echo json_encode([
    'id' => 'chatcmpl-enterlms',
    'object' => 'chat.completion',
    'choices' => [[
        'index' => 0,
        'message' => [
            'role' => 'assistant',
            'content' => $reply,
        ],
        'finish_reason' => 'stop',
    ]],
]);
