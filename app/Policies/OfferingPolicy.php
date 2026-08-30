<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Offering;
use App\Models\User;

class OfferingPolicy
{
    public function viewAny(User $user, Course $course): bool
    {
        if ($user->canManageCourses()) {
            return true;
        }

        return $course->offerings()
            ->where('facilitator_id', $user->id)
            ->exists();
    }

    public function view(User $user, Offering $offering): bool
    {
        if ($user->canManageCourses()) {
            return true;
        }

        return $offering->facilitator_id === $user->id;
    }

    public function create(User $user, Course $course): bool
    {
        return $user->canManageCourses();
    }

    public function update(User $user, Offering $offering): bool
    {
        return $user->canManageCourses();
    }

    public function delete(User $user, Offering $offering): bool
    {
        if ($offering->is_default) {
            return false;
        }

        return $user->canManageCourses();
    }
}
