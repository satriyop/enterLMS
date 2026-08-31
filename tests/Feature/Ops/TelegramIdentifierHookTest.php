<?php

use Illuminate\Support\Facades\Process;

function telegramIdentifierHook(array $payload): object
{
    $script = base_path('scripts/tutor/inject-telegram-identifier.py');

    return Process::timeout(10)
        ->input(json_encode($payload))
        ->run(['python3', $script]);
}

it('injects the numeric Telegram id into the turn context', function () {
    $result = telegramIdentifierHook([
        'hook_event_name' => 'pre_llm_call',
        'extra' => [
            'platform' => 'telegram',
            'sender_id' => '99887766',
        ],
    ]);

    expect($result->exitCode())->toBe(0, $result->errorOutput());

    $body = json_decode($result->output(), true);
    expect($body['context'])
        ->toContain('99887766')
        ->toContain('Never the display name');
});

it('rewrites a Telegram display name on resolve to the numeric sender id', function () {
    $result = telegramIdentifierHook([
        'hook_event_name' => 'pre_tool_call',
        'tool_name' => 'mcp__enterlms__resolve',
        'tool_input' => [
            'channel' => 'telegram',
            'identifier' => 'Budi Santoso',
        ],
        'extra' => [
            'platform' => 'telegram',
            'sender_id' => '99887766',
        ],
    ]);

    expect($result->exitCode())->toBe(0, $result->errorOutput());

    $body = json_decode($result->output(), true);
    expect($body['action'])->toBe('modify')
        ->and($body['args']['identifier'])->toBe('99887766');
});

it('does not rewrite a numeric Telegram identifier', function () {
    $result = telegramIdentifierHook([
        'hook_event_name' => 'pre_tool_call',
        'tool_name' => 'mcp__enterlms__resolve',
        'tool_input' => [
            'channel' => 'telegram',
            'identifier' => '99887766',
        ],
        'extra' => [
            'platform' => 'telegram',
            'sender_id' => '99887766',
        ],
    ]);

    expect($result->exitCode())->toBe(0, $result->errorOutput())
        ->and(trim($result->output()))->toBe('{}');
});
