<?php

namespace App\Domain\LearningPath\Strategies;

use App\Domain\LearningPath\Contracts\PrerequisiteEvaluatorContract;
use App\Domain\LearningPath\DTOs\PrerequisiteCheckResult;
use App\Models\Course;
use App\Models\LearningPathCourseProgress;
use App\Models\LearningPathEnrollment;

/**
 * Evaluates prerequisites based on sequential course ordering.
 * All previous courses in the path must be completed before unlocking the next.
 *
 * Respects min_completion_percentage from path course pivot: if set to 80%,
 * reaching 80% progress is sufficient even if the course isn't fully completed.
 */
class SequentialPrerequisiteEvaluator implements PrerequisiteEvaluatorContract
{
    public function evaluate(
        LearningPathEnrollment $enrollment,
        Course $course
    ): PrerequisiteCheckResult {
        $path = $enrollment->learningPath;

        // Get the course position in the path
        /** @var Course|null $pathCourse */
        $pathCourse = $path->courses()
            ->where('course_id', $course->id)
            ->first();

        if (! $pathCourse) {
            return PrerequisiteCheckResult::notMet(
                [],
                'Course not found in path'
            );
        }

        /** @var object{position: int} $pivot */
        $pivot = $pathCourse->pivot;
        $position = $pivot->position;

        // First course is always available
        if ($position === 1) {
            return PrerequisiteCheckResult::met();
        }

        // Get all courses that should be completed before this one
        /** @var \Illuminate\Database\Eloquent\Collection<int, Course> $previousCourses */
        $previousCourses = $path->courses()
            ->wherePivot('position', '<', $position)
            ->orderBy('learning_path_course.position')
            ->get();

        /** @var array<int, array{id: int, title: string}> $missingPrerequisites */
        $missingPrerequisites = [];

        foreach ($previousCourses as $previousCourse) {
            /** @var LearningPathCourseProgress|null $progress */
            $progress = $enrollment->courseProgress()
                ->where('course_id', $previousCourse->id)
                ->first();

            if (! $this->meetsRequirement($progress, $previousCourse)) {
                $missingPrerequisites[] = [
                    'id' => $previousCourse->id,
                    'title' => $previousCourse->title,
                ];
            }
        }

        if (empty($missingPrerequisites)) {
            return PrerequisiteCheckResult::met();
        }

        return PrerequisiteCheckResult::notMet(
            $missingPrerequisites,
            'Previous courses must be completed'
        );
    }

    /**
     * Check if the course progress meets the completion requirement.
     *
     * A course is considered complete for prerequisite purposes when:
     * 1. The course is fully completed (state = completed), OR
     * 2. The enrollment progress >= min_completion_percentage from pivot
     */
    protected function meetsRequirement(?LearningPathCourseProgress $progress, Course $previousCourse): bool
    {
        if (! $progress) {
            return false;
        }

        // Fully completed courses always meet the requirement
        if ($progress->isCompleted()) {
            return true;
        }

        // Check if min_completion_percentage allows partial completion
        /** @var object{min_completion_percentage: int|null} $pivot */
        $pivot = $previousCourse->pivot;
        $minRequired = $pivot->min_completion_percentage ?? 100;

        // If 100% is required, must be fully completed (which we already checked)
        if ($minRequired >= 100) {
            return false;
        }

        // Check the underlying enrollment's progress
        $courseEnrollment = $progress->courseEnrollment;
        if (! $courseEnrollment) {
            return false;
        }

        return $courseEnrollment->progress_percentage >= $minRequired;
    }

    public function getName(): string
    {
        return 'sequential';
    }
}
