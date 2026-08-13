<?php

/**
 * Instructor Course Creation End-to-End Tests
 *
 * Tests the complete instructor workflow:
 * - Course creation with metadata
 * - Section and lesson management
 * - Assessment creation
 * - Publishing workflow
 */

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\User;

describe('Instructor Course Creation Journey', function () {


    describe('Assessment Creation Workflow', function () {

        it('admin can publish assessment', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);
            $assessment = Assessment::factory()->draft()->create([
                'course_id' => $course->id,
                'user_id' => $cm->id,
            ]);

            $this->actingAs($admin)
                ->post(route('assessments.publish', [$course, $assessment]))
                ->assertRedirect();

            expect($assessment->refresh()->status)->toBe('published');
        });

    });

    describe('Course Update and Edit Workflow', function () {

        it('owner can update draft course details', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);

            $this->actingAs($cm)
                ->patch(route('courses.update', $course), [
                    'title' => 'Updated Course Title',
                    'short_description' => 'Updated description',
                    'difficulty_level' => 'advanced',
                    'visibility' => 'public',
                ])
                ->assertRedirect();

            $course->refresh();
            expect($course->title)->toBe('Updated Course Title');
            expect($course->short_description)->toBe('Updated description');
            expect($course->difficulty_level)->toBe('advanced');
        });

        it('owner can update section details', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);
            $section = CourseSection::factory()->create(['course_id' => $course->id]);

            $this->actingAs($cm)
                ->patch(route('sections.update', $section), [
                    'title' => 'Updated Section Title',
                    'description' => 'Updated section description',
                ])
                ->assertRedirect();

            expect($section->refresh()->title)->toBe('Updated Section Title');
        });

        it('owner can update lesson details', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);
            $section = CourseSection::factory()->create(['course_id' => $course->id]);
            $lesson = Lesson::factory()->create([
                'course_section_id' => $section->id,
                'content_type' => 'text',
            ]);

            $this->actingAs($cm)
                ->patch(route('lessons.update', $lesson), [
                    'title' => 'Updated Lesson Title',
                    'content_type' => 'text',
                    'rich_content' => [
                        'type' => 'doc',
                        'content' => [[
                            'type' => 'paragraph',
                            'content' => [['type' => 'text', 'text' => 'Updated content']],
                        ]],
                    ],
                ])
                ->assertRedirect();

            expect($lesson->refresh()->title)->toBe('Updated Lesson Title');
        });

        it('owner can delete lesson from draft course', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);
            $section = CourseSection::factory()->create(['course_id' => $course->id]);
            $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

            $lessonId = $lesson->id;

            $this->actingAs($cm)
                ->delete(route('lessons.destroy', $lesson))
                ->assertRedirect();

            // Lesson uses soft delete
            $this->assertSoftDeleted('lessons', ['id' => $lessonId]);
        });

        it('owner can delete section from draft course', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);
            $section = CourseSection::factory()->create(['course_id' => $course->id]);

            $sectionId = $section->id;

            $this->actingAs($cm)
                ->delete(route('sections.destroy', $section))
                ->assertRedirect();

            // Section uses soft delete
            $this->assertSoftDeleted('course_sections', ['id' => $sectionId]);
        });

    });

    describe('Publishing Restrictions', function () {

        it('admin can modify structure of published course', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $admin = User::factory()->create(['role' => 'lms_admin']);

            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);
            CourseSection::factory()->create(['course_id' => $course->id]);

            // Admin publishes the course
            $this->actingAs($admin)->post(route('courses.publish', $course));

            // Admin CAN add new sections
            $this->actingAs($admin)
                ->post(route('courses.sections.store', $course), [
                    'title' => 'Admin Added Section',
                    'description' => 'Section added by admin',
                ])
                ->assertRedirect();

            expect($course->sections()->where('title', 'Admin Added Section')->exists())->toBeTrue();
        });

    });

    describe('Course Visibility and Access', function () {

        it('draft course is only visible to owner and admins', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $otherCm = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create(['role' => 'learner']);
            $admin = User::factory()->create(['role' => 'lms_admin']);

            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);

            // Owner can view
            $this->actingAs($cm)
                ->get(route('courses.show', $course))
                ->assertOk();

            // Admin can view
            $this->actingAs($admin)
                ->get(route('courses.show', $course))
                ->assertOk();

            // Other CM can view (for collaboration purposes)
            $this->actingAs($otherCm)
                ->get(route('courses.show', $course))
                ->assertOk();

            // Learner cannot view draft course
            $this->actingAs($learner)
                ->get(route('courses.show', $course))
                ->assertForbidden();
        });

        it('course index shows appropriate courses per role', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create(['role' => 'learner']);

            // Create various courses
            Course::factory()->draft()->create(['user_id' => $cm->id, 'title' => 'CM Draft']);
            Course::factory()->published()->public()->create(['user_id' => $cm->id, 'title' => 'CM Published']);
            Course::factory()->published()->public()->create(['title' => 'Other Published']);

            // Admin sees all courses
            $this->actingAs($admin)
                ->get(route('courses.index'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->has('courses.data', 3) // All 3 courses
                );

            // Learner sees only published public courses
            $this->actingAs($learner)
                ->get(route('courses.index'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->has('courses.data', 2) // 2 published courses
                );
        });

    });

    describe('Assessment Required for Completion', function () {

        it('assessment can be marked as required', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);

            $this->actingAs($cm)
                ->post(route('assessments.store', $course), [
                    'title' => 'Required Assessment',
                    'passing_score' => 70,
                    'max_attempts' => 3,
                    'status' => 'draft',
                    'visibility' => 'public',
                    'is_required' => true,
                ])
                ->assertRedirect();

            $assessment = Assessment::where('title', 'Required Assessment')->first();
            expect($assessment->is_required)->toBeTrue();
        });

        it('assessment defaults to required when is_required not specified', function () {
            $cm = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);

            // Create assessment without specifying is_required
            $this->actingAs($cm)
                ->post(route('assessments.store', $course), [
                    'title' => 'Default Assessment',
                    'passing_score' => 70,
                    'max_attempts' => 3,
                    'status' => 'draft',
                    'visibility' => 'public',
                    // is_required not specified - should default to true
                ])
                ->assertRedirect();

            $assessment = Assessment::where('title', 'Default Assessment')->first();
            // Database default is true
            expect($assessment->is_required)->toBeTrue();
        });

    });

});
