<?php

namespace App\Domain\Enrollment\Services;

use App\Domain\Enrollment\Events\UserEnrolled;
use App\Domain\Enrollment\Exceptions\AlreadyEnrolledException;
use App\Domain\Enrollment\Exceptions\CourseNotPublishedException;
use App\Domain\Enrollment\Exceptions\EnrollmentCapacityExceededException;
use App\Domain\Enrollment\Exceptions\EnrollmentDeadlinePassedException;
use App\Domain\Enrollment\Exceptions\PaymentRequiredException;
use App\Domain\Enrollment\States\ActiveState;
use App\Domain\Enrollment\States\CompletedState;
use App\Domain\Enrollment\States\DroppedState;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\Helpers\DatabaseHelper;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Enrollment orchestration service.
 *
 * Handles enrollment creation/validation. State transitions (drop, complete, reactivate)
 * are now owned by the Enrollment model itself.
 */
class EnrollmentService
{
    /**
     * Enroll a user in a course.
     *
     * Returns the Enrollment model directly. Controllers transform using EnrollmentData.
     */
    public function enroll(
        int $userId,
        int $courseId,
        ?int $invitedBy = null,
        ?DateTimeInterface $enrolledAt = null
    ): Enrollment {
        $user = User::findOrFail($userId);
        $course = Course::findOrFail($courseId);

        $this->validateEnrollment($user, $course);

        // Check for existing dropped enrollment (re-enrollment case)
        $droppedEnrollment = $this->getDroppedEnrollment($user, $course);

        if ($droppedEnrollment) {
            // Capacity still applies when reactivating a dropped seat
            return DB::transaction(function () use ($courseId, $droppedEnrollment, $invitedBy) {
                $lockedCourse = Course::query()->whereKey($courseId)->lockForUpdate()->firstOrFail();
                $this->assertCapacityAvailable($lockedCourse);

                return $droppedEnrollment->reactivate(
                    preserveProgress: true,
                    invitedBy: $invitedBy
                );
            });
        }

        try {
            return DB::transaction(function () use ($userId, $courseId, $invitedBy, $enrolledAt) {
                // Serialize capacity checks across concurrent enrolls for the same course.
                $lockedCourse = Course::query()->whereKey($courseId)->lockForUpdate()->firstOrFail();
                $this->assertCapacityAvailable($lockedCourse);

                $enrollment = Enrollment::create([
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'status' => ActiveState::$name,
                    'progress_percentage' => 0,
                    'enrolled_at' => $enrolledAt ?? now(),
                    'invited_by' => $invitedBy,
                ]);

                UserEnrolled::dispatch($enrollment);

                return $enrollment;
            });
        } catch (QueryException $e) {
            if (DatabaseHelper::isDuplicateKeyException($e)) {
                throw new AlreadyEnrolledException($userId, $courseId);
            }
            throw $e;
        }
    }

    /**
     * Re-check capacity after locking the course row.
     */
    protected function assertCapacityAvailable(Course $course): void
    {
        if ($course->max_enrollments === null) {
            return;
        }

        // Fresh count under the course lock (same transaction).
        $activeCount = Enrollment::query()
            ->where('course_id', $course->id)
            ->whereIn('status', [ActiveState::$name, CompletedState::$name])
            ->count();

        if ($activeCount >= $course->max_enrollments) {
            throw new EnrollmentCapacityExceededException($course->id, $course->max_enrollments);
        }
    }

    public function canEnroll(User $user, Course $course): bool
    {
        // Already actively enrolled?
        if ($this->getActiveEnrollment($user, $course)) {
            return false;
        }

        // Course published?
        if (! $course->isPublished()) {
            return false;
        }

        // Enrollment deadline passed?
        if ($course->isEnrollmentDeadlinePassed()) {
            return false;
        }

        // Course at capacity?
        if ($course->isAtCapacity()) {
            return false;
        }

        // Course visible to user?
        if ($course->visibility === 'hidden') {
            return false;
        }

        // Restricted course - check invitation or if user was previously enrolled (dropped)
        if ($course->visibility === 'restricted') {
            // Allow re-enrollment for previously dropped users
            if ($this->getDroppedEnrollment($user, $course)) {
                return true;
            }

            return $this->hasValidInvitation($user, $course);
        }

        return true;
    }

