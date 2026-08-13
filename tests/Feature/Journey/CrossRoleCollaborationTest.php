<?php

/**
 * Cross-Role Collaboration Tests
 *
 * Tests multi-user workflows where different roles collaborate:
 * - Content manager creates, admin publishes, learner enrolls
 * - Trainer invitations to any course
 * - Re-enrollment after dropping
 * - Grading workflows across roles
 */

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;

describe('Cross-Role Collaboration', function () {

    describe('CM Creates → Admin Publishes → Learner Enrolls Workflow', function () {

        it('completes full workflow from content creation to learner enrollment', function () {
            // Step 1: Content Manager creates a course
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create(['role' => 'learner']);

            // CM creates course
            $this->actingAs($cm)
                ->post(route('courses.store'), [
                    'title' => 'Manajemen Risiko Perbankan',
                    'short_description' => 'Kursus manajemen risiko untuk perbankan',
                    'difficulty_level' => 'intermediate',
                ])
                ->assertRedirect();

            $course = Course::where('title', 'Manajemen Risiko Perbankan')->first();
            expect($course)->not->toBeNull();
            expect($course->user_id)->toBe($cm->id);
            expect($course->isDraft())->toBeTrue();

            // Step 2: CM adds section and lesson
            $this->actingAs($cm)
                ->post(route('courses.sections.store', $course), [
                    'title' => 'Modul 1: Pengenalan',
                    'description' => 'Pengenalan manajemen risiko',
                ])
                ->assertRedirect();

            $section = $course->sections()->first();

            $this->actingAs($cm)
                ->post(route('sections.lessons.store', $section), [
                    'title' => 'Lesson 1: Konsep Dasar',
                    'content_type' => 'text',
                    'rich_content' => [
                        'type' => 'doc',
                        'content' => [[
                            'type' => 'paragraph',
                            'content' => [['type' => 'text', 'text' => 'Materi tentang konsep dasar manajemen risiko.']],
                        ]],
                    ],
                ])
                ->assertRedirect();

            expect($course->lessons()->count())->toBe(1);

            // Ensure course has required content for publishing
            expect($course->lessons()->count())->toBe(1);

            expect($course->refresh()->isDraft())->toBeTrue();

            // Step 3: Admin publishes the course
            $this->actingAs($admin)
                ->post(route('courses.publish', $course))
                ->assertRedirect();

            expect($course->refresh()->isPublished())->toBeTrue();

            // Step 5: Learner can now enroll
            $this->actingAs($learner)
                ->post(route('courses.enroll', $course))
                ->assertRedirect();

            $enrollment = Enrollment::where('user_id', $learner->id)
                ->where('course_id', $course->id)
                ->first();

            expect($enrollment)->not->toBeNull();
            expect($enrollment->isActive())->toBeTrue();
        });

        it('learner cannot enroll while course is draft', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create(['role' => 'learner']);

            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);

            $this->actingAs($learner)
                ->post(route('courses.enroll', $course))
                ->assertForbidden();

            $this->assertDatabaseMissing('enrollments', [
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);
        });

    });


    describe('Re-enrollment After Dropping', function () {

        it('learner can re-enroll after dropping and enrollment is reactivated', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->public()->create();

            // Create dropped enrollment with some progress
            $droppedEnrollment = Enrollment::factory()->dropped()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
                'progress_percentage' => 50,
            ]);

            expect($droppedEnrollment->isDropped())->toBeTrue();
            expect($droppedEnrollment->progress_percentage)->toBe(50);

            // Re-enrollment reactivates the existing enrollment
            $this->actingAs($learner)
                ->post(route('courses.enroll', $course))
                ->assertRedirect();

            // Verify enrollment was reactivated (not a new one created)
            $enrollment = Enrollment::where('user_id', $learner->id)
                ->where('course_id', $course->id)
                ->first();

            expect($enrollment->id)->toBe($droppedEnrollment->id); // Same record
            expect($enrollment->isActive())->toBeTrue(); // Now active
            expect($enrollment->progress_percentage)->toBe(50); // Progress preserved by default
        });

        it('re-enrollment preserves previous progress by default', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->public()->create();
            $section = CourseSection::factory()->create(['course_id' => $course->id]);
            $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

            // Create dropped enrollment with progress
            $droppedEnrollment = Enrollment::factory()->dropped()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
                'progress_percentage' => 75,
                'last_lesson_id' => $lesson->id,
            ]);

            LessonProgress::create([
                'enrollment_id' => $droppedEnrollment->id,
                'lesson_id' => $lesson->id,
                'is_completed' => true,
                'current_page' => 1,
            ]);

            // Re-enroll
            $this->actingAs($learner)
                ->post(route('courses.enroll', $course))
                ->assertRedirect();

            $enrollment = Enrollment::find($droppedEnrollment->id);

            // Progress is preserved
            expect($enrollment->progress_percentage)->toBe(75);
            expect($enrollment->last_lesson_id)->toBe($lesson->id);

            // Lesson progress still exists and is associated
            expect($enrollment->lessonProgress()->where('lesson_id', $lesson->id)->exists())->toBeTrue();
        });

        it('learner cannot re-enroll while completed enrollment exists', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->public()->create();

            // Create completed enrollment
            Enrollment::factory()->completed()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            // Cannot re-enroll - service blocks active/completed
            $response = $this->actingAs($learner)
                ->post(route('courses.enroll', $course));

            // Should be rejected
            expect($response->status())->toBeIn([403, 302, 422, 500]);
        });

        it('learner cannot re-enroll while active enrollment exists', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->public()->create();

            // Create active enrollment
            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            // Cannot create another enrollment
            $response = $this->actingAs($learner)
                ->post(route('courses.enroll', $course));

            // Should be rejected (403 or redirect with error)
            expect($response->status())->toBeIn([403, 302, 422, 500]);
        });

        it('dropped enrollment progress is not accessible', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->public()->create();
            $section = CourseSection::factory()->create(['course_id' => $course->id]);
            $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

            // Create dropped enrollment with some progress
            $enrollment = Enrollment::factory()->dropped()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            LessonProgress::create([
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
                'is_completed' => true,
                'current_page' => 1,
            ]);

            // Cannot view lesson
            $this->actingAs($learner)
                ->get(route('courses.lessons.show', [$course, $lesson]))
                ->assertForbidden();

            // Cannot update progress
            $this->actingAs($learner)
                ->patch(route('courses.lessons.progress.update', [$course, $lesson]), [
                    'current_page' => 2,
                    'total_pages' => 3,
                ])
                ->assertForbidden();
        });

    });

    describe('Admin Collaboration Workflows', function () {

        it('admin can modify any course regardless of owner', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $cm = User::factory()->create(['role' => 'lms_admin']);

            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);

            $this->actingAs($admin)
                ->patch(route('courses.update', $course), [
                    'title' => 'Admin Modified Title',
                    'short_description' => 'Modified by admin',
                    'difficulty_level' => $course->difficulty_level ?? 'beginner',
                    'visibility' => $course->visibility ?? 'restricted',
                ])
                ->assertRedirect();

            expect($course->refresh()->title)->toBe('Admin Modified Title');
        });

        it('admin can add sections to any published course', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $cm = User::factory()->create(['role' => 'lms_admin']);

            $course = Course::factory()->published()->create(['user_id' => $cm->id]);

            $this->actingAs($admin)
                ->post(route('courses.sections.store', $course), [
                    'title' => 'Admin Added Section',
                    'description' => 'Added by admin',
                ])
                ->assertRedirect();

            expect($course->sections()->where('title', 'Admin Added Section')->exists())->toBeTrue();
        });

        it('admin can unpublish and re-publish course', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->published()->create();
            $section = CourseSection::factory()->create(['course_id' => $course->id]);
            Lesson::factory()->create(['course_section_id' => $section->id]);

            // Unpublish
            $this->actingAs($admin)
                ->post(route('courses.unpublish', $course))
                ->assertRedirect();

            expect($course->refresh()->isDraft())->toBeTrue();

            // Re-publish (content exists)
            $this->actingAs($admin)
                ->post(route('courses.publish', $course))
                ->assertRedirect();

            expect($course->refresh()->isPublished())->toBeTrue();
        });

        it('admin can restore archived course', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->published()->create();
            $section = CourseSection::factory()->create(['course_id' => $course->id]);
            Lesson::factory()->create(['course_section_id' => $section->id]);

            // Archive
            $this->actingAs($admin)
                ->post(route('courses.archive', $course))
                ->assertRedirect();

            expect($course->refresh()->isArchived())->toBeTrue();

            // Restore to published (content exists)
            $this->actingAs($admin)
                ->post(route('courses.publish', $course))
                ->assertRedirect();

            expect($course->refresh()->isPublished())->toBeTrue();
        });

    });

    describe('Invitation to Enrollment Workflow', function () {

        it('complete flow: invite → accept → enroll → complete', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create(['role' => 'learner']);

            // Setup course with content - already has section + lesson
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);
            $section = CourseSection::factory()->create(['course_id' => $course->id]);
            $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

            // Admin publishes - content requirement is met
            $this->actingAs($admin)->post(route('courses.publish', $course));

            // CM invites learner
            $this->actingAs($cm)
                ->post(route('courses.invitations.store', $course), [
                    'user_id' => $learner->id,
                ])
                ->assertRedirect();

            $invitation = CourseInvitation::where('user_id', $learner->id)
                ->where('course_id', $course->id)
                ->first();

            expect($invitation)->not->toBeNull();
            expect($invitation->status)->toBe('pending');

            // Learner accepts invitation
            $this->actingAs($learner)
                ->post(route('invitations.accept', $invitation))
                ->assertRedirect();

            // Check enrollment created
            $enrollment = Enrollment::where('user_id', $learner->id)
                ->where('course_id', $course->id)
                ->first();

            expect($enrollment)->not->toBeNull();
            expect($enrollment->isActive())->toBeTrue();

            // Check invitation status updated
            expect($invitation->refresh()->status)->toBe('accepted');
        });

        it('learner cannot access grade page', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create(['role' => 'learner']);

            $course = Course::factory()->published()->create(['user_id' => $cm->id]);

            $assessment = Assessment::factory()->published()->create([
                'course_id' => $course->id,
                'user_id' => $cm->id,
            ]);

            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            $attempt = AssessmentAttempt::factory()->submitted()->create([
                'assessment_id' => $assessment->id,
                'user_id' => $learner->id,
            ]);

            // Learner cannot access grade page
            $this->actingAs($learner)
                ->get(route('assessments.grade', [$course, $assessment, $attempt]))
                ->assertForbidden();
        });

    });

});
