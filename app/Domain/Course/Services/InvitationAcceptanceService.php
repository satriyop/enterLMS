<?php

namespace App\Domain\Course\Services;

use App\Domain\Course\Exceptions\InvitationExpiredException;
use App\Domain\Course\Exceptions\InvitationNotPendingException;
use App\Domain\Enrollment\Services\EnrollmentService;
use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvitationAcceptanceService
{
    public function __construct(
        protected EnrollmentService $enrollmentService
    ) {}

    /**
     * Accept invitation with pessimistic locking.
     *
     * Returns course_id on success.
     *
     * @throws InvitationNotPendingException
     * @throws InvitationExpiredException
     * @throws \App\Domain\Enrollment\Exceptions\AlreadyEnrolledException
     * @throws \App\Domain\Enrollment\Exceptions\CourseNotPublishedException
     */
    public function acceptWithLocking(User $user, CourseInvitation $invitation): int
    {
        return DB::transaction(function () use ($user, $invitation) {
            $lockedInvitation = CourseInvitation::lockForUpdate()
                ->findOrFail($invitation->id);

            if ($lockedInvitation->status !== 'pending') {
                throw new InvitationNotPendingException($lockedInvitation->id);
            }

            if ($lockedInvitation->is_expired) {
                $lockedInvitation->update(['status' => 'expired']);
                throw new InvitationExpiredException($lockedInvitation->id);
            }

            $enrollment = $this->accept($user, $lockedInvitation);

            return $enrollment->course_id;
        });
    }

    /**
     * Accept invitation without locking.
     */
    public function accept(User $user, CourseInvitation $invitation): Enrollment
    {
        $enrollment = $this->enrollmentService->enroll(
            userId: $user->id,
            courseId: $invitation->course_id,
            invitedBy: $invitation->invited_by,
        );

        $invitation->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        return $enrollment;
    }
}
