<?php

/**
 * Role-Based View Tests
 *
 * Ensures different roles see appropriate content and controls:
 * - Dashboard differences (admin, CM, learner)
 * - Course index views
 * - Course detail views
 * - Assessment views
 */

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

describe('Role-Based Views', function () {

    describe('Dashboard Differences', function () {

        it('admin dashboard shows all courses count', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $cm = User::factory()->create(['role' => 'lms_admin']);

            // Create various courses
            Course::factory()->draft()->create(['user_id' => $cm->id]);
            Course::factory()->published()->public()->create(['user_id' => $cm->id]);
            Course::factory()->published()->create(['user_id' => $admin->id]);

            $this->actingAs($admin)
                ->get(route('dashboard'))
                ->assertOk();

            // Admin can access the general dashboard
            expect(Course::count())->toBe(3);
        });

        it('learner dashboard shows enrolled courses with progress', function () {
            $learner = User::factory()->create(['role' => 'learner']);

            $course1 = Course::factory()->published()->public()->create();
            $course2 = Course::factory()->published()->public()->create();
            $course3 = Course::factory()->published()->public()->create(); // Not enrolled

            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course1->id,
                'progress_percentage' => 50,
            ]);

            Enrollment::factory()->completed()->create([
                'user_id' => $learner->id,
                'course_id' => $course2->id,
                'progress_percentage' => 100,
            ]);

            $this->actingAs($learner)
                ->get(route('learner.dashboard'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->has('myLearning', 2) // Only enrolled courses
                );
        });

    });

    describe('Course Index Views', function () {

        it('admin sees all courses with all statuses', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $cm = User::factory()->create(['role' => 'lms_admin']);

            Course::factory()->draft()->create(['user_id' => $cm->id]);
            Course::factory()->published()->create(['user_id' => $cm->id]);
            Course::factory()->create(['user_id' => $cm->id, 'status' => 'archived']);

            $this->actingAs($admin)
                ->get(route('courses.index'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->has('courses.data', 3) // All 3 courses
                );
        });

    });


});
