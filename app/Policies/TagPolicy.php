<?php

namespace App\Policies;

use App\Models\User;

class TagPolicy
{
    /**
     * Anyone can view tags (used in course browsing/filtering).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Only LMS Admin can create tags.
     */
    public function create(User $user): bool
    {
        return $user->isLmsAdmin();
    }

    /**
     * Only admins can update tags.
     */
    public function update(User $user): bool
    {
        return $user->isLmsAdmin();
    }

    /**
     * Only admins can delete tags.
     */
    public function delete(User $user): bool
    {
        return $user->isLmsAdmin();
    }
}
