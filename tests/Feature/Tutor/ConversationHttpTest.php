<?php

use App\Domain\Tutor\Services\TutorRuntime;
use App\Models\Conversation;
use App\Models\ConversationTurn;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

function tutorLesson(string $title = 'Apa itu agen', string $body = 'Agen berbeda dari chatbot biasa.'): array
{
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => $title,
        'description' => $body,
        'is_free_preview' => false,
        'rich_content' => [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => $body]],
                ],
            ],
        ],
    ]);

    return compact('course', 'section', 'lesson');
}

function fakeTutorReply(string $reply = 'Agen berbeda dari chatbot.'): void
{
    test()->mock(TutorRuntime::class, function ($mock) use ($reply) {
        $mock->shouldReceive('completeTurn')->andReturn($reply);
    });
}

it('does not send the Hermes API key to the browser', function () {
    config()->set('tutor.runtime_api_key', 'secret-hermes-key-do-not-leak');
    config()->set('tutor.runtime_secret', 'secret-hermes-key-do-not-leak');

    fakeTutorReply();

    ['course' => $course, 'lesson' => $lesson] = tutorLesson();
    ['user' => $user] = createEnrolledLearner($course);

    $this->actingAs($user)
        ->get(route('courses.lessons.show', [$course, $lesson]))
        ->assertOk()
        ->assertDontSee('secret-hermes-key-do-not-leak');

    $this->actingAs($user)
        ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Apa bedanya agen dengan chatbot?',
        ])
        ->assertRedirect(route('courses.lessons.show', [$course, $lesson]));

    $this->actingAs($user)
        ->get(route('courses.lessons.show', [$course, $lesson]))
        ->assertOk()
        ->assertDontSee('secret-hermes-key-do-not-leak');
});

describe('Learner talks on a Lesson', function () {
    it('lets an enrolled learner post a turn, reload the Conversation, and does not complete the Lesson', function () {
        fakeTutorReply('Agen berbeda dari chatbot.');

        ['course' => $course, 'lesson' => $lesson] = tutorLesson();
        ['user' => $user, 'enrollment' => $enrollment] = createEnrolledLearner($course);

        $this->actingAs($user)
            ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
                'message' => 'Apa bedanya agen dengan chatbot?',
            ])
            ->assertRedirect(route('courses.lessons.show', [$course, $lesson]));

        $conversation = Conversation::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        expect($conversation)->not->toBeNull();
        expect($conversation->turns)->toHaveCount(2)
            ->and($conversation->turns[0]->role)->toBe('learner')
            ->and($conversation->turns[0]->body)->toBe('Apa bedanya agen dengan chatbot?')
            ->and($conversation->turns[1]->role)->toBe('tutor')
            ->and($conversation->turns[1]->body)->toBe('Agen berbeda dari chatbot.');

        $enrollment->refresh();
        expect($enrollment->progress_percentage)->toBe(0);

        expect(LessonProgress::query()->where('enrollment_id', $enrollment->id)->where('is_completed', true)->count())->toBe(0);

        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('lessons/Show')
                ->has('conversation.turns', 2)
                ->where('conversation.turns.0.body', 'Apa bedanya agen dengan chatbot?')
                ->where('conversation.turns.1.body', 'Agen berbeda dari chatbot.')
                ->where('conversation.can_post', true)
            );
    });
});

