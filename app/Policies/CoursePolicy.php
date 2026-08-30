<?php

namespace App\Policies;

use App\Domain\Enrollment\DTOs\EnrollmentContext;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Determine whether the user can view any models.
     * Everyone may list Courses; the query decides what each role actually sees.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * Requires EnrollmentContext to avoid hidden queries in authorization.
     * Controllers must pre-fetch context before calling this policy.
     */
    public function view(User $user, Course $course, EnrollmentContext $context): bool
    {
        // Course managers can view all courses
        if ($user->canManageCourses()) {
            return true;
        }

        // Owner can always view their own course
        if ($course->user_id === $user->id) {
            return true;
        }

        // Enrolled learners can always view their courses (even if draft/under revision)
        if ($context->hasAnyEnrollment) {
            return true;
        }

        // Learners can view published public courses
        if ($course->isPublished() && $course->visibility === 'public') {
            return true;
        }

        // Learners can view published restricted courses if invited
        if ($course->isPublished() && $course->visibility === 'restricted') {
            return $context->hasPendingInvitation;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canManageCourses();
    }

    /**
     * Determine whether the user can update the model.
     *
     * LMS Admin may edit any Course, draft or published.
     *
     * The ownership branch below is unreachable while LMS Admin is the only role
     * that can manage Courses. ADR 007 accepted that; it is kept as the seam where
     * a second authoring role would regain the draft-only restriction.
     */
    public function update(User $user, Course $course): bool
    {
        // LMS Admin can always edit any course (draft or published)
        if ($user->isLmsAdmin()) {
            return true;
        }

        // Seam for a future authoring role: own drafts only. Unreachable today.
        if ($course->user_id === $user->id && $user->canManageCourses()) {
            return $course->isDraft();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Course $course): bool
    {
        // LMS Admin can delete any course
        if ($user->isLmsAdmin()) {
            return true;
        }

        // Owner can only delete draft courses
        return $course->user_id === $user->id && $course->isDraft();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Course $course): bool
    {
        return $user->isLmsAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Course $course): bool
    {
        return $user->isLmsAdmin();
    }

    /**
     * Determine whether the user can publish the course.
     */
    public function publish(User $user, Course $course): bool
    {
        return $user->isLmsAdmin();
    }

    /**
     * Determine whether the user can unpublish the course.
     */
    public function unpublish(User $user, Course $course): bool
    {
        return $user->isLmsAdmin();
    }

    /**
     * Determine whether the user can archive the course.
     */
    public function archive(User $user, Course $course): bool
    {
        return $user->isLmsAdmin();
    }

    /**
     * Determine whether the user can set the course status.
     */
    public function setStatus(User $user, Course $course): bool
    {
        return $user->isLmsAdmin();
    }

    /**
     * Determine whether the user can set the course visibility.
     */
    public function setVisibility(User $user, Course $course): bool
    {
        return $user->isLmsAdmin();
    }

    /**
     * Determine whether the user can enroll in the course.
     *
     * Requires EnrollmentContext to avoid database queries in authorization.
     * Controllers must pre-fetch the context before calling this policy.
     */
    public function enroll(User $user, Course $course, EnrollmentContext $context): bool
    {
        // Can only enroll in published courses
        if (! $course->isPublished()) {
            return false;
        }

        // Can't enroll if already actively enrolled
        if ($context->isActivelyEnrolled) {
            return false;
        }

        // Public courses - anyone can enroll
        if ($course->visibility === 'public') {
            return true;
        }

        // Restricted courses - only if invited
        if ($course->visibility === 'restricted') {
            return $context->hasPendingInvitation;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk enroll learners into the course.
     *
     * Only LMS Admin can bulk enroll users.
     */
    public function bulkEnroll(User $user, Course $course): bool
    {
        if (! $course->isPublished()) {
            return false;
        }

        return $user->isLmsAdmin() || $user->facilitatesCourse($course);
    }
}
