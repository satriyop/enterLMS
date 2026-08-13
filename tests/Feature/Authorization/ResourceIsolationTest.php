<?php

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CourseRating;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;

/**
 * Resource Isolation Tests
 *
 * These tests verify that users cannot access or modify resources
 * that belong to other users. Data isolation is critical for security
 * and privacy.
 */
describe('Resource Isolation', function () {




    describe('Enrollment Isolation', function () {

        it('learner cannot view another users enrollment progress via API', function () {
            $course = createPublishedCourseWithContent();

            $learner1 = User::factory()->create(['role' => 'learner']);
            $learner2 = User::factory()->create(['role' => 'learner']);

            Enrollment::factory()->active()->create([
                'user_id' => $learner1->id,
                'course_id' => $course->id,
                'progress_percentage' => 50,
            ]);

            Enrollment::factory()->active()->create([
                'user_id' => $learner2->id,
                'course_id' => $course->id,
                'progress_percentage' => 0,
            ]);

            // Learner 2's dashboard should only show their own progress
            $this->actingAs($learner2)
                ->get(route('learner.dashboard'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->has('myLearning', 1)
                    ->where('myLearning.0.progress_percentage', 0)
                );
        });

        it('learner cannot drop another users enrollment', function () {
            $course = createPublishedCourseWithContent();

            $learner1 = User::factory()->create(['role' => 'learner']);
            $learner2 = User::factory()->create(['role' => 'learner']);

            Enrollment::factory()->active()->create([
                'user_id' => $learner1->id,
                'course_id' => $course->id,
            ]);

            // Learner 2 has no enrollment in this course
            // Attempting to unenroll should return 404 (not found for this user)
            $this->actingAs($learner2)
                ->delete(route('courses.unenroll', $course))
                ->assertNotFound();
        });

    });

    describe('Assessment Attempt Isolation', function () {

        it('learner cannot view another users attempt', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->published()->create(['user_id' => $cm->id]);
            $assessment = Assessment::factory()->published()->create([
                'course_id' => $course->id,
                'user_id' => $cm->id,
            ]);

            $learner1 = User::factory()->create(['role' => 'learner']);
            $learner2 = User::factory()->create(['role' => 'learner']);

            Enrollment::factory()->active()->create([
                'user_id' => $learner1->id,
                'course_id' => $course->id,
            ]);
            Enrollment::factory()->active()->create([
                'user_id' => $learner2->id,
                'course_id' => $course->id,
            ]);

            $attempt = AssessmentAttempt::factory()->submitted()->create([
                'assessment_id' => $assessment->id,
                'user_id' => $learner1->id,
            ]);

            $this->actingAs($learner2)
                ->get(route('assessments.attempt', [$course, $assessment, $attempt]))
                ->assertForbidden();
        });

        it('learner cannot submit answers to another users attempt', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->published()->create(['user_id' => $cm->id]);
            $assessment = Assessment::factory()->published()->create([
                'course_id' => $course->id,
                'user_id' => $cm->id,
            ]);

            $question = Question::factory()->create([
                'assessment_id' => $assessment->id,
                'question_type' => 'true_false',
            ]);

            $learner1 = User::factory()->create(['role' => 'learner']);
            $learner2 = User::factory()->create(['role' => 'learner']);

            Enrollment::factory()->active()->create([
                'user_id' => $learner1->id,
                'course_id' => $course->id,
            ]);
            Enrollment::factory()->active()->create([
                'user_id' => $learner2->id,
                'course_id' => $course->id,
            ]);

            $attempt = AssessmentAttempt::factory()->inProgress()->create([
                'assessment_id' => $assessment->id,
                'user_id' => $learner1->id,
            ]);

            $this->actingAs($learner2)
                ->post(route('assessments.attempt.submit', [$course, $assessment, $attempt]), [
                    'answers' => [
                        ['question_id' => $question->id, 'answer_text' => 'true'],
                    ],
                ])
                ->assertForbidden();
        });

    });

    describe('Rating Isolation', function () {

        it('learner cannot update another users rating', function () {
            $course = createPublishedCourseWithContent();

            $learner1 = User::factory()->create(['role' => 'learner']);
            $learner2 = User::factory()->create(['role' => 'learner']);

            Enrollment::factory()->active()->create([
                'user_id' => $learner1->id,
                'course_id' => $course->id,
            ]);
            Enrollment::factory()->active()->create([
                'user_id' => $learner2->id,
                'course_id' => $course->id,
            ]);

            $rating = CourseRating::factory()->create([
                'user_id' => $learner1->id,
                'course_id' => $course->id,
                'rating' => 5,
            ]);

            $this->actingAs($learner2)
                ->patch(route('courses.ratings.update', [$course, $rating]), ['rating' => 1])
                ->assertForbidden();

            expect($rating->refresh()->rating)->toBe(5);
        });

        it('learner cannot delete another users rating', function () {
            $course = createPublishedCourseWithContent();

            $learner1 = User::factory()->create(['role' => 'learner']);
            $learner2 = User::factory()->create(['role' => 'learner']);

            Enrollment::factory()->active()->create([
                'user_id' => $learner1->id,
                'course_id' => $course->id,
            ]);
            Enrollment::factory()->active()->create([
                'user_id' => $learner2->id,
                'course_id' => $course->id,
            ]);

            $rating = CourseRating::factory()->create([
                'user_id' => $learner1->id,
                'course_id' => $course->id,
            ]);

            $this->actingAs($learner2)
                ->delete(route('courses.ratings.destroy', [$course, $rating]))
                ->assertForbidden();

            $this->assertDatabaseHas('course_ratings', ['id' => $rating->id]);
        });

        it('admin can delete any users rating', function () {
            $course = createPublishedCourseWithContent();
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create(['role' => 'learner']);

            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            $rating = CourseRating::factory()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            $this->actingAs($admin)
                ->delete(route('courses.ratings.destroy', [$course, $rating]))
                ->assertRedirect();

            $this->assertSoftDeleted('course_ratings', ['id' => $rating->id]);
        });

    });

    describe('Cross-Resource Validation', function () {

        it('cannot access lesson through wrong course URL', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create(['role' => 'learner']);

            // Create two courses
            $course1 = Course::factory()->published()->create(['user_id' => $cm->id]);
            $section1 = CourseSection::factory()->create(['course_id' => $course1->id]);
            $lesson1 = Lesson::factory()->create(['course_section_id' => $section1->id]);

            $course2 = Course::factory()->published()->create(['user_id' => $cm->id]);

            // Enroll in both courses
            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course1->id,
            ]);
            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course2->id,
            ]);

            // Try to access lesson1 through course2's URL
            $this->actingAs($learner)
                ->get(route('courses.lessons.show', [$course2, $lesson1]))
                ->assertNotFound();
        });

        it('cannot access assessment through wrong course URL', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create(['role' => 'learner']);

            $course1 = Course::factory()->published()->create(['user_id' => $cm->id]);
            $assessment1 = Assessment::factory()->published()->create([
                'course_id' => $course1->id,
                'user_id' => $cm->id,
            ]);

            $course2 = Course::factory()->published()->create(['user_id' => $cm->id]);

            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course1->id,
            ]);
            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course2->id,
            ]);

            // Try to access assessment1 through course2's URL
            // App returns 403 (forbidden) rather than 404 - both are valid security responses
            $this->actingAs($learner)
                ->get(route('assessments.show', [$course2, $assessment1]))
                ->assertForbidden();
        });

        it('cannot start assessment attempt through wrong course URL', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create(['role' => 'learner']);

            $course1 = Course::factory()->published()->create(['user_id' => $cm->id]);
            $assessment1 = Assessment::factory()->published()->create([
                'course_id' => $course1->id,
                'user_id' => $cm->id,
            ]);

            $course2 = Course::factory()->published()->create(['user_id' => $cm->id]);

            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course1->id,
            ]);
            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course2->id,
            ]);

            // App returns 403 (forbidden) rather than 404 - both are valid security responses
            $this->actingAs($learner)
                ->post(route('assessments.start', [$course2, $assessment1]))
                ->assertForbidden();
        });

    });

});