describe('Conversation follows Enrollment', function () {
    it('does not offer a Tutor on preview without Enrollment', function () {
        ['course' => $course, 'lesson' => $lesson] = tutorLesson();
        $lesson->update(['is_free_preview' => true]);
        $user = User::factory()->create(['role' => 'learner']);

        $this->actingAs($user)
            ->get(route('courses.lessons.preview', [$course, $lesson]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('courses/LessonPreview')
                ->missing('conversation')
            );

        $this->actingAs($user)
            ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
                'message' => 'Halo',
            ])
            ->assertForbidden();
    });

    it('lets a dropped learner read but not add turns', function () {
        fakeTutorReply();

        ['course' => $course, 'lesson' => $lesson] = tutorLesson();
        ['user' => $user, 'enrollment' => $enrollment] = createEnrolledLearner($course);

        $this->actingAs($user)->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Apa bedanya agen dengan chatbot?',
        ])->assertRedirect();

        $enrollment->drop();

        $this->actingAs($user)
            ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
                'message' => 'Lagi',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('courses.lessons.conversation.show', [$course, $lesson]))
            ->assertOk()
            ->assertJsonPath('turns.0.body', 'Apa bedanya agen dengan chatbot?')
            ->assertJsonPath('can_post', false);
    });

    it('lets a completed enrollment talk without uncompleting', function () {
        fakeTutorReply('Masih bisa bertanya.');

        ['course' => $course, 'lesson' => $lesson] = tutorLesson();
        ['user' => $user, 'enrollment' => $enrollment] = createEnrolledLearner($course);
        $enrollment->update(['status' => 'completed', 'progress_percentage' => 100, 'completed_at' => now()]);

        $this->actingAs($user)
            ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
                'message' => 'Apa bedanya agen dengan chatbot?',
            ])
            ->assertRedirect();

        $enrollment->refresh();
        expect($enrollment->isCompleted())->toBeTrue()
            ->and($enrollment->progress_percentage)->toBe(100);
    });

    it('keeps the Conversation when reactivating with progress and starts empty without', function () {
        fakeTutorReply();

        ['course' => $course, 'lesson' => $lesson] = tutorLesson();
        ['user' => $user, 'enrollment' => $enrollment] = createEnrolledLearner($course);

        $this->actingAs($user)->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Apa bedanya agen dengan chatbot?',
        ]);

        $enrollment->drop();
        $enrollment->reactivate(preserveProgress: true);

        expect(Conversation::query()->where('enrollment_id', $enrollment->id)->where('lesson_id', $lesson->id)->first()->turns)->toHaveCount(2);

        $enrollment->drop();
        $enrollment->reactivate(preserveProgress: false);

        $fresh = Conversation::query()->where('enrollment_id', $enrollment->id)->where('lesson_id', $lesson->id)->first();
        expect($fresh)->toBeNull();
    });

    it('forbids another learner from the Conversation', function () {
        fakeTutorReply();

        ['course' => $course, 'lesson' => $lesson] = tutorLesson();
        ['user' => $user] = createEnrolledLearner($course);

        $this->actingAs($user)->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Apa bedanya agen dengan chatbot?',
        ]);

        $stranger = User::factory()->create(['role' => 'learner']);

        $this->actingAs($stranger)
            ->get(route('courses.lessons.conversation.show', [$course, $lesson]))
            ->assertForbidden();

        Enrollment::factory()->active()->create([
            'user_id' => $stranger->id,
            'course_id' => $course->id,
        ]);

        $this->actingAs($stranger)
            ->get(route('courses.lessons.conversation.show', [$course, $lesson]))
            ->assertNotFound();

        $this->actingAs($stranger)
            ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
                'message' => 'Mencuri percakapan',
            ]);

        $ownerBodies = Conversation::query()
            ->where('enrollment_id', $user->enrollments()->first()->id)
            ->first()
            ->turns
            ->pluck('body');

        expect($ownerBodies->contains('Mencuri percakapan'))->toBeFalse();
    });

    it('lets LMS Admin read a Conversation and talk only if enrolled', function () {
        fakeTutorReply();

        ['course' => $course, 'lesson' => $lesson] = tutorLesson();
        ['user' => $user, 'enrollment' => $enrollment] = createEnrolledLearner($course);

        $this->actingAs($user)->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Apa bedanya agen dengan chatbot?',
        ]);

        $admin = User::factory()->create(['role' => 'lms_admin']);

        $this->actingAs($admin)
            ->get(route('courses.lessons.conversation.show', [$course, $lesson]).'?enrollment_id='.$enrollment->id)
            ->assertOk()
            ->assertJsonPath('turns.0.body', 'Apa bedanya agen dengan chatbot?');

        $this->actingAs($admin)
            ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
                'message' => 'Halo dari admin',
            ])
            ->assertForbidden();
    });

    it('deletes the Conversation when the Lesson is deleted', function () {
        fakeTutorReply();

        ['course' => $course, 'lesson' => $lesson] = tutorLesson();
        ['user' => $user, 'enrollment' => $enrollment] = createEnrolledLearner($course);

        $this->actingAs($user)->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Apa bedanya agen dengan chatbot?',
        ]);

        $lesson->delete();

        expect(Conversation::query()->where('enrollment_id', $enrollment->id)->count())->toBe(0);
        expect(ConversationTurn::query()->count())->toBe(0);
    });
});

