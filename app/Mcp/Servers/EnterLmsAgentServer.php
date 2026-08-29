<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Agent\AgentPingTool;
use App\Mcp\Tools\Agent\EnrollCourseTool;
use App\Mcp\Tools\Agent\GetCourseTool;
use App\Mcp\Tools\Agent\GetEnrollmentTool;
use App\Mcp\Tools\Agent\GetProgressTool;
use App\Mcp\Tools\Agent\GetUserTrainingStatusTool;
use App\Mcp\Tools\Agent\ListAuditEventsTool;
use App\Mcp\Tools\Agent\ListCatalogTool;
use App\Mcp\Tools\Agent\ListCertificatesTool;
use App\Mcp\Tools\Agent\ListMyEnrollmentsTool;
use App\Mcp\Tools\Agent\MarkLessonCompleteTool;
use App\Mcp\Tools\Tutor\GetCourseOutlineTool;
use App\Mcp\Tools\Tutor\GetPublishedLessonTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('EnterLMS Agent Server')]
#[Version('1.2.0')]
#[Instructions(<<<'MARKDOWN'
EnterLMS agent capability server (Depth B + free-flow + compliance read).

- Authenticate with Sanctum Bearer token.
- Free-flow learner: `agent:token {email} --free-flow`
- Compliance: token with `agent:compliance.read` on user role compliance_officer|auditor|lms_admin
- Free-flow: list-catalog → get-course → enroll-course → get-progress → mark-lesson-complete.
- Compliance: list-audit-events, get-user-training-status, list-certificates.
- Tutor runtime: `agent:token {email} --tutor-read` then get-published-lesson / get-course-outline. Never bundled with --free-flow.
- Paid enroll rejected when payments enabled; no admin content mutation.
MARKDOWN)]
class EnterLmsAgentServer extends Server
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
        ListAuditEventsTool::class,
        GetUserTrainingStatusTool::class,
        ListCertificatesTool::class,
        GetPublishedLessonTool::class,
        GetCourseOutlineTool::class,
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
