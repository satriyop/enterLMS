<?php

use App\Mcp\Servers\EnteraksiAgentServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| Agent MCP (Hermes / OpenClaw) — Depth B
|--------------------------------------------------------------------------
|
| Clients must send: Authorization: Bearer <sanctum-personal-access-token>
| Issue tokens via: php artisan agent:token {email} --ability=agent:ping
|
*/

Mcp::web('/mcp/enteraksi', EnteraksiAgentServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp']);
