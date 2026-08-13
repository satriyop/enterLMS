<?php

namespace Tests\Unit\Policies;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Media;
use App\Models\User;
use App\Policies\MediaPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Unit tests for MediaPolicy.
 *
 * Tests verify authorization logic for media management operations.
 * Media authorization delegates to the parent model (Course or Lesson).
 * Users with 'update' permission on the parent can create/delete media.
 *
 * Note: Methods that delegate to CoursePolicy/LessonPolicy via Gate::allows()
 * require actingAs() so the inner Gate call resolves the correct user.
 */
beforeEach(function () {
    $this->policy = new MediaPolicy;

    $this->lmsAdmin = User::factory()->create(['role' => 'lms_admin']);
    $this->contentManager = User::factory()->create(['role' => 'lms_admin']);
    $this->otherContentManager = User::factory()->create(['role' => 'lms_admin']);
    $this->learner = User::factory()->create(['role' => 'learner']);

    // Create DRAFT course for owner to be able to update
    $this->course = Course::factory()->draft()->create(['user_id' => $this->contentManager->id]);
    $this->section = CourseSection::factory()->create(['course_id' => $this->course->id]);
    $this->lesson = Lesson::factory()->create(['course_section_id' => $this->section->id]);

    // Create media for the lesson
    $this->lessonMedia = Media::factory()->video()->create([
        'mediable_type' => Lesson::class,
        'mediable_id' => $this->lesson->id,
    ]);

    // Create media for the course
    $this->courseMedia = Media::factory()->thumbnail()->create([
        'mediable_type' => Course::class,
        'mediable_id' => $this->course->id,
    ]);
});

// ========== create (for Course) ==========

describe('create media for Course', function () {
    it('allows lms_admin to create media for any course', function () {
        $this->actingAs($this->lmsAdmin);
        expect(Gate::check('create', [Media::class, $this->course]))->toBeTrue();
    });

    it('denies learner to create media for any course', function () {
        $this->actingAs($this->learner);
        expect(Gate::denies('create', [Media::class, $this->course]))->toBeTrue();
    });
});

// ========== create (for Lesson) ==========

describe('create media for Lesson', function () {
    it('allows lms_admin to create media for any lesson', function () {
        $this->actingAs($this->lmsAdmin);
        expect(Gate::check('create', [Media::class, $this->lesson]))->toBeTrue();
    });

    it('denies learner to create media for any lesson', function () {
        $this->actingAs($this->learner);
        expect(Gate::denies('create', [Media::class, $this->lesson]))->toBeTrue();
    });
});

// ========== delete (for Course media) ==========

describe('delete media from Course', function () {
    it('allows lms_admin to delete media from any course', function () {
        $this->actingAs($this->lmsAdmin);
        expect(Gate::check('delete', $this->courseMedia))->toBeTrue();
    });

    it('denies learner to delete media from any course', function () {
        $this->actingAs($this->learner);
        expect(Gate::denies('delete', $this->courseMedia))->toBeTrue();
    });
});

// ========== delete (for Lesson media) ==========

describe('delete media from Lesson', function () {
    it('allows lms_admin to delete media from any lesson', function () {
        $this->actingAs($this->lmsAdmin);
        expect(Gate::check('delete', $this->lessonMedia))->toBeTrue();
    });

    it('denies learner to delete media from any lesson', function () {
        $this->actingAs($this->learner);
        expect(Gate::denies('delete', $this->lessonMedia))->toBeTrue();
    });
});

// ========== Edge Cases ==========

describe('edge cases', function () {
    it('returns false when deleting orphaned media (null mediable)', function () {
        // Create a lesson and media, then delete the lesson to orphan the media
        $tempCourse = Course::factory()->draft()->create();
        $tempSection = CourseSection::factory()->create(['course_id' => $tempCourse->id]);
        $tempLesson = Lesson::factory()->create(['course_section_id' => $tempSection->id]);

        $orphanedMedia = Media::factory()->video()->create([
            'mediable_type' => Lesson::class,
            'mediable_id' => $tempLesson->id,
        ]);

        // Delete the lesson to orphan the media (force delete to bypass soft deletes if any)
        $tempLesson->forceDelete();

        // Reload media to get fresh state
        $orphanedMedia = $orphanedMedia->fresh();

        // Even admin cannot delete orphaned media (mediable will be null after deletion)
        $this->actingAs($this->lmsAdmin);
        expect(Gate::denies('delete', $orphanedMedia))->toBeTrue();
    });

    it('allows lms_admin to create media for published course', function () {
        $publishedCourse = Course::factory()->published()->create();

        // LMS admin can edit any course regardless of status
        $this->actingAs($this->lmsAdmin);
        expect(Gate::check('create', [Media::class, $publishedCourse]))->toBeTrue();
    });
});
