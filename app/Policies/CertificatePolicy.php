<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    /**
     * Determine whether the user can view any certificates.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the certificate.
     */
    public function view(User $user, Certificate $certificate): bool
    {
        // Owner can always view
        if ($certificate->user_id === $user->id) {
            return true;
        }

        // LMS Admin can view all
        return $user->isLmsAdmin();
    }

    /**
     * Determine whether the user can download the certificate.
     */
    public function download(User $user, Certificate $certificate): bool
    {
        // Only active certificates can be downloaded
        if (! $certificate->isActive()) {
            return false;
        }

        // Owner can download
        if ($certificate->user_id === $user->id) {
            return true;
        }

        // LMS Admin can download for compliance purposes
        return $user->isLmsAdmin();
    }

    /**
     * Determine whether the user can revoke the certificate.
     */
    public function revoke(User $user, Certificate $certificate): bool
    {
        // Only LMS Admin can revoke certificates
        return $user->isLmsAdmin();
    }
}
