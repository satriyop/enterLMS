<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\User;

/**
 * Nested Route Scoping Tests
 *
 * These tests verify that child resources cannot be accessed or manipulated
 * through a parent resource they don't belong to. Route model binding does
 * NOT auto-scope children to parents — this must be verified explicitly.
 */
describe('Nested Route Scoping', function () {

    describe('Lesson through wrong Course', function () {

        it('cannot view lesson through wrong course URL', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);
            $learner = User::factory()->create(['role' => 'learner']);

            $courseA = Course::factory()->published()->create(['user_id' => $cm->id]);
            $sectionA = CourseSection::factory()->create(['course_id' => $courseA->id]);
            $lessonA = Lesson::factory()->create(['course_section_id' => $sectionA->id]);

            $courseB = Course::factory()->published()->create(['user_id' => $cm->id]);

            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $courseA->id,
            ]);
            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $courseB->id,
            ]);

            // LessonA belongs to CourseA, accessing via CourseB URL should fail
            $this->actingAs($learner)
                ->get(route('courses.lessons.show', [$courseB, $lessonA]))
                ->assertNotFound();
        });

        it('cannot complete lesson through wrong course URL', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);
            $learner = User::factory()->create(['role' => 'learner']);

            $courseA = Course::factory()->published()->create(['user_id' => $cm->id]);
            $sectionA = CourseSection::factory()->create(['course_id' => $courseA->id]);
            $lessonA = Lesson::factory()->create(['course_section_id' => $sectionA->id]);

            $courseB = Course::factory()->published()->create(['user_id' => $cm->id]);

            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $courseA->id,
            ]);
            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $courseB->id,
            ]);

            $this->actingAs($learner)
                ->post(route('courses.lessons.progress.complete', [$courseB, $lessonA]))
                ->assertNotFound();
        });

    });

    describe('Assessment through wrong Course', function () {

        it('cannot view assessment through wrong course URL', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);
            $learner = User::factory()->create(['role' => 'learner']);

            $courseA = Course::factory()->published()->create(['user_id' => $cm->id]);
            $assessmentA = Assessment::factory()->published()->create([
                'course_id' => $courseA->id,
                'user_id' => $cm->id,
            ]);

            $courseB = Course::factory()->published()->create(['user_id' => $cm->id]);

            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $courseA->id,
            ]);
            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $courseB->id,
            ]);

            // AssessmentA belongs to CourseA, should fail via CourseB
            $response = $this->actingAs($learner)
                ->get(route('assessments.show', [$courseB, $assessmentA]));

            // Application returns 403 (policy denies because assessment doesn't match course)
            expect($response->status())->toBeIn([403, 404]);
        });

        it('cannot start assessment attempt through wrong course URL', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);
            $learner = User::factory()->create(['role' => 'learner']);

            $courseA = Course::factory()->published()->create(['user_id' => $cm->id]);
            $assessmentA = Assessment::factory()->published()->create([
                'course_id' => $courseA->id,
                'user_id' => $cm->id,
            ]);

            $courseB = Course::factory()->published()->create(['user_id' => $cm->id]);

            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $courseA->id,
            ]);
            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $courseB->id,
            ]);

            $response = $this->actingAs($learner)
                ->post(route('assessments.start', [$courseB, $assessmentA]));

            expect($response->status())->toBeIn([403, 404]);
        });

        it('content manager cannot edit assessment through wrong course URL', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);

            $courseA = Course::factory()->draft()->create(['user_id' => $cm->id]);
            $assessmentA = Assessment::factory()->draft()->create([
                'course_id' => $courseA->id,
                'user_id' => $cm->id,
            ]);

            $courseB = Course::factory()->draft()->create(['user_id' => $cm->id]);

            $response = $this->actingAs($cm)
                ->get(route('assessments.edit', [$courseB, $assessmentA]));

            expect($response->status())->toBeIn([403, 404]);
        });

    });

    describe('Reorder with cross-resource IDs', function () {

        it('reorder sections rejects IDs from another course', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);

            $courseA = Course::factory()->draft()->create(['user_id' => $cm->id]);
            $sectionA1 = CourseSection::factory()->create(['course_id' => $courseA->id, 'order' => 1]);
            $sectionA2 = CourseSection::factory()->create(['course_id' => $courseA->id, 'order' => 2]);

            $courseB = Course::factory()->draft()->create(['user_id' => $cm->id]);
            $sectionB1 = CourseSection::factory()->create(['course_id' => $courseB->id, 'order' => 1]);

            // Try to reorder courseA's sections but include sectionB1 (belongs to courseB)
            $this->actingAs($cm)
                ->post(route('courses.sections.reorder', $courseA), [
                    'sections' => [$sectionA1->id, $sectionB1->id],
                ])
                ->assertSessionHasErrors('sections.1');
        });

        it('reorder questions rejects IDs from another assessment', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);

            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);

            $assessmentA = Assessment::factory()->draft()->create([
                'course_id' => $course->id,
                'user_id' => $cm->id,
            ]);
            $questionA = Question::factory()->create(['assessment_id' => $assessmentA->id]);

            $assessmentB = Assessment::factory()->draft()->create([
                'course_id' => $course->id,
                'user_id' => $cm->id,
            ]);
            $questionB = Question::factory()->create(['assessment_id' => $assessmentB->id]);

            // ReorderQuestionsRequest expects questions[].id + questions[].position format
            $this->actingAs($cm)
                ->post(route('assessments.questions.reorder', [$course, $assessmentA]), [
                    'questions' => [
                        ['id' => $questionA->id, 'position' => 0],
                        ['id' => $questionB->id, 'position' => 1],
                    ],
                ])
                ->assertSessionHasErrors('questions.1.id');
        });

    });

    describe('Question through wrong Assessment', function () {

        it('cannot delete question through wrong assessment URL', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);

            $assessmentA = Assessment::factory()->draft()->create([
                'course_id' => $course->id,
                'user_id' => $cm->id,
            ]);
            $questionA = Question::factory()->create(['assessment_id' => $assessmentA->id]);

            $assessmentB = Assessment::factory()->draft()->create([
                'course_id' => $course->id,
                'user_id' => $cm->id,
            ]);

            // QuestionA belongs to assessmentA, try to delete via assessmentB
            $this->actingAs($cm)
                ->delete(route('assessments.questions.destroy', [$course, $assessmentB, $questionA]))
                ->assertNotFound();
        });

    });

});
