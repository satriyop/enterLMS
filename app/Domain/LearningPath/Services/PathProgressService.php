<?php

namespace App\Domain\LearningPath\Services;

use App\Domain\Enrollment\Services\EnrollmentService;
use App\Domain\LearningPath\DTOs\PathProgressResult;
use App\Domain\LearningPath\DTOs\PrerequisiteCheckResult;
use App\Domain\LearningPath\Events\CourseUnlockedInPath;
use App\Domain\LearningPath\Events\PathProgressUpdated;
use App\Domain\LearningPath\Exceptions\CourseNotInPathException;
use App\Domain\LearningPath\Exceptions\PrerequisitesNotMetException;
use App\Domain\LearningPath\States\AvailableCourseState;
use App\Domain\LearningPath\States\CompletedCourseState;
use App\Domain\LearningPath\States\InProgressCourseState;
use App\Domain\LearningPath\States\LockedCourseState;
use App\Domain\Shared\Services\DomainLogger;
use App\Domain\Shared\Services\MetricsService;
use App\Domain\Shared\ValueObjects\Percentage;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningPathCourseProgress;
use App\Models\LearningPathEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PathProgressService
{
    public function __construct(
        protected DomainLogger $logger,
        protected MetricsService $metrics,
        protected PrerequisiteEvaluatorFactory $evaluatorFactory,
        protected EnrollmentService $enrollmentService
    ) {}

    public function getProgress(LearningPathEnrollment $enrollment): PathProgressResult
    {
        // Load course progress with related data
        /** @var \Illuminate\Database\Eloquent\Collection<int, LearningPathCourseProgress> $courseProgresses */
        $courseProgresses = $enrollment->courseProgress()
            ->with(['course', 'courseEnrollment'])
            ->orderBy('position')
            ->get();

        // Build pivot data lookup from learning path courses
        $pivotDataByCourseId = $enrollment->learningPath->courses
            ->keyBy('id')
            ->map(fn ($course) => [
                'is_required' => $course->pivot->is_required ?? true,
                'min_completion_percentage' => $course->pivot->min_completion_percentage ?? null,
                'prerequisites' => $course->pivot->prerequisites ?? null,
            ]);

        $items = $courseProgresses->map(function (LearningPathCourseProgress $progress) use ($pivotDataByCourseId) {
            $pivotData = $pivotDataByCourseId->get($progress->course_id, []);

            return [
                'course_id' => $progress->course_id,
                'course_title' => $progress->course->title ?? 'Unknown',
                'status' => (string) $progress->state,
                'position' => $progress->position,
                'is_required' => $pivotData['is_required'] ?? true,
                'completion_percentage' => $progress->courseEnrollment->progress_percentage ?? 0,
                'min_required_percentage' => $pivotData['min_required_percentage'] ?? null,
                'prerequisites' => $pivotData['prerequisites'] ?? null,
                'lock_reason' => null,
                'unlocked_at' => $progress->unlocked_at?->toIso8601String(),
                'started_at' => $progress->started_at?->toIso8601String(),
                'completed_at' => $progress->completed_at?->toIso8601String(),
                'enrollment_id' => $progress->learning_path_enrollment_id,
            ];
        })->all();

        $totalCourses = $courseProgresses->count();
        $completedCourses = $courseProgresses->filter(fn ($p) => $p->isCompleted());
        $inProgressCourses = $courseProgresses->filter(fn ($p) => $p->isInProgress());
        $lockedCourses = $courseProgresses->filter(fn ($p) => $p->isLocked());
        $availableCourses = $courseProgresses->filter(fn ($p) => $p->isAvailable());

        // Calculate required course stats
        $requiredCourseIds = $pivotDataByCourseId
            ->filter(fn ($data) => $data['is_required'] === true)
            ->keys()
            ->toArray();

        $requiredCourses = array_filter($items, fn ($c) => in_array($c['course_id'], $requiredCourseIds));
        $completedRequiredCourses = array_filter($requiredCourses, fn ($c) => $c['status'] === 'completed');

        return new PathProgressResult(
            pathEnrollmentId: $enrollment->id,
            overallPercentage: Percentage::fromFraction(count($completedCourses), $totalCourses),
            totalCourses: $totalCourses,
            completedCourses: count($completedCourses),
            inProgressCourses: count($inProgressCourses),
            lockedCourses: count($lockedCourses),
            availableCourses: count($availableCourses),
            courses: $items,
            isCompleted: count($completedCourses) === $totalCourses && count($completedRequiredCourses) === count($requiredCourseIds),
            requiredCourses: count($requiredCourseIds),
            completedRequiredCourses: count($completedRequiredCourses),
            requiredPercentage: count($requiredCourseIds) > 0 ? (float) (count($completedRequiredCourses) / count($requiredCourseIds)) * 100 : null,
        );
    }

    /**
     * Calculate progress percentage based on required courses only.
     *
     * If no required courses are defined, all courses are considered required.
     */
    public function calculateProgressPercentage(LearningPathEnrollment $enrollment): int
    {
        $stats = $this->getRequiredCourseStats($enrollment);

        if ($stats['total'] === 0) {
            return 0;
        }

        return (int) round(($stats['completed'] / $stats['total']) * 100);
    }

    public function checkPrerequisites(
        LearningPathEnrollment $enrollment,
        Course $course
    ): PrerequisiteCheckResult {
        $evaluator = $this->evaluatorFactory->make($enrollment->learningPath);

        return $evaluator->evaluate($enrollment, $course);
    }

    public function isCourseUnlocked(LearningPathEnrollment $enrollment, Course $course): bool
    {
        /** @var LearningPathCourseProgress|null $progress */
        $progress = $enrollment->courseProgress()
            ->where('course_id', $course->id)
            ->first();

        if (! $progress) {
            return false;
        }

        return ! $progress->state instanceof LockedCourseState;
    }

    public function unlockNextCourses(LearningPathEnrollment $enrollment): array
    {
        $unlockedCourses = [];
        $enrollment->loadMissing('user', 'learningPath');
        $evaluator = $this->evaluatorFactory->make($enrollment->learningPath);

        /** @var \Illuminate\Database\Eloquent\Collection<int, LearningPathCourseProgress> $lockedProgresses */
        $lockedProgresses = $enrollment->courseProgress()
            ->where('state', LockedCourseState::$name)
            ->orderBy('position')
            ->with('course')
            ->get();

        foreach ($lockedProgresses as $progress) {
            /** @var LearningPathCourseProgress $progress */
            $result = $evaluator->evaluate($enrollment, $progress->course);

            if ($result->isMet) {
                $this->unlockCourse($progress, $enrollment);
                $unlockedCourses[] = $progress->course;

                CourseUnlockedInPath::dispatch(
                    $enrollment,
                    $progress->course,
                    $progress->position
                );

                $this->logger->info('learning_path.course.unlocked', [
                    'enrollment_id' => $enrollment->id,
                    'course_id' => $progress->course_id,
                    'position' => $progress->position,
                ]);
            }
        }

        return $unlockedCourses;
    }

    public function onCourseCompleted(
        LearningPathEnrollment $pathEnrollment,
        Enrollment $courseEnrollment
    ): void {
        $this->logger->info('learning_path.course.completed', [
            'path_enrollment_id' => $pathEnrollment->id,
            'course_enrollment_id' => $courseEnrollment->id,
            'course_id' => $courseEnrollment->course_id,
        ]);

        $startTime = microtime(true);
        $previousPercentage = $pathEnrollment->progress_percentage;

        DB::transaction(function () use ($pathEnrollment, $courseEnrollment, $previousPercentage) {
            // Update course progress state to completed
            /** @var LearningPathCourseProgress|null $courseProgress */
            $courseProgress = $pathEnrollment->courseProgress()
                ->where('course_id', $courseEnrollment->course_id)
                ->first();

            if ($courseProgress && ! $courseProgress->isCompleted()) {
                $courseProgress->update([
                    'state' => CompletedCourseState::$name,
                    'completed_at' => now(),
                ]);
            }

            // Unlock next courses based on prerequisites
            $this->unlockNextCourses($pathEnrollment);

            // Update overall path progress
            $newPercentage = $this->calculateProgressPercentage($pathEnrollment);
            $pathEnrollment->progress_percentage = $newPercentage;
            $pathEnrollment->save();

            if ($newPercentage !== $previousPercentage) {
                PathProgressUpdated::dispatch(
                    $pathEnrollment,
                    $previousPercentage,
                    $newPercentage,
                    $courseEnrollment->course_id
                );
            }

            // Check if path is now complete
            if ($this->isPathCompleted($pathEnrollment)) {
                app(PathEnrollmentService::class)->complete($pathEnrollment);
            }
        });

        $this->metrics->timing('learning_path.course_completion.processing', microtime(true) - $startTime);
    }

    public function isPathCompleted(LearningPathEnrollment $enrollment): bool
    {
        $stats = $this->getRequiredCourseStats($enrollment);

        // Zero required courses = vacuously complete
        if ($stats['total'] === 0) {
            return true;
        }

        return $stats['completed'] >= $stats['total'];
    }

    /**
     * Get required course completion statistics for a path enrollment.
     *
     * If no courses are explicitly marked as required, all courses are considered required.
     * Uses a single query with conditional aggregation instead of 3-5 separate queries.
     *
     * @return array{total: int, completed: int}
     */
    protected function getRequiredCourseStats(LearningPathEnrollment $enrollment): array
    {
        $stats = DB::table('learning_path_course_progress as cp')
            ->join('learning_path_course as lpc', function ($join) use ($enrollment) {
                $join->on('cp.course_id', '=', 'lpc.course_id')
                    ->where('lpc.learning_path_id', $enrollment->learning_path_id);
            })
            ->where('cp.learning_path_enrollment_id', $enrollment->id)
            ->selectRaw(
                'SUM(CASE WHEN lpc.is_required = 1 THEN 1 ELSE 0 END) as required_total, '.
                'SUM(CASE WHEN lpc.is_required = 1 AND cp.state = ? THEN 1 ELSE 0 END) as required_completed, '.
                'COUNT(*) as all_total, '.
                'SUM(CASE WHEN cp.state = ? THEN 1 ELSE 0 END) as all_completed',
                [CompletedCourseState::$name, CompletedCourseState::$name]
            )
            ->first();

        $hasRequired = ((int) ($stats->required_total ?? 0)) > 0;

        return [
            'total' => $hasRequired ? (int) $stats->required_total : (int) ($stats->all_total ?? 0),
            'completed' => $hasRequired ? (int) $stats->required_completed : (int) ($stats->all_completed ?? 0),
        ];
    }

    public function startCourse(LearningPathEnrollment $enrollment, Course $course): void
    {
        /** @var LearningPathCourseProgress|null $progress */
        $progress = $enrollment->courseProgress()
            ->where('course_id', $course->id)
            ->first();

        if (! $progress) {
            return;
        }

        if ($progress->isAvailable()) {
            $progress->update([
                'state' => InProgressCourseState::$name,
                'started_at' => now(),
            ]);

            $this->logger->info('learning_path.course.started', [
                'enrollment_id' => $enrollment->id,
                'course_id' => $course->id,
            ]);
        }
    }

    protected function unlockCourse(LearningPathCourseProgress $progress, LearningPathEnrollment $enrollment): void
    {
        if (! $progress->isLocked()) {
            return;
        }

        $user = $enrollment->user;
        $course = $progress->course;

        // Create/reuse course enrollment
        $courseEnrollment = $this->ensureCourseEnrollment($user, $course);

        $progress->update([
            'state' => AvailableCourseState::$name,
            'unlocked_at' => now(),
            'course_enrollment_id' => $courseEnrollment->id,
        ]);
    }

    /**
     * Ensure user has an active course enrollment.
     * Reuses existing enrollment or creates new one.
     */
    protected function ensureCourseEnrollment(User $user, Course $course): Enrollment
    {
        // Check if user already has an active enrollment for this course
        $existingEnrollment = $this->enrollmentService->getActiveEnrollment($user, $course);

        if ($existingEnrollment) {
            $this->logger->info('learning_path.course_enrollment.reused_on_unlock', [
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

        $this->logger->info('learning_path.course_enrollment.created_on_unlock', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
        ]);

        return $enrollment;
    }

    /**
     * Clean up orphaned course progress records and recalculate path progress
     * after courses are removed from a learning path.
     *
     * Should be called after sync/detach operations on learning_path_course.
     *
     * @param  array<int>  $removedCourseIds  Course IDs that were removed from the path
     */
    public function onCoursesRemovedFromPath(\App\Models\LearningPath $path, array $removedCourseIds): void
    {
        if (empty($removedCourseIds)) {
            return;
        }

        $this->logger->info('learning_path.courses.removed', [
            'learning_path_id' => $path->id,
            'removed_course_ids' => $removedCourseIds,
        ]);

        // Delete orphaned course progress records for all active enrollments
        LearningPathCourseProgress::query()
            ->whereIn('course_id', $removedCourseIds)
            ->whereHas('enrollment', fn ($q) => $q
                ->where('learning_path_id', $path->id)
            )
            ->delete();

        // Recalculate progress for all active enrollments in this path
        $activeEnrollments = LearningPathEnrollment::query()
            ->where('learning_path_id', $path->id)
            ->where('state', 'active')
            ->get();

        foreach ($activeEnrollments as $enrollment) {
            $previousPercentage = $enrollment->progress_percentage;
            $newPercentage = $this->calculateProgressPercentage($enrollment);

            $enrollment->update(['progress_percentage' => $newPercentage]);

            if ($newPercentage !== $previousPercentage) {
                PathProgressUpdated::dispatch(
                    $enrollment,
                    $previousPercentage,
                    $newPercentage,
                    null // No specific course triggered this
                );
            }

            // Check if path is now complete after course removal
            if ($this->isPathCompleted($enrollment)) {
                app(PathEnrollmentService::class)->complete($enrollment);
            }
        }
    }

    /**
     * Validate that a course belongs to the learning path.
     * Throws CourseNotInPathException if not.
     *
     * @throws CourseNotInPathException
     */
    public function validateCourseInPathOrFail(LearningPathEnrollment $enrollment, Course $course): void
    {
        $exists = $enrollment->courseProgress()
            ->where('course_id', $course->id)
            ->exists();

        if (! $exists) {
            throw new CourseNotInPathException(
                courseId: $course->id,
                learningPathId: $enrollment->learning_path_id
            );
        }
    }

    /**
     * Validate that prerequisites are met for a course.
     * Throws PrerequisitesNotMetException if not.
     *
     * @throws CourseNotInPathException
     * @throws PrerequisitesNotMetException
     */
    public function validatePrerequisitesOrFail(LearningPathEnrollment $enrollment, Course $course): void
    {
        // First ensure course is in the path
        $this->validateCourseInPathOrFail($enrollment, $course);

        $result = $this->checkPrerequisites($enrollment, $course);

        if (! $result->isMet) {
            throw new PrerequisitesNotMetException(
                pathEnrollmentId: $enrollment->id,
                courseId: $course->id,
                missingPrerequisites: $result->missingPrerequisites
            );
        }
    }
}
