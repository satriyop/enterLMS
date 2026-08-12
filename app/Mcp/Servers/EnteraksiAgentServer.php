<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Agent\AgentPingTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Enteraksi Agent Server')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
Enteraksi LMS agent capability server (Depth B).

- Authenticate with Sanctum Bearer token that has agent abilities (e.g. agent:ping).
- Acting-as is always the token owner.
- Prefer read tools first; write tools are limited to free-flow enroll/progress (see B-013).
- Do not attempt payment, admin content mutation, or privilege escalation.
MARKDOWN)]
class EnteraksiAgentServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        AgentPingTool::class,
    ];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [];

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [];
}
