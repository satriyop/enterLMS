<?php

use App\Domain\LearningPath\Services\PathEnrollmentService;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\User;

beforeEach(function () {
    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->requiredCourse = Course::factory()->published()->create();
    $this->optionalCourse = Course::factory()->published()->create();

    $this->path = LearningPath::factory()->published()->create([
        'prerequisite_mode' => 'none', // No prerequisites for simplicity
    ]);

    // Attach courses - first required, second optional
    $this->path->courses()->attach($this->requiredCourse->id, [
        'position' => 1,
        'is_required' => true,
    ]);
    $this->path->courses()->attach($this->optionalCourse->id, [
        'position' => 2,
        'is_required' => false,
    ]);

    $this->enrollmentService = app(PathEnrollmentService::class);
});

describe('PathEnrollmentService::enrollInOptionalCourse', function () {
    it('enrolls learner in optional course', function () {
        // Enroll in path first
        $pathEnrollment = $this->enrollmentService->enroll($this->learner, $this->path);

        // Verify optional course has no enrollment initially
        $optionalProgress = $pathEnrollment->courseProgress()
            ->where('course_id', $this->optionalCourse->id)
            ->first();
        expect($optionalProgress->course_enrollment_id)->toBeNull();

        // Enroll in optional course
        $courseEnrollment = $this->enrollmentService->enrollInOptionalCourse(
            $pathEnrollment,
            $this->optionalCourse
        );

        expect($courseEnrollment)->toBeInstanceOf(Enrollment::class);
        expect($courseEnrollment->course_id)->toBe($this->optionalCourse->id);
        expect($courseEnrollment->user_id)->toBe($this->learner->id);

        // Verify progress record is updated
        $optionalProgress->refresh();
        expect($optionalProgress->course_enrollment_id)->toBe($courseEnrollment->id);
    });

    it('returns existing enrollment if already enrolled', function () {
        $pathEnrollment = $this->enrollmentService->enroll($this->learner, $this->path);

        // First enrollment
        $enrollment1 = $this->enrollmentService->enrollInOptionalCourse(
            $pathEnrollment,
            $this->optionalCourse
        );

        // Second call should return same enrollment
        $enrollment2 = $this->enrollmentService->enrollInOptionalCourse(
            $pathEnrollment,
            $this->optionalCourse
        );

        expect($enrollment1->id)->toBe($enrollment2->id);
    });

    it('throws exception for course not in path', function () {
        $pathEnrollment = $this->enrollmentService->enroll($this->learner, $this->path);
        $unrelatedCourse = Course::factory()->published()->create();

        $this->enrollmentService->enrollInOptionalCourse($pathEnrollment, $unrelatedCourse);
    })->throws(\InvalidArgumentException::class, 'not part of learning path');

    it('throws exception for required course', function () {
        $pathEnrollment = $this->enrollmentService->enroll($this->learner, $this->path);

        $this->enrollmentService->enrollInOptionalCourse($pathEnrollment, $this->requiredCourse);
    })->throws(\InvalidArgumentException::class, 'is required');

    it('throws exception for locked course', function () {
        // Create a sequential path
        $sequentialPath = LearningPath::factory()->published()->create([
            'prerequisite_mode' => 'sequential',
        ]);
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();

        $sequentialPath->courses()->attach($course1->id, [
            'position' => 1,
            'is_required' => true,
        ]);
        $sequentialPath->courses()->attach($course2->id, [
            'position' => 2,
            'is_required' => false, // Optional but locked until course 1 is done
        ]);

        $pathEnrollment = $this->enrollmentService->enroll($this->learner, $sequentialPath);

        // Course 2 should be locked
        $course2Progress = $pathEnrollment->courseProgress()
            ->where('course_id', $course2->id)
            ->first();
        expect($course2Progress->isLocked())->toBeTrue();

        $this->enrollmentService->enrollInOptionalCourse($pathEnrollment, $course2);
    })->throws(\InvalidArgumentException::class, 'is locked');
});

describe('Controller endpoint', function () {
    it('allows learner to enroll in optional course via HTTP', function () {
        $pathEnrollment = $this->enrollmentService->enroll($this->learner, $this->path);

        $response = $this->actingAs($this->learner)
            ->post(route('learner.learning-paths.courses.enroll', [
                'learningPath' => $this->path,
                'course' => $this->optionalCourse,
            ]));

        $response->assertRedirect(route('learner.learning-paths.progress', $this->path));
        $response->assertSessionHas('success');

        // Verify enrollment was created
        expect(Enrollment::where([
            'user_id' => $this->learner->id,
            'course_id' => $this->optionalCourse->id,
        ])->exists())->toBeTrue();
    });

    it('returns error if not enrolled in path', function () {
        $response = $this->actingAs($this->learner)
            ->post(route('learner.learning-paths.courses.enroll', [
                'learningPath' => $this->path,
                'course' => $this->optionalCourse,
            ]));

        $response->assertRedirect(route('learner.learning-paths.show', $this->path));
        $response->assertSessionHas('error');
    });

    it('returns translated error for locked course', function () {
        // Create sequential path where course 2 is locked
        $sequentialPath = LearningPath::factory()->published()->create([
            'prerequisite_mode' => 'sequential',
        ]);
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();

        $sequentialPath->courses()->attach($course1->id, [
            'position' => 1,
            'is_required' => true,
        ]);
        $sequentialPath->courses()->attach($course2->id, [
            'position' => 2,
            'is_required' => false,
        ]);

        $this->enrollmentService->enroll($this->learner, $sequentialPath);

        $response = $this->actingAs($this->learner)
            ->post(route('learner.learning-paths.courses.enroll', [
                'learningPath' => $sequentialPath,
                'course' => $course2,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Kursus ini masih terkunci. Selesaikan prasyarat terlebih dahulu.');
    });
});
