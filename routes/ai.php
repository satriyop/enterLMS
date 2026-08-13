<?php

use App\Mcp\Servers\EnterLmsAgentServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| Agent MCP (Hermes / OpenClaw) — Depth B
|--------------------------------------------------------------------------
|
| Clients must send: Authorization: Bearer <sanctum-personal-access-token>
| Issue tokens via: php artisan agent:token {email}
| (default ability: agent:ping only; use --free-flow after B-013 tools)
|
*/

Mcp::web('/mcp/enterlms', EnterLmsAgentServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp']);
