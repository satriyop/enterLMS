<?php

/**
 * Reading what the Tutor taught.
 *
 * CONTEXT.md: a Conversation is "Readable by that Learner, by LMS Admin, and
 * by the Facilitator of that Enrollment's Offering." The Learner's own read is
 * the Lesson overlay; these are the other two.
 */

use App\Models\Conversation;
use App\Models\ConversationTurn;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Offering;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * @return array{course: Course, lesson: Lesson}
 */
function reviewCourse(string $lessonTitle = 'Apa itu agen'): array
{
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => $lessonTitle,
        'order' => 1,
    ]);

    return compact('course', 'lesson');
}

function conversationFor(Course $course, Lesson $lesson, ?Offering $offering = null, string $question = 'apa itu loop'): Conversation
{
    $learner = User::factory()->create(['role' => 'learner']);

    $enrollment = Enrollment::factory()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'offering_id' => $offering?->id ?? $course->ensureDefaultOffering()->id,
        'status' => 'active',
    ]);

    $conversation = Conversation::factory()->create([
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson->id,
    ]);

    ConversationTurn::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => ConversationTurn::ROLE_LEARNER,
        'body' => $question,
    ]);
    ConversationTurn::factory()->create([
        'conversation_id' => $conversation->id,
        'role' => ConversationTurn::ROLE_TUTOR,
        'body' => 'Loop adalah salah satu dari tiga bagian agen.',
    ]);

    return $conversation;
}

it('shows an lms admin every conversation on a course they run', function () {
    ['course' => $course, 'lesson' => $lesson] = reviewCourse();

    conversationFor($course, $lesson, question: 'apa itu loop');
    conversationFor($course, $lesson, question: 'kapan sertifikat terbit');

    $this->actingAs(User::factory()->create(['role' => 'lms_admin']))
        ->get(route('courses.conversations.index', $course))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/conversations/Index')
            ->has('conversations.data', 2)
            ->where('conversations.data.0.turns_count', 2)
        );
});

it('shows a facilitator only the offerings they were granted', function () {
    ['course' => $course, 'lesson' => $lesson] = reviewCourse();

    $facilitator = User::factory()->create(['role' => 'learner']);
    $mine = Offering::factory()->create(['course_id' => $course->id, 'facilitator_id' => $facilitator->id]);
    $theirs = Offering::factory()->create(['course_id' => $course->id]);

    conversationFor($course, $lesson, $mine, 'pertanyaan kelas saya');
    conversationFor($course, $lesson, $theirs, 'pertanyaan kelas lain');

    $this->actingAs($facilitator)
        ->get(route('courses.conversations.index', $course))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('conversations.data', 1)
            ->where('conversations.data.0.opening_question', 'pertanyaan kelas saya')
            // The filter must not offer a Kelas they cannot read.
            ->has('offerings', 1)
        );
});

it('refuses a learner who merely has an enrollment on the course', function () {
    ['course' => $course, 'lesson' => $lesson] = reviewCourse();

    $conversation = conversationFor($course, $lesson);
    $learner = $conversation->enrollment->user;

    $this->actingAs($learner)
        ->get(route('courses.conversations.index', $course))
        ->assertForbidden();
});

it('refuses a facilitator of a different course', function () {
    ['course' => $course, 'lesson' => $lesson] = reviewCourse();
    conversationFor($course, $lesson);

    $elsewhere = Course::factory()->published()->create();
    $facilitator = User::factory()->create(['role' => 'learner']);
    Offering::factory()->create(['course_id' => $elsewhere->id, 'facilitator_id' => $facilitator->id]);

    $this->actingAs($facilitator)
        ->get(route('courses.conversations.index', $course))
        ->assertForbidden();
});

it('reads a transcript with both voices in order', function () {
    ['course' => $course, 'lesson' => $lesson] = reviewCourse();
    $conversation = conversationFor($course, $lesson);

    $this->actingAs(User::factory()->create(['role' => 'lms_admin']))
        ->get(route('courses.conversations.show', [$course, $conversation]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/conversations/Show')
            ->where('conversation.turns.0.role', 'learner')
            ->where('conversation.turns.1.role', 'tutor')
            ->where('conversation.lesson.title', 'Apa itu agen')
        );
});

/**
 * Route model binding does not scope a child to its parent, so without an
 * explicit check a reader could reach any Conversation by id through a Course
 * they happen to run.
 */
it('does not serve a conversation from another course through this one', function () {
    ['course' => $mine] = reviewCourse();
    ['course' => $other, 'lesson' => $otherLesson] = reviewCourse('Lesson lain');

    $foreign = conversationFor($other, $otherLesson);

    $this->actingAs(User::factory()->create(['role' => 'lms_admin']))
        ->get(route('courses.conversations.show', [$mine, $foreign]))
        ->assertNotFound();
});

it('refuses a facilitator the transcript of an offering they do not hold', function () {
    ['course' => $course, 'lesson' => $lesson] = reviewCourse();

    $facilitator = User::factory()->create(['role' => 'learner']);
    Offering::factory()->create(['course_id' => $course->id, 'facilitator_id' => $facilitator->id]);
    $theirs = Offering::factory()->create(['course_id' => $course->id]);

    $foreign = conversationFor($course, $lesson, $theirs);

    $this->actingAs($facilitator)
        ->get(route('courses.conversations.show', [$course, $foreign]))
        ->assertForbidden();
});

it('does not let a search match a Learner who is not on this conversation', function () {
    ['course' => $course, 'lesson' => $lesson] = reviewCourse();

    conversationFor($course, $lesson, question: 'hanya alice');
    User::factory()->create(['email' => 'stranger@gmail.com']);

    $this->actingAs(User::factory()->create(['role' => 'lms_admin']))
        ->get(route('courses.conversations.index', [$course, 'search' => 'gmail.com']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('conversations.data', 0)
        );
});

it('narrows the list to one lesson', function () {
    ['course' => $course, 'lesson' => $first] = reviewCourse();
    $second = Lesson::factory()->text()->create([
        'course_section_id' => $first->course_section_id,
        'title' => 'Lesson kedua',
        'order' => 2,
    ]);

    conversationFor($course, $first, question: 'tentang lesson pertama');
    conversationFor($course, $second, question: 'tentang lesson kedua');

    $this->actingAs(User::factory()->create(['role' => 'lms_admin']))
        ->get(route('courses.conversations.index', [$course, 'lesson_id' => $second->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('conversations.data', 1)
            ->where('conversations.data.0.opening_question', 'tentang lesson kedua')
        );
});
