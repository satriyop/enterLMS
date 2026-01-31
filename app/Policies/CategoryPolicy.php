<?php

namespace App\Policies;

use App\Models\User;

class CategoryPolicy
{
    /**
     * Anyone can view categories (used in course browsing).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Only admins and content managers can create categories.
     */
    public function create(User $user): bool
    {
        return $user->isLmsAdmin() || $user->isContentManager();
    }

    /**
     * Only admins can update categories.
     */
    public function update(User $user): bool
    {
        return $user->isLmsAdmin();
    }

    /**
     * Only admins can delete categories.
     */
    public function delete(User $user): bool
    {
        return $user->isLmsAdmin();
    }
}
