<?php

namespace App\Domain\LearningPath\Services;

use App\Domain\Enrollment\Services\EnrollmentService;
use App\Domain\LearningPath\Events\PathEnrollmentCreated;
use App\Domain\LearningPath\Exceptions\AlreadyEnrolledInPathException;
use App\Domain\LearningPath\Exceptions\PathNotOpenForSelfEnrollmentException;
use App\Domain\LearningPath\Exceptions\PathNotPublishedException;
use App\Domain\LearningPath\States\ActivePathState;
use App\Domain\LearningPath\States\AvailableCourseState;
use App\Domain\LearningPath\States\CompletedPathState;
use App\Domain\LearningPath\States\DroppedPathState;
use App\Domain\LearningPath\States\LockedCourseState;
use App\Domain\Shared\Services\DomainLogger;
use App\Domain\Shared\Services\MetricsService;
use App\Models\Course;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PathEnrollmentService
{
    public function __construct(
        protected DomainLogger $logger,
        protected MetricsService $metrics,
        protected PrerequisiteEvaluatorFactory $evaluatorFactory,
        protected EnrollmentService $enrollmentService
    ) {}

    public function enroll(User $user, LearningPath $path, bool $preserveProgress = true, bool $grantedByAdmin = false): LearningPathEnrollment
    {
        $this->validateEnrollment($user, $path, requireOpenForSelfEnrollment: ! $grantedByAdmin);

        // Check for existing dropped enrollment (re-enrollment case)
        $droppedEnrollment = $this->getDroppedEnrollment($user, $path);
        if ($droppedEnrollment) {
            return $this->reactivatePathEnrollment($droppedEnrollment, $preserveProgress);
        }

        $this->logger->info('learning_path.enrollment.starting', [
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
        ]);

        $startTime = microtime(true);

        try {
            $enrollment = DB::transaction(function () use ($user, $path) {
                // Create the path enrollment
                $enrollment = LearningPathEnrollment::create([
                    'user_id' => $user->id,
                    'learning_path_id' => $path->id,
                    'state' => ActivePathState::$name,
                    'progress_percentage' => 0,
                    'enrolled_at' => now(),
                ]);

                // Initialize course progress for all courses in the path
                $this->initializeCourseProgress($enrollment, $path);

                PathEnrollmentCreated::dispatch($enrollment);

                return $enrollment;
            });

            $this->metrics->increment('learning_path.enrollments.created');
            $this->metrics->timing('learning_path.enrollment.duration', microtime(true) - $startTime);

            $this->logger->info('learning_path.enrollment.created', [
                'enrollment_id' => $enrollment->id,
                'user_id' => $user->id,
                'learning_path_id' => $path->id,
            ]);

            return $enrollment;
        } catch (\Throwable $e) {
            $this->metrics->increment('learning_path.enrollments.failed');
            $this->logger->error('learning_path.enrollment.failed', [
                'user_id' => $user->id,
                'learning_path_id' => $path->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get dropped enrollment for a user in a learning path.
     */
    public function getDroppedEnrollment(User $user, LearningPath $path): ?LearningPathEnrollment
    {
        return LearningPathEnrollment::query()
            ->where('user_id', $user->id)
            ->where('learning_path_id', $path->id)
            ->where('state', DroppedPathState::$name)
            ->first();
    }

    /**
     * Reactivate a dropped path enrollment (re-enrollment).
     *
     * Note: Named 'PathEnrollment' to distinguish from EnrollmentService's method.
     *
     * Best practice: Preserve progress by default to honor learner's previous work.
     * Set preserveProgress to false to reset the learner's progress.
     *
     * IMPORTANT: This default matches EnrollmentService::reactivateCourseEnrollment()
     * for consistent API behavior across all reactivation methods.
     */
    public function reactivatePathEnrollment(
        LearningPathEnrollment $enrollment,
        bool $preserveProgress = true
    ): LearningPathEnrollment {
        $this->logger->info('learning_path.enrollment.reactivating', [
            'enrollment_id' => $enrollment->id,
            'preserve_progress' => $preserveProgress,
        ]);

        DB::transaction(function () use ($enrollment, $preserveProgress) {
            $path = $enrollment->learningPath;

            // Reactivate the enrollment
            $enrollment->update([
                'state' => ActivePathState::$name,
                'enrolled_at' => now(),
                'dropped_at' => null,
                'drop_reason' => null,
                'completed_at' => null,
            ]);

            if ($preserveProgress) {
                // Keep existing course progress, just re-link course enrollments
                $this->relinkCourseEnrollments($enrollment);
            } else {
                // Delete old course progress and start fresh
                $enrollment->courseProgress()->delete();
                $enrollment->update(['progress_percentage' => 0]);
                $this->initializeCourseProgress($enrollment, $path);
            }

            PathEnrollmentCreated::dispatch($enrollment);
        });

        $this->metrics->increment('learning_path.enrollments.reactivated');

        $this->logger->info('learning_path.enrollment.reactivated', [
            'enrollment_id' => $enrollment->id,
            'preserve_progress' => $preserveProgress,
        ]);

        return $enrollment;
    }

    /**
     * Re-link course enrollments for reactivated path enrollment.
     * Creates new course enrollments for unlocked courses that don't have one.
     */
    protected function relinkCourseEnrollments(LearningPathEnrollment $enrollment): void
    {
        $enrollment->loadMissing('user', 'courseProgress.course');
        $user = $enrollment->user;

        foreach ($enrollment->courseProgress as $progress) {
            // Skip locked courses - they don't need enrollment yet
            if ($progress->isLocked()) {
                continue;
            }

            // If already has valid course enrollment, skip
            if ($progress->course_enrollment_id && $progress->courseEnrollment?->isActive()) {
                continue;
            }

            // Create/link course enrollment
            $courseEnrollment = $this->ensureCourseEnrollment($user, $progress->course);
            $progress->update(['course_enrollment_id' => $courseEnrollment->id]);
        }
    }

    public function canEnroll(User $user, LearningPath $path): bool
    {
        if ($this->getActiveEnrollment($user, $path)) {
            return false;
        }

        return $path->isOpenForSelfEnrollment();
    }

    public function enrollByAdmin(User $admin, User $learner, LearningPath $path, bool $preserveProgress = true): LearningPathEnrollment
    {
        if (! $admin->isLmsAdmin()) {
            throw new PathNotOpenForSelfEnrollmentException($path->id);
        }

        return $this->enroll($learner, $path, $preserveProgress, grantedByAdmin: true);
    }

    public function getActiveEnrollment(User $user, LearningPath $path): ?LearningPathEnrollment
    {
        return LearningPathEnrollment::query()
            ->where('user_id', $user->id)
            ->where('learning_path_id', $path->id)
            ->whereIn('state', [ActivePathState::$name, CompletedPathState::$name])
            ->first();
    }

    public function isEnrolled(User $user, LearningPath $path): bool
    {
        return $this->getActiveEnrollment($user, $path) !== null;
    }

    public function drop(LearningPathEnrollment $enrollment, ?string $reason = null): void
    {
        $this->logger->info('learning_path.drop.starting', [
            'enrollment_id' => $enrollment->id,
            'reason' => $reason,
        ]);

        // Model owns the state transition and event dispatch
        $enrollment->drop($reason);

        $this->metrics->increment('learning_path.enrollments.dropped');

        $this->logger->info('learning_path.drop.completed', [
            'enrollment_id' => $enrollment->id,
        ]);
    }

    public function complete(LearningPathEnrollment $enrollment): void
    {
        if ($enrollment->isCompleted()) {
            return; // Idempotent - check before logging
        }

        $this->logger->info('learning_path.complete.starting', [
            'enrollment_id' => $enrollment->id,
        ]);

        // Model owns the state transition and event dispatch
        $enrollment->complete();

        $this->metrics->increment('learning_path.enrollments.completed');

        $this->logger->info('learning_path.complete.completed', [
            'enrollment_id' => $enrollment->id,
        ]);
    }

    public function getActiveEnrollments(User $user): Collection
    {
        return LearningPathEnrollment::query()
            ->where('user_id', $user->id)
            ->where('state', ActivePathState::$name)
            ->with(['learningPath', 'courseProgress'])
            ->get();
    }

    protected function validateEnrollment(User $user, LearningPath $path, bool $requireOpenForSelfEnrollment = true): void
    {
        $existingEnrollment = $this->getActiveEnrollment($user, $path);
        if ($existingEnrollment) {
            throw new AlreadyEnrolledInPathException($user->id, $path->id);
        }

        if (! $path->is_published) {
            throw new PathNotPublishedException($path->id);
        }

        if ($requireOpenForSelfEnrollment && $path->isRestricted()) {
            throw new PathNotOpenForSelfEnrollmentException($path->id);
        }
    }

    protected function initializeCourseProgress(
        LearningPathEnrollment $enrollment,
        LearningPath $path
    ): void {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Course> $courses */
        $courses = $path->courses()->orderBy('learning_path_course.position')->get();
        $enrollment->loadMissing('user');
        $user = $enrollment->user;
        $noPrerequisites = $path->prerequisite_mode === 'none';

        foreach ($courses as $index => $course) {
            /** @var Course $course */
            /** @var object{position: int, is_required: bool} $pivot */
            $pivot = $course->pivot;
            $isRequired = $pivot->is_required ?? true;
            $isFirstCourse = $index === 0;
            $isAvailable = $isFirstCourse || $noPrerequisites;
            $state = $isAvailable ? AvailableCourseState::$name : LockedCourseState::$name;

            // Only create course enrollment for REQUIRED + AVAILABLE courses
            // Optional courses are tracked in progress but not auto-enrolled
            $courseEnrollmentId = null;
            if ($isAvailable && $isRequired) {
                $courseEnrollment = $this->ensureCourseEnrollment($user, $course);
                $courseEnrollmentId = $courseEnrollment->id;
            }

            $enrollment->courseProgress()->create([
                'course_id' => $course->id,
                'course_enrollment_id' => $courseEnrollmentId,
                'state' => $state,
                'position' => $pivot->position,
                'unlocked_at' => $isAvailable ? now() : null,
            ]);
        }
    }

    /**
     * Ensure the Learner holds a seat in the Course this path step points at.
     * Reuses the enrollment they already have, or creates one.
     */
    public function ensureCourseEnrollment(User $user, Course $course): \App\Models\Enrollment
    {
        // A Course finished before the path was joined still counts as held --
        // asking only for an *active* enrollment would try to create a second
        // one and hit the unique seat constraint.
        $existingEnrollment = $this->enrollmentService->getCurrentEnrollment($user, $course);

        if ($existingEnrollment) {
            $this->logger->info('learning_path.course_enrollment.reused', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrollment_id' => $existingEnrollment->id,
            ]);

            return $existingEnrollment;
        }

        // Create new enrollment - now returns Enrollment model directly
        $enrollment = $this->enrollmentService->enroll(
            userId: $user->id,
            courseId: $course->id,
        );

        $this->logger->info('learning_path.course_enrollment.created', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
        ]);

        return $enrollment;
    }

    /**
     * Enroll a learner in an optional course within a learning path.
     *
     * Optional courses are tracked in progress but not auto-enrolled.
     * This method allows learners to opt-in to optional courses.
     *
     * @throws \InvalidArgumentException If course is not in path, not optional, or not available
     */
    public function enrollInOptionalCourse(
        LearningPathEnrollment $pathEnrollment,
        Course $course
    ): \App\Models\Enrollment {
        $pathEnrollment->loadMissing(['learningPath.courses', 'user', 'courseProgress']);

        // Verify the course is in the path
        $pathCourse = $pathEnrollment->learningPath->courses
            ->firstWhere('id', $course->id);

        if (! $pathCourse) {
            throw new \InvalidArgumentException(
                "Course {$course->id} is not part of learning path {$pathEnrollment->learning_path_id}"
            );
        }

        // Verify the course is optional
        /** @var object{is_required: bool} $pivot */
        $pivot = $pathCourse->pivot;
        if ($pivot->is_required) {
            throw new \InvalidArgumentException(
                "Course {$course->id} is required, not optional. Required courses are auto-enrolled."
            );
        }

        // Get the course progress record
        $progress = $pathEnrollment->courseProgress
            ->firstWhere('course_id', $course->id);

        if (! $progress) {
            throw new \InvalidArgumentException(
                "No progress record found for course {$course->id} in path enrollment {$pathEnrollment->id}"
            );
        }

        // Verify the course is available (not locked)
        if ($progress->isLocked()) {
            throw new \InvalidArgumentException(
                "Course {$course->id} is locked. Complete prerequisites first."
            );
        }

        // Check if already enrolled
        if ($progress->course_enrollment_id !== null) {
            $this->logger->info('learning_path.optional_course.already_enrolled', [
                'path_enrollment_id' => $pathEnrollment->id,
                'course_id' => $course->id,
                'course_enrollment_id' => $progress->course_enrollment_id,
            ]);

            return \App\Models\Enrollment::findOrFail($progress->course_enrollment_id);
        }

        // Create the course enrollment
        $courseEnrollment = $this->ensureCourseEnrollment($pathEnrollment->user, $course);

        // Link the enrollment to the progress record
        $progress->update([
            'course_enrollment_id' => $courseEnrollment->id,
            'started_at' => $progress->started_at ?? now(),
        ]);

        $this->logger->info('learning_path.optional_course.enrolled', [
            'path_enrollment_id' => $pathEnrollment->id,
            'course_id' => $course->id,
            'course_enrollment_id' => $courseEnrollment->id,
        ]);

        return $courseEnrollment;
    }
}
