<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Database\Seeders\AgentAcademyCourseSeeder;
use Database\Seeders\AiForCampusCourseSeeder;
use Database\Seeders\AiForCorporateCourseSeeder;
use Database\Seeders\FreeFlowDemoSeeder;

it('lists AI untuk Korporat on the public catalog as a free Open Course', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiForCampusCourseSeeder::class);
    $this->seed(AiForCorporateCourseSeeder::class);
    $this->seed(AgentAcademyCourseSeeder::class);

    $learner = User::query()->where('email', 'learner@enterlms.test')->first();
    $corporate = Course::query()->where('title', AiForCorporateCourseSeeder::COURSE_TITLE)->first();

    expect($corporate)->not->toBeNull()
        ->and($corporate->visibility)->toBe('public')
        ->and($corporate->is_paid)->toBeFalse()
        ->and($corporate->isPublished())->toBeTrue()
        ->and($corporate->lessons()->count())->toBe(count(AiForCorporateCourseSeeder::LESSON_TITLES));

    $this->actingAs($learner)
        ->get(route('courses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('courses/Browse')
            ->has('courses.data', 3)
        )
        ->assertSee(AiForCorporateCourseSeeder::COURSE_TITLE)
        ->assertSee(AiForCampusCourseSeeder::COURSE_TITLE)
        ->assertSee(FreeFlowDemoSeeder::FREE_COURSE_TITLE)
        ->assertDontSee(AgentAcademyCourseSeeder::RESTRICTED_COURSE_TITLE);
});

it('lets a Learner self-enroll in AI untuk Korporat', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiForCorporateCourseSeeder::class);

    $learner = User::query()->where('email', 'learner@enterlms.test')->first();
    $course = Course::query()->where('title', AiForCorporateCourseSeeder::COURSE_TITLE)->first();

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

it('keeps a preview Lesson without requiring Enrollment', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiForCorporateCourseSeeder::class);

    $learner = User::query()->where('email', 'learner@enterlms.test')->first();
    $course = Course::query()->where('title', AiForCorporateCourseSeeder::COURSE_TITLE)->first();
    $preview = $course->lessons()->where('lessons.title', 'Untuk siapa kursus ini')->first();

    expect($preview?->is_free_preview)->toBeTrue();

    $this->actingAs($learner)
        ->get(route('courses.lessons.preview', [$course, $preview]))
        ->assertOk();
});

it('does not grant Restricted OpenClaw by finishing AI untuk Korporat', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiForCorporateCourseSeeder::class);
    $this->seed(AgentAcademyCourseSeeder::class);

    $learner = User::query()->where('email', 'learner@enterlms.test')->first();
    $corporate = Course::query()->where('title', AiForCorporateCourseSeeder::COURSE_TITLE)->first();
    $openClaw = Course::query()->where('title', AgentAcademyCourseSeeder::RESTRICTED_COURSE_TITLE)->first();

    Enrollment::factory()->completed()->create([
        'user_id' => $learner->id,
        'course_id' => $corporate->id,
    ]);

    expect(Enrollment::query()
        ->where('user_id', $learner->id)
        ->where('course_id', $openClaw->id)
        ->exists())->toBeFalse();

    $this->actingAs($learner)
        ->get(route('courses.show', $openClaw))
        ->assertForbidden();
});
