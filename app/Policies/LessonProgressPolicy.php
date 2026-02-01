<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

/**
 * Authorization for lesson progress operations.
 * Users must have an active enrollment in the course to update progress.
 */
class LessonProgressPolicy
{
    /**
     * Determine whether the user can update lesson progress for a course.
     * Requires an active enrollment in the course.
     */
    public function update(User $user, Course $course): bool
    {
        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->active()
            ->exists();
    }

    /**
     * Determine whether the user can mark a lesson as complete.
     */
    public function complete(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }
}
