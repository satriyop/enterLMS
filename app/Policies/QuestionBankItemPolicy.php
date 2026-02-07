<?php

namespace App\Policies;

use App\Models\QuestionBankItem;
use App\Models\User;

class QuestionBankItemPolicy
{
    /**
     * Determine whether the user can view any question bank items.
     */
    public function viewAny(User $user): bool
    {
        // Content creators can access the question bank
        return in_array($user->role, ['lms_admin', 'content_manager', 'trainer']);
    }

    /**
     * Determine whether the user can view the question bank item.
     */
    public function view(User $user, QuestionBankItem $item): bool
    {
        // Owner can always view
        if ($item->user_id === $user->id) {
            return true;
        }

        // Public items visible to all content creators
        if ($item->visibility === 'public') {
            return in_array($user->role, ['lms_admin', 'content_manager', 'trainer']);
        }

        // Department items visible to instructors+
        if ($item->visibility === 'department') {
            return in_array($user->role, ['lms_admin', 'content_manager', 'trainer']);
        }

        // Private items only visible to owner (already checked above)
        return false;
    }

    /**
     * Determine whether the user can create question bank items.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['lms_admin', 'content_manager', 'trainer']);
    }

    /**
     * Determine whether the user can update the question bank item.
     */
    public function update(User $user, QuestionBankItem $item): bool
    {
        // LMS Admin can update any item
        if ($user->isLmsAdmin()) {
            return true;
        }

        // Only owner can update their items
        return $item->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the question bank item.
     */
    public function delete(User $user, QuestionBankItem $item): bool
    {
        // LMS Admin can delete any item
        if ($user->isLmsAdmin()) {
            return true;
        }

        // Only owner can delete their items
        return $item->user_id === $user->id;
    }

    /**
     * Determine whether the user can duplicate the question bank item.
     */
    public function duplicate(User $user, QuestionBankItem $item): bool
    {
        // Can duplicate if can view
        return $this->view($user, $item);
    }

    /**
     * Determine whether the user can import the question bank item to an assessment.
     */
    public function import(User $user, QuestionBankItem $item): bool
    {
        // Can import if can view
        return $this->view($user, $item);
    }

    /**
     * Determine whether the user can change visibility.
     */
    public function changeVisibility(User $user, QuestionBankItem $item): bool
    {
        // LMS Admin can change visibility of any item
        if ($user->isLmsAdmin()) {
            return true;
        }

        // Only owner can change visibility
        return $item->user_id === $user->id;
    }
}
