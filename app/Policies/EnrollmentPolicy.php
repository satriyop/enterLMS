<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * Determine whether the user can view any enrollments.
     * Admins and trainers see all; learners see only their own (handled by query scoping).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the enrollment.
     * Owner or course managers can view.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->isLmsAdmin()) {
            return true;
        }

        if ($user->canManageCourses() && $enrollment->course->user_id === $user->id) {
            return true;
        }

        return $enrollment->user_id === $user->id;
    }

    /**
     * Determine whether the user can drop the enrollment.
     * Only the enrollment owner or an admin can drop.
     * Enrollment must be active.
     */
    public function drop(User $user, Enrollment $enrollment): bool
    {
        if (! $enrollment->isActive()) {
            return false;
        }

        if ($user->isLmsAdmin()) {
            return true;
        }

        return $enrollment->user_id === $user->id;
    }

    /**
     * Determine whether the user can complete the enrollment.
     * Only the system (via services) or admin can mark as complete.
     * Enrollment must be active.
     */
    public function complete(User $user, Enrollment $enrollment): bool
    {
        if (! $enrollment->isActive()) {
            return false;
        }

        return $user->isLmsAdmin();
    }

    /**
     * Determine whether the user can reactivate a dropped enrollment.
     * Owner can re-enroll themselves; admin can reactivate any.
     * Enrollment must be dropped.
     */
    public function reactivate(User $user, Enrollment $enrollment): bool
    {
        if (! $enrollment->isDropped()) {
            return false;
        }

        if ($user->isLmsAdmin()) {
            return true;
        }

        return $enrollment->user_id === $user->id;
    }
}
