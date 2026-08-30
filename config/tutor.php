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

    /*
    | Hermes profile for `hermes -p {profile} chat`. Empty uses default
    | HERMES_HOME (wrong for Tutor — that profile has other MCP servers).
    */
    'hermes_profile' => env('TUTOR_HERMES_PROFILE', ''),

    'skill' => env('TUTOR_HERMES_SKILL', 'tutor'),

    /*
    | completeTurn raises PHP's max_execution_time to this value plus a
    | short buffer. Valet FPM defaults to 30s, which is shorter than Hermes.
    */
    'timeout_seconds' => (int) env('TUTOR_HERMES_TIMEOUT', 90),

    'max_turns' => (int) env('TUTOR_HERMES_MAX_TURNS', 8),

    /*
    | When set, completeTurn POSTs OpenAI-compatible chat completions to this
    | Hermes API base (e.g. http://127.0.0.1:8642). The browser never sees
    | the API key — only Laravel holds it.
    */
    'runtime_url' => env('TUTOR_RUNTIME_URL', ''),

    'runtime_api_key' => env('TUTOR_RUNTIME_API_KEY', env('TUTOR_RUNTIME_SECRET', '')),

    'runtime_secret' => env('TUTOR_RUNTIME_SECRET', ''),

    'model' => env('TUTOR_HERMES_MODEL', 'hermes'),
];