describe('Tutor stays in the Lesson', function () {
    it('does not dump a later Lesson body', function () {
        fakeTutorReply('Itu di pelajaran berikutnya dalam Course ini.');

        ['course' => $course, 'section' => $section, 'lesson' => $lesson] = tutorLesson('Apa itu agen', 'Agen berbeda dari chatbot.');
        $later = Lesson::factory()->text()->create([
            'course_section_id' => $section->id,
            'title' => 'Kill switch',
            'description' => 'RAHASIA_LEMBAGA_KEMUDIAN',
            'rich_content' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => 'RAHASIA_LEMBAGA_KEMUDIAN']],
                ]],
            ],
        ]);

        ['user' => $user] = createEnrolledLearner($course);

        $this->actingAs($user)->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Jelaskan kill switch secara lengkap',
        ])->assertRedirect();

        $tutorBody = ConversationTurn::query()->where('role', 'tutor')->value('body');
        expect($tutorBody)->not->toContain('RAHASIA_LEMBAGA_KEMUDIAN')
            ->and($later->description)->toBe('RAHASIA_LEMBAGA_KEMUDIAN');
    });

    it('refuses operating a live OpenClaw and does not open a console', function () {
        fakeTutorReply('Praktik mengoperasikan agen hidup bukan di academy ini. Lesson ini bukan konsol runtime.');

        ['course' => $course, 'lesson' => $lesson] = tutorLesson();
        ['user' => $user] = createEnrolledLearner($course);

        $this->actingAs($user)->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Buka konsol OpenClaw dan operasikan runtime hidup',
        ])->assertRedirect();

        $tutorTurn = ConversationTurn::query()->where('role', 'tutor')->first();
        expect($tutorTurn->body)->toContain('bukan di academy ini')
            ->and($tutorTurn->body)->not->toContain('https://');
    });

    it('saves no turns when the runtime fails and the Lesson still opens', function () {
        test()->mock(TutorRuntime::class, function ($mock) {
            $mock->shouldReceive('completeTurn')->andThrow(new RuntimeException('down'));
        });

        ['course' => $course, 'lesson' => $lesson] = tutorLesson();
        ['user' => $user] = createEnrolledLearner($course);

        $this->actingAs($user)
            ->from(route('courses.lessons.show', [$course, $lesson]))
            ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
                'message' => 'Apa bedanya agen dengan chatbot?',
            ])
            ->assertRedirect(route('courses.lessons.show', [$course, $lesson]))
            ->assertSessionHasErrors(['message' => 'Tutor sedang tidak dapat menjawab. Silakan coba lagi.']);

        expect(ConversationTurn::query()->count())->toBe(0);

        $this->actingAs($user)
            ->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertOk();
    });

    it('uses current Lesson text for new turns after an edit', function () {
        fakeTutorReply('Berdasarkan Lesson ini: KATAUNIKBARU');

        ['course' => $course, 'lesson' => $lesson] = tutorLesson('Apa itu agen', 'Teks lama tanpa kata unik.');
        ['user' => $user] = createEnrolledLearner($course);

        $this->actingAs($user)->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Apa bedanya agen dengan chatbot?',
        ]);

        $lesson->update(['description' => 'Teks baru memuat KATAUNIKBARU tentang agen.']);

        $this->actingAs($user)->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'Jelaskan KATAUNIKBARU',
        ]);

        $last = ConversationTurn::query()->where('role', 'tutor')->latest('id')->first();
        expect($last->body)->toContain('KATAUNIKBARU');
    });

    it('replies in English when the Learner writes English', function () {
        fakeTutorReply('Based on this Lesson: an agent is different from a chatbot.');

        ['course' => $course, 'lesson' => $lesson] = tutorLesson('Apa itu agen', 'An agent is different from a chatbot.');
        ['user' => $user] = createEnrolledLearner($course);

        $this->actingAs($user)->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
            'message' => 'What is an agent compared to a chatbot?',
        ]);

        $tutorTurn = ConversationTurn::query()->where('role', 'tutor')->first();
        expect($tutorTurn->body)->toStartWith('Based on this Lesson:');
    });
});

