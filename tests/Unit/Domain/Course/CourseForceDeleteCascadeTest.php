<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\CourseRating;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;

describe('Course force delete cascade', function () {
    it('cleans up sections and lessons via Eloquent on force delete', function () {
        $course = Course::factory()->create();
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

        $course->forceDelete();

        $this->assertDatabaseMissing('course_sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    });

    it('cleans up assessments via Eloquent on force delete', function () {
        $course = Course::factory()->create();
        $assessment = Assessment::factory()->create(['course_id' => $course->id]);

        $course->forceDelete();

        $this->assertDatabaseMissing('assessments', ['id' => $assessment->id]);
    });

    it('cleans up enrollments on force delete', function () {
        $course = Course::factory()->create();
        $enrollment = Enrollment::factory()->active()->create(['course_id' => $course->id]);

        $course->forceDelete();

        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    });

    it('cleans up invitations on force delete', function () {
        $course = Course::factory()->create();
        $invitation = CourseInvitation::factory()->create(['course_id' => $course->id]);

        $course->forceDelete();

        $this->assertDatabaseMissing('course_invitations', ['id' => $invitation->id]);
    });

    it('cleans up ratings on force delete', function () {
        $course = Course::factory()->published()->create();
        $learner = User::factory()->create(['role' => 'learner']);
        Enrollment::factory()->active()->create([
            'course_id' => $course->id,
            'user_id' => $learner->id,
        ]);
        $rating = CourseRating::factory()->create([
            'course_id' => $course->id,
            'user_id' => $learner->id,
        ]);

        $course->forceDelete();

        $this->assertDatabaseMissing('course_ratings', ['id' => $rating->id]);
    });

    it('does not clean up children on soft delete', function () {
        $course = Course::factory()->create();
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $enrollment = Enrollment::factory()->active()->create(['course_id' => $course->id]);

        $course->delete(); // soft delete

        // Children should still exist
        $this->assertDatabaseHas('course_sections', ['id' => $section->id]);
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id]);

        // Course should be soft-deleted
        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    });
});
