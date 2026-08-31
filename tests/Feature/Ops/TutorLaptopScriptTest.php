<?php

use Illuminate\Support\Facades\Process;

it('starts the tutor gateway on enterlms-tutor with a dedicated Telegram token', function () {
    $path = base_path('scripts/tutor-laptop-for-prod.sh');
    $script = file_get_contents($path);

    expect($path)->toBeFile();
    expect($script)
        ->toContain('hermes -p enterlms-tutor gateway run')
        ->toContain('TELEGRAM_BOT_TOKEN')
        ->toContain('not lsptdi-ops');

    $result = Process::timeout(10)->run(['bash', '-n', $path]);

    expect($result->exitCode())->toBe(0, $result->errorOutput());
});
