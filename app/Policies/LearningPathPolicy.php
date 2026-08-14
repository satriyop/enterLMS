<?php

namespace App\Policies;

use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LearningPathPolicy
{
    use HandlesAuthorization;

    /**
     * Authoring list. Learners browse via the learner routes, not this resource.
     */
    public function viewAny(User $user): bool
    {
        return $user->canManageLearningPaths();
    }

    /**
     * Determine whether the user can view the learning path.
     */
    public function view(User $user, LearningPath $learningPath): bool
    {
        // Admins can view any learning path
        if ($user->isLmsAdmin()) {
            return true;
        }

        // Content managers can view learning paths they created
        if ($user->isLmsAdmin()) {
            return $user->id === $learningPath->created_by;
        }

        if ($learningPath->isOpenForSelfEnrollment()) {
            return true;
        }

        if ($learningPath->isPublished() && $learningPath->isRestricted()) {
            return $learningPath->learnerEnrollments()
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create learning paths.
     */
    public function create(User $user): bool
    {
        return $user->canManageLearningPaths();
    }

    /**
     * Determine whether the user can update the learning path.
     */
    public function update(User $user, LearningPath $learningPath): bool
    {
        // Admins can update any learning path
        if ($user->isLmsAdmin()) {
            return true;
        }

        // Content managers can update learning paths they created
        if ($user->isLmsAdmin()) {
            return $user->id === $learningPath->created_by;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the learning path.
     */
    public function delete(User $user, LearningPath $learningPath): bool
    {
        // Admins can delete any learning path
        if ($user->isLmsAdmin()) {
            return true;
        }

        // Content managers can delete learning paths they created
        if ($user->isLmsAdmin()) {
            return $user->id === $learningPath->created_by;
        }

        return false;
    }

    /**
     * Determine whether the user can publish/unpublish the learning path.
     */
    public function publish(User $user, LearningPath $learningPath): bool
    {
        // Admins can publish any learning path
        if ($user->isLmsAdmin()) {
            return true;
        }

        // Content managers can publish learning paths they created
        if ($user->isLmsAdmin()) {
            return $user->id === $learningPath->created_by;
        }

        return false;
    }

    /**
     * Determine whether the user can reorder courses in the learning path.
     */
    public function reorder(User $user, LearningPath $learningPath): bool
    {
        return $this->update($user, $learningPath);
    }
}
