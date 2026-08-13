<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Servers\EnterLmsAgentServer;
use App\Mcp\Tools\Agent\GetUserTrainingStatusTool;
use App\Mcp\Tools\Agent\ListAuditEventsTool;
use App\Mcp\Tools\Agent\ListCertificatesTool;
use App\Models\AgentActionLog;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

it('lists audit events for compliance officer with ability', function () {
    $officer = User::factory()->create(['role' => 'compliance_officer']);
    $learner = User::factory()->learner()->create();

    DB::table('domain_event_log')->insert([
        'event_id' => (string) \Illuminate\Support\Str::uuid(),
        'event_name' => 'UserEnrolled',
        'aggregate_type' => 'Enrollment',
        'aggregate_id' => 1,
        'actor_id' => $learner->id,
        'metadata' => json_encode(['course_id' => 1, 'password' => 'secret-should-strip']),
        'occurred_at' => now()->subDay()->format('Y-m-d H:i:s'),
        'created_at' => now(),
    ]);

    Sanctum::actingAs($officer, complianceAbilities());

    EnterLmsAgentServer::tool(ListAuditEventsTool::class, [
        'start_date' => now()->subDays(7)->toDateString(),
        'end_date' => now()->toDateString(),
        'event_name' => 'UserEnrolled',
        'limit' => 10,
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) {
            $json->where('ok', true)
                ->has('data')
                ->etc();
        })
        ->assertDontSee('secret-should-strip');
});

it('returns user training status for auditor', function () {
    $auditor = User::factory()->create(['role' => 'auditor']);
    $learner = User::factory()->learner()->create(['name' => 'Budi Training']);
    $course = Course::factory()->published()->create();
    Enrollment::factory()->completed()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'progress_percentage' => 100,
    ]);
    Certificate::factory()->create([
        'user_id' => $learner->id,
        'certificable_type' => Course::class,
        'certificable_id' => $course->id,
        'status' => Certificate::STATUS_ACTIVE,
    ]);

    Sanctum::actingAs($auditor, complianceAbilities());

    EnterLmsAgentServer::tool(GetUserTrainingStatusTool::class, [
        'user_id' => $learner->id,
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($learner) {
            $json->where('ok', true)
                ->where('data.user.id', $learner->id)
                ->where('data.user.name', 'Budi Training')
                ->where('data.summary.completed', 1)
                ->where('data.summary.certificates_active', 1)
                ->etc();
        })
        ->assertDontSee('password');
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
