<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Database\Seeders\AgentAcademyCourseSeeder;
use Database\Seeders\AiEngineerCourseSeeder;
use Database\Seeders\FreeFlowDemoSeeder;

it('lists Insinyur Kecerdasan Artificial on the public catalog as a free Open Course', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiEngineerCourseSeeder::class);
    $this->seed(AgentAcademyCourseSeeder::class);

    $learner = User::query()->where('email', 'learner@enterlms.test')->first();
    $course = Course::query()->where('title', AiEngineerCourseSeeder::COURSE_TITLE)->first();

    expect($course)->not->toBeNull()
        ->and($course->code)->toBe(AiEngineerCourseSeeder::COURSE_CODE)
        ->and($course->visibility)->toBe('public')
        ->and($course->is_paid)->toBeFalse()
        ->and($course->difficulty_level)->toBe('advanced')
        ->and($course->isPublished())->toBeTrue()
        ->and($course->lessons()->count())->toBe(count(AiEngineerCourseSeeder::LESSON_TITLES));

    $this->actingAs($learner)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/Browse')
            ->has('courses.data', 2)
        )
        ->assertSee(AiEngineerCourseSeeder::COURSE_TITLE)
        ->assertSee(FreeFlowDemoSeeder::FREE_COURSE_TITLE)
        ->assertDontSee(AgentAcademyCourseSeeder::RESTRICTED_COURSE_TITLE);
});

it('lets a Learner self-enroll in Insinyur Kecerdasan Artificial', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiEngineerCourseSeeder::class);

    $learner = User::query()->where('email', 'learner@enterlms.test')->first();
    $course = Course::query()->where('title', AiEngineerCourseSeeder::COURSE_TITLE)->first();

    $this->actingAs($learner)
        ->post(route('courses.enroll', $course))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('enrollments', [
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    expect(Enrollment::query()->where('user_id', $learner->id)->count())->toBe(1);
});

it('keeps the AI Engineer preview Lesson readable without Enrollment', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiEngineerCourseSeeder::class);

    $learner = User::query()->where('email', 'learner@enterlms.test')->first();
    $course = Course::query()->where('title', AiEngineerCourseSeeder::COURSE_TITLE)->first();
    $preview = $course->lessons()->where('lessons.title', AiEngineerCourseSeeder::PREVIEW_LESSON_TITLE)->first();

    expect($preview?->is_free_preview)->toBeTrue();

    $this->actingAs($learner)
        ->get(route('courses.lessons.preview', [$course, $preview]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/LessonPreview')
            ->where('lesson.rich_content_html', fn ($html) => is_string($html)
                && $html !== ''
                && str_contains($html, '<svg')
                && str_contains($html, 'lesson-diagram'))
        );
});

it('does not grant Restricted OpenClaw by finishing Insinyur Kecerdasan Artificial', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiEngineerCourseSeeder::class);
    $this->seed(AgentAcademyCourseSeeder::class);

    $learner = User::query()->where('email', 'learner@enterlms.test')->first();
    $course = Course::query()->where('title', AiEngineerCourseSeeder::COURSE_TITLE)->first();
    $openClaw = Course::query()->where('title', AgentAcademyCourseSeeder::RESTRICTED_COURSE_TITLE)->first();

    Enrollment::factory()->completed()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
    ]);

    expect(Enrollment::query()
        ->where('user_id', $learner->id)
        ->where('course_id', $openClaw->id)
        ->exists())->toBeFalse();

    $this->actingAs($learner)
        ->get(route('courses.show', $openClaw))
        ->assertForbidden();
});

it('shows a rendered diagram on an enrolled AI Engineer Lesson', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiEngineerCourseSeeder::class);

    $learner = User::query()->where('email', 'learner@enterlms.test')->first();
    $course = Course::query()->where('title', AiEngineerCourseSeeder::COURSE_TITLE)->first();
    $lesson = $course->lessons()
        ->where('lessons.title', 'Memasang solusi AI ke layanan operasional')
        ->first();

    expect($lesson)->not->toBeNull();

    $this->actingAs($learner)
        ->post(route('courses.enroll', $course))
        ->assertRedirect();

    $this->actingAs($learner)
        ->get(route('courses.lessons.show', [$course, $lesson]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('lessons/Show')
            ->where('lesson.rich_content_html', fn ($html) => is_string($html)
                && $html !== ''
                && str_contains($html, '<svg')
                && str_contains($html, '<figure class="lesson-diagram">')
                && str_contains($html, 'mode bayangan'))
        );
});
