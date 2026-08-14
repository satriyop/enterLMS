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
        // LMS Admin can view any Learning Path
        if ($user->isLmsAdmin()) {
            return true;
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
        // LMS Admin can update any Learning Path
        if ($user->isLmsAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the learning path.
     */
    public function delete(User $user, LearningPath $learningPath): bool
    {
        // LMS Admin can delete any Learning Path
        if ($user->isLmsAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can publish/unpublish the learning path.
     */
    public function publish(User $user, LearningPath $learningPath): bool
    {
        // LMS Admin can publish any Learning Path
        if ($user->isLmsAdmin()) {
            return true;
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
