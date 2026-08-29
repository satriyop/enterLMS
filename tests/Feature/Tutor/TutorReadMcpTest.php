<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Servers\EnterLmsAgentServer;
use App\Mcp\Tools\Agent\EnrollCourseTool;
use App\Mcp\Tools\Agent\MarkLessonCompleteTool;
use App\Mcp\Tools\Tutor\GetCourseOutlineTool;
use App\Mcp\Tools\Tutor\GetPublishedLessonTool;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function actingWithTutorRead(User $user): void
{
    Sanctum::actingAs($user, AgentAbility::tutorRead());
}

function actingWithFreeFlowAgent(User $user): void
{
    Sanctum::actingAs($user, AgentAbility::freeFlow());
}

function publishedCourseWithSecretLaterLesson(): array
{
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
    $current = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Apa itu agen',
        'description' => 'Agen berbeda dari chatbot biasa.',
        'order' => 1,
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'Agen menerima tujuan dan memakai alat.']],
            ]],
        ],
    ]);
    $later = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Kill switch',
        'description' => 'RAHASIA_LEMBAGA_KEMUDIAN',
        'order' => 2,
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'RAHASIA_LEMBAGA_KEMUDIAN']],
            ]],
        ],
    ]);

    return compact('course', 'section', 'current', 'later');
}

it('lets tutor.read fetch this published Lesson body as it is now', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current] = publishedCourseWithSecretLaterLesson();

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, [
        'course_id' => $course->id,
        'lesson_id' => $current->id,
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($course, $current) {
            $json->where('ok', true)
                ->where('data.course_id', $course->id)
                ->where('data.lesson_id', $current->id)
                ->where('data.title', 'Apa itu agen')
                ->etc();
        });
});

it('does not return a later Lesson body when reading the current Lesson', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current] = publishedCourseWithSecretLaterLesson();

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, [
        'course_id' => $course->id,
        'lesson_id' => $current->id,
    ])
        ->assertOk()
        ->assertDontSee('RAHASIA_LEMBAGA_KEMUDIAN');
});

it('does not return another Course Lesson', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course] = publishedCourseWithSecretLaterLesson();

    $other = Course::factory()->published()->public()->create();
    $otherSection = CourseSection::factory()->create(['course_id' => $other->id]);
    $otherLesson = Lesson::factory()->text()->create([
        'course_section_id' => $otherSection->id,
        'title' => 'Foreign lesson',
        'description' => 'TUBUH_KURSUS_LAIN',
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'TUBUH_KURSUS_LAIN']],
            ]],
        ],
    ]);

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, [
        'course_id' => $course->id,
        'lesson_id' => $otherLesson->id,
    ])
        ->assertSee('Pelajaran tidak termasuk dalam Course ini.')
        ->assertDontSee('TUBUH_KURSUS_LAIN');
});

it('returns Course outline titles only', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current, 'later' => $later] = publishedCourseWithSecretLaterLesson();

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetCourseOutlineTool::class, [
        'course_id' => $course->id,
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($course, $current, $later) {
            $json->where('ok', true)
                ->where('data.course_id', $course->id)
                ->where('data.sections.0.lessons.0.id', $current->id)
                ->where('data.sections.0.lessons.0.title', $current->title)
                ->where('data.sections.0.lessons.1.id', $later->id)
                ->where('data.sections.0.lessons.1.title', $later->title)
                ->etc();
        })
        ->assertDontSee('RAHASIA_LEMBAGA_KEMUDIAN')
        ->assertDontSee('Agen menerima tujuan');
});

it('denies get-published-lesson without tutor.read', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current] = publishedCourseWithSecretLaterLesson();

    actingWithFreeFlowAgent($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, [
        'course_id' => $course->id,
        'lesson_id' => $current->id,
    ])->assertSee("ability 'tutor.read'");
});

it('denies get-course-outline without tutor.read', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course] = publishedCourseWithSecretLaterLesson();

    actingWithFreeFlowAgent($user);

    EnterLmsAgentServer::tool(GetCourseOutlineTool::class, [
        'course_id' => $course->id,
    ])->assertSee("ability 'tutor.read'");
});

it('forbids enroll and complete with a tutor.read token', function () {
    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->public()->create(['is_paid' => false]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(EnrollCourseTool::class, ['course_id' => $course->id])
        ->assertSee("ability 'agent:enrollment.write'");

    expect(Enrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->exists())
        ->toBeFalse();

    $enrollment = Enrollment::factory()->active()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);

    EnterLmsAgentServer::tool(MarkLessonCompleteTool::class, [
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson->id,
    ])->assertSee("ability 'agent:progress.write'");
});

it('issues tutor.read via artisan without free-flow abilities', function () {
    $user = User::factory()->create([
        'email' => 'tutor-runtime@example.com',
        'role' => 'lms_admin',
    ]);

    $this->artisan('agent:token', [
        'user' => 'tutor-runtime@example.com',
        '--name' => 'tutor',
        '--tutor-read' => true,
    ])->assertSuccessful();

    $token = $user->tokens()->first();
    expect($token->can(AgentAbility::TUTOR_READ))->toBeTrue()
        ->and($token->can(AgentAbility::ENROLLMENT_WRITE))->toBeFalse()
        ->and($token->can(AgentAbility::PROGRESS_WRITE))->toBeFalse()
        ->and($token->can(AgentAbility::CATALOG_READ))->toBeFalse();
});

it('does not bundle tutor.read into --free-flow or --all-abilities', function () {
    $user = User::factory()->create([
        'email' => 'free-flow-owner@example.com',
        'role' => 'learner',
    ]);

    $this->artisan('agent:token', [
        'user' => 'free-flow-owner@example.com',
        '--free-flow' => true,
    ])->assertSuccessful();

    expect($user->tokens()->first()->can(AgentAbility::TUTOR_READ))->toBeFalse()
        ->and($user->tokens()->first()->can(AgentAbility::ENROLLMENT_WRITE))->toBeTrue();

    $other = User::factory()->create([
        'email' => 'all-abilities@example.com',
        'role' => 'lms_admin',
    ]);

    $this->artisan('agent:token', [
        'user' => 'all-abilities@example.com',
        '--all-abilities' => true,
    ])->assertSuccessful();

    expect($other->tokens()->first()->can(AgentAbility::TUTOR_READ))->toBeFalse();
});

it('lets tutor.read read a published restricted Course Lesson', function () {
    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->restricted()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Kill switch',
        'description' => 'Wewenang Operator.',
    ]);

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, [
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($lesson) {
            $json->where('ok', true)
                ->where('data.lesson_id', $lesson->id)
                ->where('data.title', 'Kill switch')
                ->etc();
        });
});
