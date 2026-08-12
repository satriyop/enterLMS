<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Agent\AgentPingTool;
use App\Mcp\Tools\Agent\EnrollCourseTool;
use App\Mcp\Tools\Agent\GetCourseTool;
use App\Mcp\Tools\Agent\GetEnrollmentTool;
use App\Mcp\Tools\Agent\GetProgressTool;
use App\Mcp\Tools\Agent\ListCatalogTool;
use App\Mcp\Tools\Agent\ListMyEnrollmentsTool;
use App\Mcp\Tools\Agent\MarkLessonCompleteTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Enteraksi Agent Server')]
#[Version('1.1.0')]
#[Instructions(<<<'MARKDOWN'
Enteraksi LMS agent capability server (Depth B + free-flow tools).

- Authenticate with Sanctum Bearer token (`php artisan agent:token {email} --free-flow`).
- Acting-as is always the token owner.
- Free-flow: list-catalog → get-course → enroll-course → get-progress → mark-lesson-complete.
- Paid enroll is rejected while payments are disabled or when course requires payment.
- Do not attempt admin content mutation or privilege escalation.
MARKDOWN)]
class EnteraksiAgentServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        AgentPingTool::class,
        ListCatalogTool::class,
        GetCourseTool::class,
        ListMyEnrollmentsTool::class,
        GetEnrollmentTool::class,
        GetProgressTool::class,
        EnrollCourseTool::class,
        MarkLessonCompleteTool::class,
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
