<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\User;
use Database\Seeders\AgentAcademyCourseSeeder;
use Database\Seeders\FreeFlowDemoSeeder;

describe('Agent academy catalog', function () {
    it('lists the open intro course and hides the restricted OpenClaw course', function () {
        $this->seed(FreeFlowDemoSeeder::class);
        $this->seed(AgentAcademyCourseSeeder::class);

        $learner = User::query()->where('email', 'learner@enterlms.test')->first();

        $this->actingAs($learner)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('courses/Browse')
                ->has('courses.data', 1)
                ->where('courses.data.0.title', FreeFlowDemoSeeder::FREE_COURSE_TITLE)
            );

        $openClaw = Course::query()->where('title', AgentAcademyCourseSeeder::RESTRICTED_COURSE_TITLE)->first();
        expect($openClaw)->not->toBeNull();
        expect($openClaw->visibility)->toBe('restricted');

        $this->actingAs($learner)
            ->get(route('courses.show', $openClaw))
            ->assertForbidden();
    });

    it('lets a public learner self-enroll in Pengenalan Agen AI', function () {
        $this->seed(FreeFlowDemoSeeder::class);

        $learner = User::query()->where('email', 'learner@enterlms.test')->first();
        $course = Course::query()->where('title', FreeFlowDemoSeeder::FREE_COURSE_TITLE)->first();

        $this->actingAs($learner)
            ->post(route('courses.enroll', $course))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);
    });

    it('does not list the operator path for public browse or self-enroll', function () {
        $this->seed(FreeFlowDemoSeeder::class);
        $this->seed(AgentAcademyCourseSeeder::class);

        $learner = User::query()->where('email', 'learner@enterlms.test')->first();
        $path = LearningPath::query()->where('title', AgentAcademyCourseSeeder::OPERATOR_PATH_TITLE)->first();

        expect($path)->not->toBeNull();
        expect($path->visibility)->toBe('restricted');
        expect($path->is_published)->toBeTrue();

        $this->actingAs($learner)
            ->get(route('learner.learning-paths.browse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('learningPaths.data', 0)
            );

        $this->actingAs($learner)
            ->post(route('learner.learning-paths.enroll', $path))
            ->assertRedirect(route('learner.learning-paths.browse'))
            ->assertSessionHas('error');

        expect(LearningPathEnrollment::query()
            ->where('user_id', $learner->id)
            ->where('learning_path_id', $path->id)
            ->exists())->toBeFalse();
    });

    it('enrolls the seeded operator on the path with only the intro course unlocked', function () {
        $this->seed(FreeFlowDemoSeeder::class);
        $this->seed(AgentAcademyCourseSeeder::class);

        $operator = User::query()->where('email', AgentAcademyCourseSeeder::OPERATOR_EMAIL)->first();
        $path = LearningPath::query()->where('title', AgentAcademyCourseSeeder::OPERATOR_PATH_TITLE)->first();
        $intro = Course::query()->where('title', FreeFlowDemoSeeder::FREE_COURSE_TITLE)->first();
        $openClaw = Course::query()->where('title', AgentAcademyCourseSeeder::RESTRICTED_COURSE_TITLE)->first();

        $pathEnrollment = LearningPathEnrollment::query()
            ->where('user_id', $operator->id)
            ->where('learning_path_id', $path->id)
            ->first();

        expect($pathEnrollment)->not->toBeNull();
        expect($pathEnrollment->isActive())->toBeTrue();

        expect(Enrollment::query()
            ->where('user_id', $operator->id)
            ->where('course_id', $intro->id)
            ->exists())->toBeTrue();

        expect(Enrollment::query()
            ->where('user_id', $operator->id)
            ->where('course_id', $openClaw->id)
            ->exists())->toBeFalse();

        $this->actingAs($operator)
            ->get(route('learner.learning-paths.show', $path))
            ->assertOk();
    });
});
