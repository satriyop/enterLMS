<?php

namespace App\Policies;

use App\Models\ScormPackage;
use App\Models\User;

class ScormPackagePolicy
{
    /**
     * Determine whether the user can view any packages.
     */
    public function viewAny(User $user): bool
    {
        return $user->canManageCourses();
    }

    /**
     * Determine whether the user can view the package.
     */
    public function view(User $user, ScormPackage $package): bool
    {
        return $user->canManageCourses() || $package->uploaded_by === $user->id;
    }

    /**
     * Determine whether the user can create packages.
     */
    public function create(User $user): bool
    {
        return $user->canManageCourses();
    }

    /**
     * Determine whether the user can delete the package.
     */
    public function delete(User $user, ScormPackage $package): bool
    {
        return $user->canManageCourses();
    }
}
