<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Servers\EnterLmsAgentServer;
use App\Mcp\Tools\Agent\ListAuditEventsTool;
use App\Mcp\Tools\Agent\ListCertificatesTool;
use App\Models\AgentActionLog;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function complianceAbilities(): array
{
    return [AgentAbility::COMPLIANCE_READ, AgentAbility::PING];
}

it('denies compliance tools for learner even with ability', function () {
    $learner = User::factory()->learner()->create();
    Sanctum::actingAs($learner, complianceAbilities());

    EnterLmsAgentServer::tool(ListAuditEventsTool::class, [])
        ->assertSee('role_forbidden');

    expect(AgentActionLog::query()->where('tool', 'list-audit-events')->where('status', 'denied')->exists())
        ->toBeTrue();
});

it('denies compliance tools without ability for admin', function () {
    $admin = User::factory()->lmsAdmin()->create();
    Sanctum::actingAs($admin, [AgentAbility::PING]);

    EnterLmsAgentServer::tool(ListAuditEventsTool::class, [])
        ->assertSee("ability 'agent:compliance.read'");
});

it('lists certificates for lms admin', function () {
    $admin = User::factory()->lmsAdmin()->create();
    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    Certificate::factory()->create([
        'user_id' => $learner->id,
        'certificable_type' => Course::class,
        'certificable_id' => $course->id,
        'status' => Certificate::STATUS_ACTIVE,
        'certificable_title' => 'Cyber Security 101',
    ]);

    Sanctum::actingAs($admin, complianceAbilities());

    EnterLmsAgentServer::tool(ListCertificatesTool::class, [
        'user_id' => $learner->id,
        'limit' => 20,
    ])
        ->assertOk()
        ->assertSee('Cyber Security 101');
});
