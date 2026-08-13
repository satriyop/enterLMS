<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;

/**
 * Role Escalation Prevention Tests
 *
 * These tests verify that users cannot access functionality
 * beyond their role permissions. This is critical for security.
 *
 * Role Hierarchy:
 * - Learner: Can browse, enroll, learn
 * - Content Manager: + Create/edit own courses
 * - Trainer: + Invite to any course
 * - LMS Admin: Full access
 */
describe('Role Escalation Prevention', function () {

    describe('Learner Cannot Access Management', function () {

        it('learner cannot access course creation page', function () {
            $learner = User::factory()->create(['role' => 'learner']);

            $this->actingAs($learner)
                ->get(route('courses.create'))
                ->assertForbidden();
        });

        it('learner cannot create a course via POST', function () {
            $learner = User::factory()->create(['role' => 'learner']);

            $this->actingAs($learner)
                ->post(route('courses.store'), [
                    'title' => 'Hacked Course',
                    'description' => 'This should not work',
                ])
                ->assertForbidden();

            $this->assertDatabaseMissing('courses', ['title' => 'Hacked Course']);
        });

        it('learner cannot publish a course', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->draft()->create();

            $this->actingAs($learner)
                ->post(route('courses.publish', $course))
                ->assertForbidden();

            expect($course->refresh()->isDraft())->toBeTrue();
        });

        it('learner cannot access instructor dashboard', function () {
            $learner = User::factory()->create(['role' => 'learner']);

            $response = $this->actingAs($learner)
                ->get(route('dashboard'));

            // Should redirect to learner dashboard or show forbidden
            $response->assertRedirect(route('learner.dashboard'));
        });

        it('learner cannot create an assessment', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->create();

            $this->actingAs($learner)
                ->post(route('assessments.store', $course), [
                    'title' => 'Hacked Assessment',
                ])
                ->assertForbidden();
        });

        it('learner cannot invite users to a course', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $otherLearner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->create();

            $this->actingAs($learner)
                ->post(route('courses.invitations.store', $course), [
                    'user_id' => $otherLearner->id,
                ])
                ->assertForbidden();
        });

        it('learner cannot edit a course', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->create();

            $this->actingAs($learner)
                ->get(route('courses.edit', $course))
                ->assertForbidden();
        });

        it('learner cannot update a course', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->draft()->create(['title' => 'Original Title']);

            $this->actingAs($learner)
                ->patch(route('courses.update', $course), [
                    'title' => 'Hacked Title',
                ])
                ->assertForbidden();

            expect($course->refresh()->title)->toBe('Original Title');
        });

        it('learner cannot delete a course', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->create();

            $this->actingAs($learner)
                ->delete(route('courses.destroy', $course))
                ->assertForbidden();

            $this->assertNotSoftDeleted($course);
        });

        it('learner cannot create a section', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->draft()->create();

            $this->actingAs($learner)
                ->post(route('courses.sections.store', $course), [
                    'title' => 'Hacked Section',
                ])
                ->assertForbidden();
        });

    });



    describe('Guest Access Prevention', function () {

        it('guest cannot access dashboard', function () {
            $this->get(route('dashboard'))
                ->assertRedirect(route('login'));
        });

        it('guest cannot access learner dashboard', function () {
            $this->get(route('learner.dashboard'))
                ->assertRedirect(route('login'));
        });

        it('guest cannot create course', function () {
            $this->get(route('courses.create'))
                ->assertRedirect(route('login'));
        });

        it('guest cannot enroll in course', function () {
            $course = Course::factory()->published()->create();

            $this->post(route('courses.enroll', $course))
                ->assertRedirect(route('login'));
        });

        it('guest cannot view lesson content', function () {
            $course = createPublishedCourseWithContent();
            $lesson = $course->lessons->first();

            $this->get(route('courses.lessons.show', [$course, $lesson]))
                ->assertRedirect(route('login'));
        });

        it('guest cannot start assessment', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->published()->create(['user_id' => $cm->id]);
            $assessment = Assessment::factory()->published()->create([
                'course_id' => $course->id,
                'user_id' => $cm->id,
            ]);

            $this->post(route('assessments.start', [$course, $assessment]))
                ->assertRedirect(route('login'));
        });

    });

    describe('Admin Can Perform Admin Actions', function () {

        it('admin can publish any course', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);
            $section = \App\Models\CourseSection::factory()->create(['course_id' => $course->id]);
            \App\Models\Lesson::factory()->create(['course_section_id' => $section->id]);

            $this->actingAs($admin)
                ->post(route('courses.publish', $course))
                ->assertRedirect();

            expect($course->refresh()->isPublished())->toBeTrue();
        });

        it('admin can unpublish any course', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->published()->create();

            $this->actingAs($admin)
                ->post(route('courses.unpublish', $course))
                ->assertRedirect();

            expect($course->refresh()->isDraft())->toBeTrue();
        });

        it('admin can archive any course', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->published()->create();

            $this->actingAs($admin)
                ->post(route('courses.archive', $course))
                ->assertRedirect();

            expect($course->refresh()->isArchived())->toBeTrue();
        });

        it('admin can set course visibility', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['visibility' => 'public']);

            $this->actingAs($admin)
                ->patch(route('courses.visibility', $course), ['visibility' => 'restricted'])
                ->assertRedirect();

            expect($course->refresh()->visibility)->toBe('restricted');
        });

        it('admin can edit any course', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);

            $this->actingAs($admin)
                ->get(route('courses.edit', $course))
                ->assertOk();
        });

        it('admin can delete any course', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);

            $this->actingAs($admin)
                ->delete(route('courses.destroy', $course))
                ->assertRedirect();

            $this->assertSoftDeleted('courses', ['id' => $course->id]);
        });

    });

});
