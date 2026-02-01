<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\LearningPath;
use App\Models\User;

describe('Admin Trash Dashboard', function () {
    describe('index', function () {
        it('shows trashed items to admin', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create();
            $course->delete();

            $this->actingAs($admin)
                ->get(route('admin.trash.index'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('admin/Trash')
                    ->has('groups')
                    ->has('summary')
                );
        });

        it('filters trashed items by type', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create();
            $course->delete();
            $assessment = Assessment::factory()->create();
            $assessment->delete();

            $this->actingAs($admin)
                ->get(route('admin.trash.index', ['type' => 'course']))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('admin/Trash')
                    ->has('groups', 1)
                    ->where('groups.0.type', 'course')
                );
        });

        it('shows empty state when no trashed items', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);

            $this->actingAs($admin)
                ->get(route('admin.trash.index'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('admin/Trash')
                    ->has('groups', 0)
                );
        });

        it('denies access to non-admin users', function () {
            $learner = User::factory()->create(['role' => 'learner']);

            $this->actingAs($learner)
                ->get(route('admin.trash.index'))
                ->assertForbidden();
        });
    });

    describe('restore', function () {
        it('allows admin to restore a trashed course', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create();
            $course->delete();

            $this->actingAs($admin)
                ->post(route('admin.trash.restore', ['type' => 'course', 'id' => $course->id]))
                ->assertRedirect();

            $this->assertNotSoftDeleted('courses', ['id' => $course->id]);
        });

        it('allows admin to restore a trashed assessment', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $assessment = Assessment::factory()->create();
            $assessment->delete();

            $this->actingAs($admin)
                ->post(route('admin.trash.restore', ['type' => 'assessment', 'id' => $assessment->id]))
                ->assertRedirect();

            $this->assertNotSoftDeleted('assessments', ['id' => $assessment->id]);
        });

        it('returns 404 for invalid type', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);

            $this->actingAs($admin)
                ->post(route('admin.trash.restore', ['type' => 'invalid', 'id' => 1]))
                ->assertNotFound();
        });

        it('returns 404 for non-trashed record', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create();

            $this->actingAs($admin)
                ->post(route('admin.trash.restore', ['type' => 'course', 'id' => $course->id]))
                ->assertNotFound();
        });

        it('denies restore to non-admin users', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);
            $course = Course::factory()->draft()->create();
            $course->delete();

            $this->actingAs($cm)
                ->post(route('admin.trash.restore', ['type' => 'course', 'id' => $course->id]))
                ->assertForbidden();
        });
    });

    describe('forceDelete', function () {
        it('allows admin to permanently delete a trashed course', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create();
            $course->delete();

            $this->actingAs($admin)
                ->delete(route('admin.trash.force-delete', ['type' => 'course', 'id' => $course->id]))
                ->assertRedirect();

            expect(Course::withTrashed()->where('id', $course->id)->exists())->toBeFalse();
        });

        it('allows admin to permanently delete a trashed learning path', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $lp = LearningPath::factory()->create();
            $lp->delete();

            $this->actingAs($admin)
                ->delete(route('admin.trash.force-delete', ['type' => 'learning_path', 'id' => $lp->id]))
                ->assertRedirect();

            expect(LearningPath::withTrashed()->where('id', $lp->id)->exists())->toBeFalse();
        });

        it('returns 404 for non-trashed record', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create();

            $this->actingAs($admin)
                ->delete(route('admin.trash.force-delete', ['type' => 'course', 'id' => $course->id]))
                ->assertNotFound();
        });

        it('denies force-delete to non-admin users', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);
            $course = Course::factory()->draft()->create();
            $course->delete();

            $this->actingAs($cm)
                ->delete(route('admin.trash.force-delete', ['type' => 'course', 'id' => $course->id]))
                ->assertForbidden();
        });
    });
});
