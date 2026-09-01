<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Reading the Conversations on a Course is not the same permission as
     * reading one: a Facilitator qualifies through an Offering grant, so they
     * see the list only if they hold one on this Course.
     */
    public function viewAny(User $user, Course $course): bool
    {
        if ($user->canManageCourses()) {
            return true;
        }

        return $course->offerings()
            ->where('facilitator_id', $user->id)
            ->exists();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        $conversation->loadMissing('enrollment');

        if ($conversation->enrollment->user_id === $user->id) {
            return true;
        }

        if ($user->canManageCourses()) {
            return true;
        }

        return $user->facilitatesEnrollment($conversation->enrollment);
    }

    public function addTurn(User $user, Enrollment $enrollment, Lesson $lesson): bool
    {
        if ($enrollment->user_id !== $user->id) {
            return false;
        }

        if (! $enrollment->isActive() && ! $enrollment->isCompleted()) {
            return false;
        }

        $course = $lesson->section->course;

        return $enrollment->course_id === $course->id;
    }
}
