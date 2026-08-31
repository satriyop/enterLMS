<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Servers\EnterLmsAgentServer;
use App\Mcp\Tools\Author\GetAuthorLessonTool;
use App\Mcp\Tools\Author\ProposeContentTool;
use App\Mcp\Tools\Tutor\GetPublishedLessonTool;
use App\Models\ContentProposal;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function actingWithAuthorRead(User $user): void
{
    Sanctum::actingAs($user, AgentAbility::authorRead());
}

it('lets author.read fetch a Lesson body for LMS Admin', function () {
    $admin = User::factory()->lmsAdmin()->create();
    $course = Course::factory()->published()->public()->create(['user_id' => $admin->id]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Apa itu agen',
        'description' => 'Agen berbeda dari chatbot biasa.',
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'Agen menerima tujuan dan memakai alat.']],
            ]],
        ],
    ]);

    actingWithAuthorRead($admin);

    EnterLmsAgentServer::tool(GetAuthorLessonTool::class, [
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($course, $lesson) {
            $json->where('ok', true)
                ->where('data.course_id', $course->id)
                ->where('data.lesson_id', $lesson->id)
                ->where('data.body_ready', true)
                ->where('data.title', 'Apa itu agen')
                ->where('data.body_text', "Apa itu agen\n\nAgen berbeda dari chatbot biasa.\n\nAgen menerima tujuan dan memakai alat.")
                ->etc();
        });
});

it('does not let tutor.read or free-flow call Author tools', function () {
    $admin = User::factory()->lmsAdmin()->create();
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create(['course_section_id' => $section->id]);

    Sanctum::actingAs($admin, AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(GetAuthorLessonTool::class, [
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
    ])->assertHasErrors();

    Sanctum::actingAs($admin, AgentAbility::freeFlow());

    EnterLmsAgentServer::tool(GetAuthorLessonTool::class, [
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
    ])->assertHasErrors();
});

it('does not let author.read call Tutor lesson tools', function () {
    $admin = User::factory()->lmsAdmin()->create();
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create(['course_section_id' => $section->id]);
    $learner = User::factory()->learner()->create();

    actingWithAuthorRead($admin);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, [
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
    ])->assertHasErrors();
});

it('refuses propose-content without an asking Content Proposal', function () {
    $admin = User::factory()->lmsAdmin()->create();
    $course = Course::factory()->published()->public()->create(['user_id' => $admin->id]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create(['course_section_id' => $section->id]);

    $pending = ContentProposal::factory()->pending()->create([
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'asked_by' => $admin->id,
    ]);

    actingWithAuthorRead($admin);

    EnterLmsAgentServer::tool(ProposeContentTool::class, [
        'proposal_id' => $pending->id,
        'body_text' => 'Isi baru yang tidak diminta.',
        'reason' => 'Invented.',
    ])->assertHasErrors();

    expect($pending->fresh()->proposed_body_text)->not->toBe('Isi baru yang tidak diminta.')
        ->and($pending->fresh()->status)->toBe(ContentProposal::STATUS_PENDING);
});

it('lets author.read fill an asking Content Proposal', function () {
    $admin = User::factory()->lmsAdmin()->create();
    $course = Course::factory()->published()->public()->create(['user_id' => $admin->id]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create(['course_section_id' => $section->id]);

    $asking = ContentProposal::factory()->create([
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'asked_by' => $admin->id,
        'status' => ContentProposal::STATUS_ASKING,
        'grounding_body' => 'Agen berbeda dari chatbot biasa.',
    ]);

    actingWithAuthorRead($admin);

    EnterLmsAgentServer::tool(ProposeContentTool::class, [
        'proposal_id' => $asking->id,
        'body_text' => 'Chatbot menjawab. Agen memakai alat.',
        'reason' => 'Memisahkan dua istilah.',
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($asking) {
            $json->where('ok', true)
                ->where('data.proposal_id', $asking->id)
                ->where('data.status', ContentProposal::STATUS_PENDING)
                ->etc();
        });

    expect($asking->fresh()->proposed_body_text)->toBe('Chatbot menjawab. Agen memakai alat.')
        ->and($asking->fresh()->status)->toBe(ContentProposal::STATUS_PENDING);
});

it('issues author.read via artisan without tutor or free-flow abilities', function () {
    $user = User::factory()->lmsAdmin()->create([
        'email' => 'author-runtime@example.com',
    ]);

    $this->artisan('agent:token', [
        'user' => 'author-runtime@example.com',
        '--name' => 'author',
        '--author-read' => true,
    ])->assertSuccessful();

    $token = $user->tokens()->first();
    expect($token->can(AgentAbility::AUTHOR_READ))->toBeTrue()
        ->and($token->can(AgentAbility::TUTOR_READ))->toBeFalse()
        ->and($token->can(AgentAbility::ENROLLMENT_WRITE))->toBeFalse()
        ->and($token->can(AgentAbility::CATALOG_READ))->toBeFalse();
});

it('does not bundle author.read into --free-flow, --tutor-read, or --all-abilities', function () {
    $learner = User::factory()->learner()->create([
        'email' => 'free-flow-author@example.com',
    ]);

    $this->artisan('agent:token', [
        'user' => 'free-flow-author@example.com',
        '--free-flow' => true,
    ])->assertSuccessful();

    expect($learner->tokens()->first()->can(AgentAbility::AUTHOR_READ))->toBeFalse();

    $admin = User::factory()->lmsAdmin()->create([
        'email' => 'tutor-not-author@example.com',
    ]);

    $this->artisan('agent:token', [
        'user' => 'tutor-not-author@example.com',
        '--tutor-read' => true,
    ])->assertSuccessful();

    expect($admin->tokens()->first()->can(AgentAbility::AUTHOR_READ))->toBeFalse()
        ->and($admin->tokens()->first()->can(AgentAbility::TUTOR_READ))->toBeTrue();

    $other = User::factory()->lmsAdmin()->create([
        'email' => 'all-abilities-author@example.com',
    ]);

    $this->artisan('agent:token', [
        'user' => 'all-abilities-author@example.com',
        '--all-abilities' => true,
    ])->assertSuccessful();

    expect($other->tokens()->first()->can(AgentAbility::AUTHOR_READ))->toBeFalse()
        ->and($other->tokens()->first()->can(AgentAbility::TUTOR_READ))->toBeFalse();
});
