<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Tutor\Services\ConversationService;
use App\Mcp\Servers\EnterLmsAgentServer;
use App\Mcp\Tools\Tutor\CommitTurnTool;
use App\Models\Conversation;
use App\Models\ConversationTurn;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function commitTurnLesson(): array
{
    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Apa itu agen',
        'is_free_preview' => false,
    ]);
    $enrollment = Enrollment::factory()->active()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
    ]);

    return compact('learner', 'course', 'section', 'lesson', 'enrollment');
}

it('writes Learner then Tutor turns through ConversationService', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson, 'enrollment' => $enrollment] = commitTurnLesson();

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(CommitTurnTool::class, [
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'learner_message' => 'Apa bedanya agen dengan chatbot?',
        'tutor_message' => 'Agen berbeda dari chatbot.',
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($enrollment, $lesson) {
            $json->where('ok', true)
                ->where('data.enrollment_id', $enrollment->id)
                ->where('data.lesson_id', $lesson->id)
                ->where('data.turns', 2)
                ->etc();
        });

    $conversation = Conversation::query()
        ->where('enrollment_id', $enrollment->id)
        ->where('lesson_id', $lesson->id)
        ->first();

    expect($conversation)->not->toBeNull()
        ->and($conversation->turns)->toHaveCount(2)
        ->and($conversation->turns[0]->role)->toBe(ConversationTurn::ROLE_LEARNER)
        ->and($conversation->turns[0]->body)->toBe('Apa bedanya agen dengan chatbot?')
        ->and($conversation->turns[1]->role)->toBe(ConversationTurn::ROLE_TUTOR)
        ->and($conversation->turns[1]->body)->toBe('Agen berbeda dari chatbot.');

    expect($enrollment->fresh()->progress_percentage)->toBe(0);
    expect(LessonProgress::query()->where('enrollment_id', $enrollment->id)->where('is_completed', true)->count())->toBe(0);
});

it('leaves no new turns when persist fails', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson] = commitTurnLesson();

    ConversationTurn::creating(function (ConversationTurn $turn) {
        if ($turn->role === ConversationTurn::ROLE_TUTOR) {
            throw new RuntimeException('db down');
        }
    });

    try {
        Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

        EnterLmsAgentServer::tool(CommitTurnTool::class, [
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'learner_message' => 'Halo',
            'tutor_message' => 'Jawaban yang tidak boleh tersimpan.',
        ])->assertSee('Gagal menyimpan percakapan.');

        expect(ConversationTurn::query()->count())->toBe(0);
    } finally {
        ConversationTurn::flushEventListeners();
    }
});

it('refuses commit-turn for a dropped Enrollment', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson, 'enrollment' => $enrollment] = commitTurnLesson();
    $enrollment->drop();

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(CommitTurnTool::class, [
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'learner_message' => 'Lagi',
        'tutor_message' => 'Tidak.',
    ])->assertSee('Enrollment sudah dinonaktifkan.');

    expect(ConversationTurn::query()->count())->toBe(0);
});

it('lets a completed Enrollment commit turns', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson, 'enrollment' => $enrollment] = commitTurnLesson();
    $enrollment->update(['status' => 'completed', 'progress_percentage' => 100, 'completed_at' => now()]);

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(CommitTurnTool::class, [
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'learner_message' => 'Masih boleh tanya?',
        'tutor_message' => 'Masih bisa bertanya.',
    ])->assertOk();

    expect($enrollment->fresh()->isCompleted())->toBeTrue()
        ->and($enrollment->fresh()->progress_percentage)->toBe(100)
        ->and(ConversationTurn::query()->count())->toBe(2);
});

it('refuses commit-turn on a preview Lesson without Enrollment', function () {
    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'is_free_preview' => true,
    ]);

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(CommitTurnTool::class, [
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'learner_message' => 'Halo',
        'tutor_message' => 'Tidak.',
    ])->assertSee('Learner tidak terdaftar pada Course ini.');

    expect(ConversationTurn::query()->count())->toBe(0);
});

it('uses the same ConversationService writer as the overlay', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson, 'enrollment' => $enrollment] = commitTurnLesson();

    $this->mock(\App\Domain\Tutor\Services\TutorRuntime::class, function ($mock) {
        $mock->shouldReceive('completeTurn')->andReturn('Dari overlay.');
    });

    $this->actingAs($learner)
        ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Dari overlay',
        ])
        ->assertRedirect();

    $fromOverlay = app(ConversationService::class)->forEnrollmentAndLesson($enrollment, $lesson);
    expect($fromOverlay?->turns)->toHaveCount(2);

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(CommitTurnTool::class, [
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'learner_message' => 'Dari MCP',
        'tutor_message' => 'Juga dari MCP.',
    ])->assertOk();

    $conversation = $fromOverlay->fresh(['turns']);
    expect($conversation->turns)->toHaveCount(4)
        ->and($conversation->turns->pluck('body')->all())->toContain('Dari overlay', 'Dari MCP');
});

it('still deletes the Conversation when the Lesson is deleted after commit-turn', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson, 'enrollment' => $enrollment] = commitTurnLesson();

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(CommitTurnTool::class, [
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'learner_message' => 'Halo',
        'tutor_message' => 'Hai.',
    ])->assertOk();

    $lesson->delete();

    expect(Conversation::query()->where('enrollment_id', $enrollment->id)->count())->toBe(0);
    expect(ConversationTurn::query()->count())->toBe(0);
});

it('denies commit-turn without tutor.read', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson] = commitTurnLesson();

    Sanctum::actingAs($learner, AgentAbility::freeFlow());

    EnterLmsAgentServer::tool(CommitTurnTool::class, [
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'learner_message' => 'Halo',
        'tutor_message' => 'Hai.',
    ])->assertSee("ability 'tutor.read'");
});
