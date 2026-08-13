<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\User;

describe('CourseReorderController', function () {
    describe('POST /courses/{course}/sections/reorder', function () {

        it('allows admin to reorder any course sections', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $otherUser = User::factory()->create(['role' => 'lms_admin']);

            $course = Course::factory()->draft()->create([
                'user_id' => $otherUser->id,
            ]);

            $section1 = CourseSection::factory()->create([
                'course_id' => $course->id,
                'order' => 1,
            ]);

            $section2 = CourseSection::factory()->create([
                'course_id' => $course->id,
                'order' => 2,
            ]);

            $response = $this
                ->actingAs($admin)
                ->postJson(route('courses.sections.reorder', $course), [
                    'sections' => [$section2->id, $section1->id],
                ]);

            $response->assertSuccessful();

            expect($section2->fresh()->order)->toBe(1);
            expect($section1->fresh()->order)->toBe(2);
        });

        it('prevents learner from reordering sections', function () {
            $learner = User::factory()->create(['role' => 'learner']);

            $course = Course::factory()->draft()->create();
            $section1 = CourseSection::factory()->create(['course_id' => $course->id]);
            $section2 = CourseSection::factory()->create(['course_id' => $course->id]);

            $response = $this
                ->actingAs($learner)
                ->postJson(route('courses.sections.reorder', $course), [
                    'sections' => [$section2->id, $section1->id],
                ]);

            $response->assertForbidden();
        });

        it('validates section IDs exist', function () {
            $contentManager = User::factory()->create(['role' => 'lms_admin']);

            $course = Course::factory()->draft()->create([
                'user_id' => $contentManager->id,
            ]);

            $response = $this
                ->actingAs($contentManager)
                ->postJson(route('courses.sections.reorder', $course), [
                    'sections' => [99999, 88888],
                ]);

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('sections.0');
        });

        it('validates sections array is required', function () {
            $contentManager = User::factory()->create(['role' => 'lms_admin']);

            $course = Course::factory()->draft()->create([
                'user_id' => $contentManager->id,
            ]);

            $response = $this
                ->actingAs($contentManager)
                ->postJson(route('courses.sections.reorder', $course), []);

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('sections');
        });
    });

    describe('POST /sections/{section}/lessons/reorder', function () {

        it('validates lesson IDs exist', function () {
            $contentManager = User::factory()->create(['role' => 'lms_admin']);

            $course = Course::factory()->draft()->create([
                'user_id' => $contentManager->id,
            ]);

            $section = CourseSection::factory()->create([
                'course_id' => $course->id,
            ]);

            $response = $this
                ->actingAs($contentManager)
                ->postJson(route('sections.lessons.reorder', $section), [
                    'lessons' => [99999, 88888],
                ]);

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('lessons.0');
        });

        it('verifies order is actually updated in database', function () {
            $contentManager = User::factory()->create(['role' => 'lms_admin']);

            $course = Course::factory()->draft()->create([
                'user_id' => $contentManager->id,
            ]);

            $section = CourseSection::factory()->create([
                'course_id' => $course->id,
            ]);

            $lesson1 = Lesson::factory()->create([
                'course_section_id' => $section->id,
                'order' => 1,
            ]);

            $lesson2 = Lesson::factory()->create([
                'course_section_id' => $section->id,
                'order' => 2,
            ]);

            $response = $this
                ->actingAs($contentManager)
                ->postJson(route('sections.lessons.reorder', $section), [
                    'lessons' => [$lesson2->id, $lesson1->id],
                ]);

            $response->assertSuccessful();

            $updatedLesson1 = Lesson::find($lesson1->id);
            $updatedLesson2 = Lesson::find($lesson2->id);

            expect($updatedLesson2->order)->toBe(1);
            expect($updatedLesson1->order)->toBe(2);
        });
    });
});
