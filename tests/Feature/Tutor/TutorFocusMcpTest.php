<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Servers\EnterLmsAgentServer;
use App\Mcp\Tools\Tutor\GetFocusTool;
use App\Mcp\Tools\Tutor\ListFocusableLessonsTool;
use App\Mcp\Tools\Tutor\SetFocusTool;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathCourseProgress;
use App\Models\LearningPathEnrollment;
use App\Models\Lesson;
use App\Models\TutorFocus;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function focusLearnerOnPublishedLesson(): array
{
    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Apa itu agen',
        'order' => 1,
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'TUBUH_FOCUS_RAHASIA']],
            ]],
        ],
    ]);
    $enrollment = Enrollment::factory()->active()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
    ]);

    return compact('learner', 'course', 'section', 'lesson', 'enrollment');
}

it('sets and gets messaging Focus for an enrolled Learner', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson, 'enrollment' => $enrollment] = focusLearnerOnPublishedLesson();
    $runtime = User::factory()->lmsAdmin()->create();

    Sanctum::actingAs($runtime, AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(SetFocusTool::class, [
        'user_id' => $learner->id,
        'skin' => 'whatsapp',
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($course, $lesson, $enrollment) {
            $json->where('ok', true)
                ->where('data.skin', 'whatsapp')
                ->where('data.course_id', $course->id)
                ->where('data.lesson_id', $lesson->id)
                ->where('data.enrollment_id', $enrollment->id)
                ->etc();
        })
        ->assertDontSee('TUBUH_FOCUS_RAHASIA');

    EnterLmsAgentServer::tool(GetFocusTool::class, [
        'user_id' => $learner->id,
        'skin' => 'whatsapp',
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($lesson) {
            $json->where('ok', true)
                ->where('data.inferred', false)
                ->where('data.must_pick', false)
                ->where('data.focus.lesson_id', $lesson->id)
                ->where('data.focus.title', 'Apa itu agen')
                ->etc();
        })
        ->assertDontSee('TUBUH_FOCUS_RAHASIA');
});

it('lists focusable Lessons as titles only', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson] = focusLearnerOnPublishedLesson();
    $runtime = User::factory()->lmsAdmin()->create();

    Sanctum::actingAs($runtime, AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(ListFocusableLessonsTool::class, [
        'user_id' => $learner->id,
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($course, $lesson) {
            $json->where('ok', true)
                ->where('data.courses.0.course_id', $course->id)
                ->where('data.courses.0.lessons.0.id', $lesson->id)
                ->where('data.courses.0.lessons.0.title', 'Apa itu agen')
                ->etc();
        })
        ->assertDontSee('TUBUH_FOCUS_RAHASIA');
});

it('refuses set-focus when the Learner is not enrolled and leaves previous Focus unchanged', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson] = focusLearnerOnPublishedLesson();
    $runtime = User::factory()->lmsAdmin()->create();

    Sanctum::actingAs($runtime, AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(SetFocusTool::class, [
        'user_id' => $learner->id,
        'skin' => 'telegram',
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
    ])->assertOk();

    $other = Course::factory()->published()->public()->create();
    $otherSection = CourseSection::factory()->create(['course_id' => $other->id]);
    $otherLesson = Lesson::factory()->text()->create([
        'course_section_id' => $otherSection->id,
        'title' => 'Kursus lain',
    ]);

    EnterLmsAgentServer::tool(SetFocusTool::class, [
        'user_id' => $learner->id,
        'skin' => 'telegram',
        'course_id' => $other->id,
        'lesson_id' => $otherLesson->id,
    ])
        ->assertSee('Learner tidak terdaftar pada Course ini.');

    expect(TutorFocus::query()->where('user_id', $learner->id)->where('skin', 'telegram')->value('lesson_id'))
        ->toBe($lesson->id);
});

