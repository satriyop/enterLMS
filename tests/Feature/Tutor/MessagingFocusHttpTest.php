<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Servers\EnterLmsAgentServer;
use App\Mcp\Tools\Tutor\GetFocusTool;
use App\Models\ChannelIdentity;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\TutorFocus;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function messagingFocusLesson(): array
{
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Apa itu agen',
        'order' => 1,
    ]);

    return compact('course', 'section', 'lesson');
}

it('sets WhatsApp Focus from the Lesson page and MCP reads that Focus', function () {
    ['course' => $course, 'lesson' => $lesson] = messagingFocusLesson();
    ['user' => $learner] = createEnrolledLearner($course);

    ChannelIdentity::factory()->whatsapp()->create([
        'user_id' => $learner->id,
        'identifier' => '6281111111111',
    ]);

    $this->actingAs($learner)
        ->post(route('courses.lessons.focus.store', [$course, $lesson, 'whatsapp']))
        ->assertRedirect(route('courses.lessons.show', [$course, $lesson]));

    expect(TutorFocus::query()
        ->where('user_id', $learner->id)
        ->where('skin', 'whatsapp')
        ->where('lesson_id', $lesson->id)
        ->exists())->toBeTrue();

    $runtime = User::factory()->lmsAdmin()->create();
    Sanctum::actingAs($runtime, AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(GetFocusTool::class, [
        'user_id' => $learner->id,
        'skin' => 'whatsapp',
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($lesson) {
            $json->where('ok', true)
                ->where('data.inferred', false)
                ->where('data.focus.lesson_id', $lesson->id)
                ->where('data.focus.title', 'Apa itu agen')
                ->etc();
        });
});

it('sends an unlinked Learner to channel settings after setting Focus', function () {
    ['course' => $course, 'lesson' => $lesson] = messagingFocusLesson();
    ['user' => $learner] = createEnrolledLearner($course);

    $this->actingAs($learner)
        ->post(route('courses.lessons.focus.store', [$course, $lesson, 'telegram']))
        ->assertRedirect(route('channels.edit'));

    expect(TutorFocus::query()
        ->where('user_id', $learner->id)
        ->where('skin', 'telegram')
        ->where('lesson_id', $lesson->id)
        ->exists())->toBeTrue();
});

it('forbids setting Focus without an Enrollment', function () {
    ['course' => $course, 'lesson' => $lesson] = messagingFocusLesson();
    $stranger = User::factory()->learner()->create();

    $this->actingAs($stranger)
        ->post(route('courses.lessons.focus.store', [$course, $lesson, 'whatsapp']))
        ->assertForbidden();

    expect(TutorFocus::query()->where('user_id', $stranger->id)->exists())->toBeFalse();
});

it('forbids setting Focus on a dropped Enrollment', function () {
    ['course' => $course, 'lesson' => $lesson] = messagingFocusLesson();
    $learner = User::factory()->learner()->create();
    Enrollment::factory()->dropped()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
    ]);

    $this->actingAs($learner)
        ->post(route('courses.lessons.focus.store', [$course, $lesson, 'whatsapp']))
        ->assertForbidden();
});

it('does not set Focus for a Lesson on another Course', function () {
    ['course' => $course, 'lesson' => $lesson] = messagingFocusLesson();
    ['user' => $learner] = createEnrolledLearner($course);
    $other = Course::factory()->published()->public()->create();
    Enrollment::factory()->active()->create([
        'user_id' => $learner->id,
        'course_id' => $other->id,
    ]);

    $this->actingAs($learner)
        ->post(route('courses.lessons.focus.store', [$other, $lesson, 'whatsapp']))
        ->assertNotFound();
});
