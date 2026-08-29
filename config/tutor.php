<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tutor runtime (Hermes)
    |--------------------------------------------------------------------------
    |
    | completeTurn invokes local `hermes chat`, not the Telegram gateway.
    | Leave hermes_binary empty in CI — Conversation HTTP tests fake TutorRuntime.
    |
    */
    'hermes_binary' => env('TUTOR_HERMES_BINARY', ''),

    'skill' => env('TUTOR_HERMES_SKILL', 'tutor'),

    /*
    | completeTurn raises PHP's max_execution_time to this value plus a
    | short buffer. Valet FPM defaults to 30s, which is shorter than Hermes.
    */
    'timeout_seconds' => (int) env('TUTOR_HERMES_TIMEOUT', 90),

    'max_turns' => (int) env('TUTOR_HERMES_MAX_TURNS', 8),

    /*
    | When PHP-FPM cannot spawn Hermes (e.g. Valet's _www user), point
    | completeTurn at `php artisan tutor:serve` instead of the CLI.
    */
    'runtime_url' => env('TUTOR_RUNTIME_URL', ''),

    'runtime_secret' => env('TUTOR_RUNTIME_SECRET', ''),
];