it('refuses set-focus on a Path-locked Course', function () {
    $learner = User::factory()->learner()->create();
    $first = Course::factory()->published()->public()->create();
    $later = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $later->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Terkunci',
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'TUBUH_PATH_TERKUNCI']],
            ]],
        ],
    ]);

    $path = LearningPath::factory()->published()->create();
    $pathEnrollment = LearningPathEnrollment::factory()->active()->create([
        'user_id' => $learner->id,
        'learning_path_id' => $path->id,
    ]);
    LearningPathCourseProgress::factory()->available()->create([
        'learning_path_enrollment_id' => $pathEnrollment->id,
        'course_id' => $first->id,
        'position' => 1,
        'course_enrollment_id' => Enrollment::factory()->active()->create([
            'user_id' => $learner->id,
            'course_id' => $first->id,
        ])->id,
    ]);
    LearningPathCourseProgress::factory()->locked()->create([
        'learning_path_enrollment_id' => $pathEnrollment->id,
        'course_id' => $later->id,
        'position' => 2,
        'course_enrollment_id' => null,
    ]);

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(SetFocusTool::class, [
        'user_id' => $learner->id,
        'skin' => 'whatsapp',
        'course_id' => $later->id,
        'lesson_id' => $lesson->id,
    ])
        ->assertSee('Pelajaran masih terkunci pada Learning Path.')
        ->assertDontSee('TUBUH_PATH_TERKUNCI');

    expect(TutorFocus::query()->where('user_id', $learner->id)->exists())->toBeFalse();
});

it('infers first Focus from the last overlay Lesson when still allowed', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson, 'enrollment' => $enrollment] = focusLearnerOnPublishedLesson();
    $enrollment->update(['last_lesson_id' => $lesson->id]);

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(GetFocusTool::class, [
        'user_id' => $learner->id,
        'skin' => 'whatsapp',
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($lesson) {
            $json->where('ok', true)
                ->where('data.inferred', true)
                ->where('data.must_pick', false)
                ->where('data.focus.lesson_id', $lesson->id)
                ->etc();
        });

    expect(TutorFocus::query()->where('user_id', $learner->id)->exists())->toBeFalse();
});

it('asks the client to pick when no Focus is stored and overlay Lesson is no longer allowed', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson, 'enrollment' => $enrollment] = focusLearnerOnPublishedLesson();
    $enrollment->update(['last_lesson_id' => $lesson->id]);
    $enrollment->drop();

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(GetFocusTool::class, [
        'user_id' => $learner->id,
        'skin' => 'telegram',
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) {
            $json->where('ok', true)
                ->where('data.must_pick', true)
                ->where('data.focus', null)
                ->etc();
        });
});

it('does not require a stored Focus row for overlay Conversation', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson] = focusLearnerOnPublishedLesson();

    expect(TutorFocus::query()->count())->toBe(0);

    $this->mock(\App\Domain\Tutor\Services\TutorRuntime::class, function ($mock) {
        $mock->shouldReceive('completeTurn')->andReturn('Agen berbeda dari chatbot.');
    });

    $this->actingAs($learner)
        ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Apa bedanya agen dengan chatbot?',
        ])
        ->assertRedirect(route('courses.lessons.show', [$course, $lesson]));

    expect(TutorFocus::query()->count())->toBe(0);
});

it('records last overlay Lesson so first messaging Focus can infer it', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson, 'enrollment' => $enrollment] = focusLearnerOnPublishedLesson();

    $this->actingAs($learner)
        ->get(route('courses.lessons.show', [$course, $lesson]))
        ->assertOk();

    expect($enrollment->fresh()->last_lesson_id)->toBe($lesson->id);
});

it('denies focus tools without tutor.read', function () {
    ['learner' => $learner, 'course' => $course, 'lesson' => $lesson] = focusLearnerOnPublishedLesson();

    Sanctum::actingAs($learner, AgentAbility::freeFlow());

    EnterLmsAgentServer::tool(GetFocusTool::class, [
        'user_id' => $learner->id,
        'skin' => 'whatsapp',
    ])->assertSee("ability 'tutor.read'");

    EnterLmsAgentServer::tool(SetFocusTool::class, [
        'user_id' => $learner->id,
        'skin' => 'whatsapp',
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
    ])->assertSee("ability 'tutor.read'");
});