describe('Overlay persist is grounded or nothing', function () {
    beforeEach(function () {
        config()->set('tutor.runtime_url', 'http://127.0.0.1:8642');
        config()->set('tutor.runtime_api_key', 'secret-test');
        config()->set('tutor.hermes_binary', '');
        config()->set('tutor.model', 'hermes');
    });

    it('persists Learner then Tutor only after Laravel reads this Lesson body for that Learner', function () {
        ['course' => $course, 'lesson' => $lesson] = tutorLesson('Apa itu agen', 'Agen berbeda dari chatbot biasa.');
        ['user' => $user, 'enrollment' => $enrollment] = createEnrolledLearner($course);

        Http::fake([
            'http://127.0.0.1:8642/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Agen berbeda dari chatbot biasa.']]],
            ], 200),
        ]);

        $this->actingAs($user)
            ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
                'message' => 'Apa bedanya agen dengan chatbot?',
            ])
            ->assertRedirect(route('courses.lessons.show', [$course, $lesson]));

        $conversation = Conversation::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        expect($conversation)->not->toBeNull()
            ->and($conversation->turns)->toHaveCount(2)
            ->and($conversation->turns[0]->role)->toBe('learner')
            ->and($conversation->turns[0]->body)->toBe('Apa bedanya agen dengan chatbot?')
            ->and($conversation->turns[1]->role)->toBe('tutor')
            ->and($conversation->turns[1]->body)->toBe('Agen berbeda dari chatbot biasa.');

        expect($enrollment->fresh()->progress_percentage)->toBe(0);
        expect(LessonProgress::query()->where('enrollment_id', $enrollment->id)->where('is_completed', true)->count())->toBe(0);

        Http::assertSent(function ($request) use ($user, $course, $lesson) {
            $system = (string) ($request['messages'][0]['content'] ?? '');

            return $request->url() === 'http://127.0.0.1:8642/v1/chat/completions'
                && str_contains($system, 'user_id: '.$user->id)
                && str_contains($system, 'course_id: '.$course->id)
                && str_contains($system, 'lesson_id: '.$lesson->id)
                && str_contains($system, 'body_text:')
                && str_contains($system, 'Agen berbeda dari chatbot biasa.');
        });
    });

    it('persists no turns when the Lesson body cannot be read', function () {
        $course = Course::factory()->published()->public()->create();
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'course_section_id' => $section->id,
            'title' => 'Lembar glosarium agen',
            'content_type' => 'document',
            'is_free_preview' => false,
        ]);
        ['user' => $user] = createEnrolledLearner($course);

        Http::fake([
            'http://127.0.0.1:8642/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'Jawaban tanpa tubuh pelajaran.']]],
            ], 200),
        ]);

        $this->actingAs($user)
            ->from(route('courses.lessons.show', [$course, $lesson]))
            ->post(route('courses.lessons.conversation.turns.store', [$course, $lesson]), [
                'message' => 'Apa bedanya agen dengan chatbot?',
            ])
            ->assertRedirect(route('courses.lessons.show', [$course, $lesson]))
            ->assertSessionHasErrors(['message' => 'Tutor sedang tidak dapat menjawab. Silakan coba lagi.']);

        expect(ConversationTurn::query()->count())->toBe(0);
        Http::assertNothingSent();
    });
});
