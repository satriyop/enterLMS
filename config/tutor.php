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

    'timeout_seconds' => (int) env('TUTOR_HERMES_TIMEOUT', 90),

    'max_turns' => (int) env('TUTOR_HERMES_MAX_TURNS', 8),
];
