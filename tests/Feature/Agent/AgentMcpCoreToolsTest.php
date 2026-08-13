<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Servers\EnterLmsAgentServer;
use App\Mcp\Tools\Agent\EnrollCourseTool;
use App\Mcp\Tools\Agent\GetCourseTool;
use App\Mcp\Tools\Agent\GetEnrollmentTool;
use App\Mcp\Tools\Agent\GetProgressTool;
use App\Mcp\Tools\Agent\ListCatalogTool;
use App\Mcp\Tools\Agent\ListMyEnrollmentsTool;
use App\Mcp\Tools\Agent\MarkLessonCompleteTool;
use App\Models\AgentActionLog;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function freeFlowAbilities(): array
{
    return AgentAbility::freeFlow();
}

function actingAgent(User $user, ?array $abilities = null): void
{
    Sanctum::actingAs($user, $abilities ?? freeFlowAbilities());
}

it('lists published public courses in catalog', function () {
    $user = User::factory()->learner()->create();
    $public = Course::factory()->published()->create([
        'visibility' => 'public',
        'title' => 'AML Fundamentals',
    ]);
    Course::factory()->draft()->create(['visibility' => 'public']);
    Course::factory()->published()->create(['visibility' => 'hidden']);

    actingAgent($user);

    EnterLmsAgentServer::tool(ListCatalogTool::class, [])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($public) {
            $json->where('ok', true)
                ->has('data')
                ->where('data.0.id', $public->id)
                ->etc();
        });
});

it('denies list-catalog without ability', function () {
    $user = User::factory()->learner()->create();
    actingAgent($user, [AgentAbility::PING]);

    EnterLmsAgentServer::tool(ListCatalogTool::class)
        ->assertSee("ability 'agent:catalog.read'");
});

it('returns course outline for public published course', function () {
    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->create(['visibility' => 'public']);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['course_section_id' => $section->id, 'title' => 'Intro']);

    actingAgent($user);

    EnterLmsAgentServer::tool(GetCourseTool::class, ['course_id' => $course->id])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($course, $lesson) {
            $json->where('ok', true)
                ->where('data.id', $course->id)
                ->where('data.sections.0.lessons.0.id', $lesson->id)
                ->etc();
        });
});

it('runs free-flow enroll and mark lesson complete with audit', function () {
    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->create([
        'visibility' => 'public',
        'is_paid' => false,
    ]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

    actingAgent($user);

    EnterLmsAgentServer::tool(EnrollCourseTool::class, ['course_id' => $course->id])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($course) {
            $json->where('ok', true)
                ->where('data.course_id', $course->id)
                ->where('data.status', 'active')
                ->etc();
        });

    $enrollment = Enrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->first();
    expect($enrollment)->not->toBeNull();

    EnterLmsAgentServer::tool(ListMyEnrollmentsTool::class)
        ->assertOk()
        ->assertSee((string) $enrollment->id);

    EnterLmsAgentServer::tool(GetEnrollmentTool::class, ['enrollment_id' => $enrollment->id])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($enrollment) {
            $json->where('ok', true)
                ->where('data.id', $enrollment->id)
                ->etc();
        });

    EnterLmsAgentServer::tool(MarkLessonCompleteTool::class, [
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson->id,
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) {
            $json->where('ok', true)
                ->where('data.lesson_completed', true)
                ->etc();
        });

    EnterLmsAgentServer::tool(GetProgressTool::class, ['enrollment_id' => $enrollment->id])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($lesson) {
            $json->where('ok', true)
                ->where('data.lessons.0.lesson_id', $lesson->id)
                ->where('data.lessons.0.is_completed', true)
                ->etc();
        });

    expect(AgentActionLog::query()->where('tool', 'enroll-course')->where('status', 'success')->exists())->toBeTrue();
    expect(AgentActionLog::query()->where('tool', 'mark-lesson-complete')->where('status', 'success')->exists())->toBeTrue();
});

it('rejects enroll when payments enabled and course is paid', function () {
    config()->set('lms.mode', 'commercial');
    config()->set('lms.payment.enabled', true);

    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->create([
        'visibility' => 'public',
        'is_paid' => true,
        'price' => 100000,
    ]);

    actingAgent($user);

    EnterLmsAgentServer::tool(EnrollCourseTool::class, ['course_id' => $course->id])
        ->assertSee('payment_required');

    expect(Enrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeFalse();
});

it('forbids marking progress on another users enrollment', function () {
    $alice = User::factory()->learner()->create();
    $bob = User::factory()->learner()->create();
    $course = Course::factory()->published()->create(['visibility' => 'public']);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);
    $bobEnrollment = Enrollment::factory()->active()->create([
        'user_id' => $bob->id,
        'course_id' => $course->id,
    ]);

    actingAgent($alice);

    EnterLmsAgentServer::tool(MarkLessonCompleteTool::class, [
        'enrollment_id' => $bobEnrollment->id,
        'lesson_id' => $lesson->id,
    ])->assertSee('tidak milik');

    EnterLmsAgentServer::tool(GetEnrollmentTool::class, [
        'enrollment_id' => $bobEnrollment->id,
    ])->assertSee('tidak milik');
});

it('exposes free-flow tools when listing tools over http', function () {
    $user = User::factory()->learner()->create();
    $token = $user->createToken('t', freeFlowAbilities());

    $response = $this->withToken($token->plainTextToken)
        ->postJson('/mcp/enterlms', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => new stdClass,
        ]);

    $response->assertSuccessful();
    $names = collect($response->json('result.tools'))->pluck('name')->all();

    expect($names)->toContain(
        'list-catalog',
        'get-course',
        'list-my-enrollments',
        'get-enrollment',
        'get-progress',
        'enroll-course',
        'mark-lesson-complete',
        'agent-ping',
    );
});