    public function getActiveEnrollment(User $user, Course $course): ?Enrollment
    {
        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [ActiveState::$name, CompletedState::$name])
            ->first();
    }

    public function getDroppedEnrollment(User $user, Course $course): ?Enrollment
    {
        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', DroppedState::$name)
            ->first();
    }

    protected function validateEnrollment(User $user, Course $course): void
    {
        $existingEnrollment = $this->getActiveEnrollment($user, $course);
        if ($existingEnrollment) {
            throw new AlreadyEnrolledException($user->id, $course->id);
        }

        if (! $course->isPublished()) {
            throw new CourseNotPublishedException($course->id);
        }

        if ($course->isEnrollmentDeadlinePassed()) {
            throw new EnrollmentDeadlinePassedException($course->id, $course->enrollment_deadline);
        }

        if ($course->isAtCapacity()) {
            throw new EnrollmentCapacityExceededException($course->id, $course->max_enrollments);
        }

        // A priced Course has no self-serve path: LMS Admin grants Enrollment.
        if ($course->isPaid()) {
            throw new PaymentRequiredException($course->id, $course->price);
        }
    }

    /**
     * Build an enrollment status map for a list of courses.
     *
     * Returns array keyed by course_id with status and progress for a given user.
     * Used by course index/browse pages to show enrollment badges.
     *
     * @param  iterable<Course>  $courses
     * @return array<int, array{status: string, progress_percentage: int|null}>
     */
    public function getEnrollmentMapForCourses(User $user, iterable $courses): array
    {
        $courseIds = collect($courses)->pluck('id')->toArray();

        return Enrollment::query()
            ->where('user_id', $user->id)
            ->whereIn('course_id', $courseIds)
            ->get()
            ->mapWithKeys(fn (Enrollment $e) => [
                $e->course_id => [
                    'status' => $e->status->getValue(),
                    'progress_percentage' => $e->progress_percentage,
                ],
            ])
            ->toArray();
    }

    /**
     * Check if user has a pending (usable) invitation for the course.
     */
    protected function hasValidInvitation(User $user, Course $course): bool
    {
        return $course->invitations()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Bulk enroll multiple users in a course.
     *
     * @param  array<int>  $userIds  Array of user IDs to enroll
     * @param  int  $courseId  Course ID to enroll users in
     * @param  int|null  $enrolledBy  User ID of admin performing the bulk enrollment
     * @return array{success: int, failed: int, skipped: int, errors: array<string>}
     */
    public function bulkEnroll(array $userIds, int $courseId, ?int $enrolledBy = null): array
    {
        $course = Course::findOrFail($courseId);

        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($userIds as $userId) {
            try {
                $user = User::find($userId);

                if (! $user) {
                    $results['errors'][] = "User ID {$userId}: Pengguna tidak ditemukan.";
                    $results['failed']++;

                    continue;
                }

                if (! $user->isLearner()) {
                    $results['errors'][] = "{$user->email}: Hanya learner yang dapat didaftarkan.";
                    $results['skipped']++;

                    continue;
                }

                // Check if already enrolled
                if ($this->getActiveEnrollment($user, $course)) {
                    $results['skipped']++;

                    continue;
                }

                $this->enroll(
                    userId: $userId,
                    courseId: $courseId,
                    invitedBy: $enrolledBy
                );

                $results['success']++;
            } catch (AlreadyEnrolledException) {
                $results['skipped']++;
            } catch (CourseNotPublishedException) {
                $results['errors'][] = 'Kursus belum dipublikasikan.';
                $results['failed']++;
                break; // Stop processing if course is not published
            } catch (EnrollmentDeadlinePassedException) {
                $results['errors'][] = 'Batas waktu pendaftaran sudah lewat.';
                $results['failed']++;
                break; // Stop processing if deadline passed
            } catch (EnrollmentCapacityExceededException) {
                $results['errors'][] = 'Kapasitas pendaftaran sudah penuh.';
                $results['failed']++;
                break; // Stop processing if capacity exceeded
            } catch (PaymentRequiredException) {
                $results['errors'][] = 'Kursus berbayar tidak dapat didaftarkan secara bulk.';
                $results['failed']++;
                break; // Stop processing for paid courses
            } catch (\Throwable $e) {
                $results['errors'][] = "User ID {$userId}: ".$e->getMessage();
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Bulk enroll users from CSV data.
     *
     * @param  array<int, array<int, string>>  $csvData  CSV rows (excluding header)
     * @param  int  $emailIndex  Index of email column
     * @param  int  $courseId  Course ID to enroll users in
     * @param  int|null  $enrolledBy  User ID of admin performing the bulk enrollment
     * @return array{success: int, failed: int, skipped: int, errors: array<string>}
     */
    public function bulkEnrollFromCsv(
        array $csvData,
        int $emailIndex,
        int $courseId,
        ?int $enrolledBy = null
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $course = Course::findOrFail($courseId);

        foreach ($csvData as $rowIndex => $row) {
            $email = trim($row[$emailIndex] ?? '');

            if (empty($email)) {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $results['errors'][] = 'Baris '.($rowIndex + 2).": Email tidak valid ({$email}).";
                $results['failed']++;

                continue;
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $results['errors'][] = 'Baris '.($rowIndex + 2).": Pengguna dengan email {$email} tidak ditemukan.";
                $results['failed']++;

                continue;
            }

            if (! $user->isLearner()) {
                $results['errors'][] = 'Baris '.($rowIndex + 2).": {$email} bukan learner.";
                $results['skipped']++;

                continue;
            }

            try {
                if ($this->getActiveEnrollment($user, $course)) {
                    $results['skipped']++;

                    continue;
                }

                $this->enroll(
                    userId: $user->id,
                    courseId: $courseId,
                    invitedBy: $enrolledBy
                );

                $results['success']++;
            } catch (AlreadyEnrolledException) {
                $results['skipped']++;
            } catch (\Throwable $e) {
                $results['errors'][] = 'Baris '.($rowIndex + 2)." ({$email}): ".$e->getMessage();
                $results['failed']++;
            }
        }

        return $results;
    }
}
