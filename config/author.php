<?php

return [
    /*
    | Author Agent runtime (Hermes). Overlay POSTs OpenAI-compatible
    | chat completions. The browser never sees this key. Empty in CI —
    | Content Proposal HTTP tests fake AuthorRuntime.
    */
    'runtime_url' => env('AUTHOR_RUNTIME_URL', ''),

    'runtime_api_key' => env('AUTHOR_RUNTIME_API_KEY', ''),

    'timeout_seconds' => (int) env('AUTHOR_HERMES_TIMEOUT', 90),

    'model' => env('AUTHOR_HERMES_MODEL', 'hermes'),
];
