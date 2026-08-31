<?php

use App\Domain\Content\Services\AuthorRuntime;
use App\Models\ContentProposal;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Offering;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function authorLesson(string $body = 'Agen berbeda dari chatbot biasa.'): array
{
    $admin = User::factory()->lmsAdmin()->create();
    $course = Course::factory()->published()->public()->create(['user_id' => $admin->id]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Apa itu agen',
        'description' => $body,
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => $body]],
            ]],
        ],
    ]);

    return compact('admin', 'course', 'section', 'lesson');
}

function fakeAuthorDraft(string $body = 'Chatbot menjawab. Agen memakai alat.', string $reason = 'Perlu dipisahkan.'): void
{
    test()->mock(AuthorRuntime::class, function ($mock) use ($body, $reason) {
        $mock->shouldReceive('propose')->andReturn([
            'reason' => $reason,
            'body_text' => $body,
        ]);
    });
}

it('lets LMS Admin ask for a Content Proposal on a Course Lesson', function () {
    fakeAuthorDraft('Chatbot menjawab percakapan. Agen menerima tujuan.');

    ['admin' => $admin, 'course' => $course, 'lesson' => $lesson] = authorLesson();

    $this->actingAs($admin)
        ->post(route('courses.content-proposals.store', $course), [
            'lesson_id' => $lesson->id,
            'instruction' => 'Perjelas bedanya chatbot dan agen.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $proposal = ContentProposal::query()->where('course_id', $course->id)->first();

    expect($proposal)->not->toBeNull()
        ->and($proposal->status)->toBe(ContentProposal::STATUS_PENDING)
        ->and($proposal->asked_by)->toBe($admin->id)
        ->and($proposal->lesson_id)->toBe($lesson->id)
        ->and($proposal->grounding_body)->toContain('Agen berbeda dari chatbot biasa.')
        ->and($proposal->proposed_body_text)->toBe('Chatbot menjawab percakapan. Agen menerima tujuan.');
});

it('does not send the Author runtime API key to the Course edit page', function () {
    config()->set('author.runtime_api_key', 'secret-author-key-do-not-leak');

    ['admin' => $admin, 'course' => $course] = authorLesson();

    $this->actingAs($admin)
        ->get(route('courses.edit', $course))
        ->assertOk()
        ->assertDontSee('secret-author-key-do-not-leak')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Edit')
            ->where('can.proposeContent', true)
            ->has('contentProposals')
        );
});

it('accepts a pending proposal and Learners see the new Lesson body', function () {
    ['admin' => $admin, 'course' => $course, 'lesson' => $lesson] = authorLesson('Isi lama agen.');
    $newBody = 'Chatbot menjawab percakapan. Agen menerima tujuan dan memakai alat.';

    $proposal = ContentProposal::factory()->pending()->create([
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'asked_by' => $admin->id,
        'proposed_body_text' => $newBody,
        'proposed_rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => $newBody]],
            ]],
        ],
    ]);

    $this->actingAs($admin)
        ->post(route('courses.content-proposals.accept', [$course, $proposal]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $proposal->refresh();
    $lesson->refresh();

    expect($proposal->status)->toBe(ContentProposal::STATUS_ACCEPTED)
        ->and($lesson->readableBody())->toContain('Agen menerima tujuan dan memakai alat.')
        ->and($lesson->readableBody())->not->toContain('Isi lama agen.');

    ['user' => $learner] = createEnrolledLearner($course);

    $this->actingAs($learner)
        ->get(route('courses.lessons.show', [$course, $lesson]))
        ->assertOk()
        ->assertSee('Agen menerima tujuan dan memakai alat.')
        ->assertDontSee('Isi lama agen.');
});

it('rejects a pending proposal and leaves the Lesson unchanged', function () {
    $oldBody = 'Isi lama tetap.';
    ['admin' => $admin, 'course' => $course, 'lesson' => $lesson] = authorLesson($oldBody);

    $proposal = ContentProposal::factory()->pending()->create([
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'asked_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post(route('courses.content-proposals.reject', [$course, $proposal]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $proposal->refresh();
    $lesson->refresh();

    expect($proposal->status)->toBe(ContentProposal::STATUS_REJECTED)
        ->and($lesson->readableBody())->toContain($oldBody);
});

it('forbids a Facilitator from accepting a Content Proposal', function () {
    ['admin' => $admin, 'course' => $course, 'lesson' => $lesson] = authorLesson();
    $facilitator = User::factory()->learner()->create();
    Offering::factory()->for($course)->create(['facilitator_id' => $facilitator->id]);

    $proposal = ContentProposal::factory()->pending()->create([
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'asked_by' => $admin->id,
    ]);

    $this->actingAs($facilitator)
        ->post(route('courses.content-proposals.accept', [$course, $proposal]))
        ->assertForbidden();

    expect($proposal->fresh()->status)->toBe(ContentProposal::STATUS_PENDING)
        ->and($lesson->fresh()->readableBody())->toContain('Agen berbeda dari chatbot biasa.');
});

it('forbids a Learner from asking or accepting a Content Proposal', function () {
    ['course' => $course, 'lesson' => $lesson] = authorLesson();
    ['user' => $learner] = createEnrolledLearner($course);

    $this->actingAs($learner)
        ->post(route('courses.content-proposals.store', $course), [
            'lesson_id' => $lesson->id,
            'instruction' => 'Perjelas bedanya chatbot dan agen.',
        ])
        ->assertForbidden();

    $proposal = ContentProposal::factory()->pending()->create([
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
    ]);

    $this->actingAs($learner)
        ->post(route('courses.content-proposals.accept', [$course, $proposal]))
        ->assertForbidden();

    expect($proposal->fresh()->status)->toBe(ContentProposal::STATUS_PENDING);
});

it('does not accept a proposal from another Course', function () {
    ['admin' => $admin, 'course' => $course, 'lesson' => $lesson] = authorLesson();
    $other = Course::factory()->published()->public()->create(['user_id' => $admin->id]);

    $proposal = ContentProposal::factory()->pending()->create([
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'asked_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post(route('courses.content-proposals.accept', [$other, $proposal]))
        ->assertNotFound();

    expect($proposal->fresh()->status)->toBe(ContentProposal::STATUS_PENDING)
        ->and($lesson->fresh()->readableBody())->toContain('Agen berbeda dari chatbot biasa.');
});

it('refuses to ask on a Lesson that is not text', function () {
    ['admin' => $admin, 'course' => $course, 'section' => $section] = authorLesson();
    $document = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'content_type' => 'document',
        'title' => 'Lembar keputusan',
    ]);

    $this->actingAs($admin)
        ->post(route('courses.content-proposals.store', $course), [
            'lesson_id' => $document->id,
            'instruction' => 'Perjelas bedanya chatbot dan agen.',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Usulan konten hanya untuk Lesson teks.');

    expect(ContentProposal::query()->where('course_id', $course->id)->exists())->toBeFalse();
});
