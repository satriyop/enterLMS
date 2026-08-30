<?php

namespace App\Http\Controllers;

use App\Domain\Enrollment\Exceptions\NamedOfferingRequiredException;
use App\Domain\Shared\Academy;
use App\Http\Requests\Enrollment\BulkEnrollmentRequest;
use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\Offering;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

class EnrollmentController extends Controller
{
    public function __construct(
        protected \App\Domain\Enrollment\Services\EnrollmentService $enrollmentService,
        protected \App\Domain\Course\Services\InvitationAcceptanceService $invitationAcceptanceService
    ) {}

    /**
     * Enroll the authenticated user in a course.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        $context = \App\Domain\Enrollment\DTOs\EnrollmentContext::for($user, $course);

        Gate::authorize('enroll', [$course, $context]);

        try {
            DB::transaction(function () use ($user, $course, $request) {
                $invitation = CourseInvitation::query()
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->where('status', 'pending')
                    ->notExpired()
                    ->lockForUpdate()
                    ->first();

                $offeringId = Academy::enabled('offerings')
                    ? ($request->integer('offering_id') ?: null)
                    : null;

                $this->enrollmentService->enroll(
                    userId: $user->id,
                    courseId: $course->id,
                    invitedBy: $invitation?->invited_by,
                    offeringId: $offeringId,
                );

                if ($invitation) {
                    $invitation->update([
                        'status' => 'accepted',
                        'responded_at' => now(),
                    ]);
                }
            });

            return redirect()
                ->route('courses.show', $course)
                ->with('success', 'Berhasil mendaftar ke kursus.');

        } catch (\App\Domain\Enrollment\Exceptions\AlreadyEnrolledException) {
            return back()->with('error', 'Anda sudah terdaftar di kursus ini.');
        } catch (\App\Domain\Enrollment\Exceptions\CourseNotPublishedException) {
            return back()->with('error', 'Kursus ini belum dipublikasikan.');
        } catch (\App\Domain\Enrollment\Exceptions\EnrollmentDeadlinePassedException) {
            return back()->with('error', 'Batas waktu pendaftaran kursus ini sudah berakhir.');
        } catch (\App\Domain\Enrollment\Exceptions\EnrollmentCapacityExceededException) {
            return back()->with('error', 'Kuota pendaftaran kursus ini sudah penuh.');
        } catch (\App\Domain\Enrollment\Exceptions\PaymentRequiredException) {
            return back()->with('error', 'Kursus ini berbayar. Silakan selesaikan pembayaran terlebih dahulu.');
        } catch (\App\Domain\Enrollment\Exceptions\OfferingClosedForEnrollmentException) {
            return back()->with('error', 'Offering ini sedang tidak menerima pendaftaran.');
        } catch (NamedOfferingRequiredException) {
            return back()->with('error', 'Tidak dapat mendaftarkan ke '.Academy::label('offering').' default jika '.Academy::label('offering').' bernama sudah ada.');
        }
    }

    /**
     * Bulk enroll multiple users in a course.
     */
    public function bulkEnroll(BulkEnrollmentRequest $request, Course $course): RedirectResponse
    {
        $actor = $request->user();
        $enrolledBy = $actor->id;
        $offeringId = Academy::enabled('offerings')
            ? ($request->integer('offering_id') ?: null)
            : null;

        if ($offeringId) {
            $offering = Offering::query()
                ->where('course_id', $course->id)
                ->whereKey($offeringId)
                ->firstOrFail();

            Gate::authorize('grantEnrollment', $offering);
        }

        // Handle CSV file upload
        if ($request->hasFile('file')) {
            $results = $this->processCsvEnrollment($request->file('file'), $course->id, $actor, $offeringId);
        } else {
            // Handle user_ids array
            $results = $this->enrollmentService->bulkEnroll(
                userIds: $request->input('user_ids'),
                courseId: $course->id,
                enrolledBy: $enrolledBy,
                offeringId: $offeringId,
            );
        }

        Log::info('Bulk enrollment completed', [
            'course_id' => $course->id,
            'enrolled_by' => $enrolledBy,
            'results' => $results,
        ]);

        return $this->buildBulkEnrollmentResponse($results, $course);
    }

    /**
     * Process CSV file for bulk enrollment.
     *
     * @return array{success: int, failed: int, skipped: int, errors: array<string>}
     */
    protected function processCsvEnrollment(
        \Illuminate\Http\UploadedFile $file,
        int $courseId,
        User $actor,
        ?int $offeringId = null,
    ): array {
        try {
            $csv = Reader::createFromPath($file->getPathname(), 'r');
            $csv->setHeaderOffset(0);

            $records = iterator_to_array($csv->getRecords());
            $headers = $csv->getHeader();

            $csvData = [];
            foreach ($records as $record) {
                $csvData[] = array_values($record);
            }

            $nimIndex = $this->findCsvColumnIndex($headers, 'nim');
            $offeringCodeIndex = $this->findCsvColumnIndex($headers, 'offering_code');

            if ($nimIndex !== null && $offeringCodeIndex !== null) {
                return $this->enrollmentService->bulkEnrollFromNimRoster(
                    csvData: $csvData,
                    nimIndex: $nimIndex,
                    offeringCodeIndex: $offeringCodeIndex,
                    courseId: $courseId,
                    enrolledBy: $actor->id,
                    actor: $actor,
                );
            }

            $emailIndex = $this->findCsvColumnIndex($headers, 'email');

            if ($emailIndex === null) {
                return [
                    'success' => 0,
                    'failed' => 1,
                    'skipped' => 0,
                    'errors' => ['File CSV harus memiliki kolom "email", atau "nim" dan "offering_code".'],
                ];
            }

            return $this->enrollmentService->bulkEnrollFromCsv(
                csvData: $csvData,
                emailIndex: $emailIndex,
                courseId: $courseId,
                enrolledBy: $actor->id,
                offeringId: $offeringId,
            );

        } catch (\Exception $e) {
            Log::error('CSV parsing error in bulk enrollment', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
            ]);

            return [
                'success' => 0,
                'failed' => 1,
                'skipped' => 0,
                'errors' => ['Gagal memproses file CSV: '.$e->getMessage()],
            ];
        }
    }

    /**
     * @param  array<int, string>  $headers
     */
    protected function findCsvColumnIndex(array $headers, string $name): ?int
    {
        foreach ($headers as $index => $header) {
            if (strtolower(trim($header)) === $name) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Build redirect response for bulk enrollment.
     *
     * @param  array{success: int, failed: int, skipped: int, errors: array<string>}  $results
     */
    protected function buildBulkEnrollmentResponse(array $results, Course $course): RedirectResponse
    {
        $messages = [];

        if ($results['success'] > 0) {
            $messages[] = "{$results['success']} pengguna berhasil didaftarkan.";
        }

        if ($results['skipped'] > 0) {
            $messages[] = "{$results['skipped']} pengguna dilewati (sudah terdaftar atau bukan learner).";
        }

        if ($results['failed'] > 0) {
            $messages[] = "{$results['failed']} pengguna gagal didaftarkan.";
        }

        $type = $results['failed'] > 0 && $results['success'] === 0 ? 'error' : 'success';
        $message = implode(' ', $messages);

        if (! empty($results['errors'])) {
            $message .= ' Detail: '.implode(', ', array_slice($results['errors'], 0, 3));
            if (count($results['errors']) > 3) {
                $message .= ' ...dan '.(count($results['errors']) - 3).' error lainnya.';
            }
        }

        return redirect()
            ->route('courses.show', $course)
            ->with($type, $message);
    }

    /**
     * Re-enroll a user who previously dropped a course.
     */
    public function reenroll(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        $droppedEnrollment = $this->enrollmentService->getDroppedEnrollment($user, $course);

        if (! $droppedEnrollment) {
            return back()->with('error', 'Tidak ada pendaftaran yang dibatalkan untuk kursus ini.');
        }

        Gate::authorize('reactivate', $droppedEnrollment);

        $preserveProgress = $request->boolean('preserve_progress', true);

        $droppedEnrollment->reactivate($preserveProgress);

        $message = $preserveProgress
            ? 'Berhasil melanjutkan kursus. Progress sebelumnya telah dipulihkan.'
            : 'Berhasil mendaftar ulang. Progress telah direset.';

        return redirect()
            ->route('courses.show', $course)
            ->with('success', $message);
    }

    /**
     * Remove the authenticated user from a course.
     */
    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        $enrollment = Enrollment::query()
            ->forUserAndCourse($user, $course)
            ->firstOrFail();

        Gate::authorize('drop', $enrollment);

        try {
            $enrollment->drop();

            return redirect()
                ->route('learner.dashboard')
                ->with('success', 'Pendaftaran kursus dibatalkan.');

        } catch (\App\Domain\Shared\Exceptions\InvalidStateTransitionException) {
            return back()->with('error', 'Tidak dapat membatalkan pendaftaran ini.');
        }
    }

    /**
     * Accept a course invitation.
     */
    public function acceptInvitation(Request $request, CourseInvitation $invitation): RedirectResponse
    {
        $user = $request->user();

        if ($invitation->user_id !== $user->id) {
            abort(403);
        }

        try {
            $courseId = $this->invitationAcceptanceService->acceptWithLocking($user, $invitation);

            return redirect()
                ->route('courses.show', $courseId)
                ->with('success', 'Undangan diterima. Selamat belajar!');

        } catch (\App\Domain\Course\Exceptions\InvitationNotPendingException) {
            return back()->with('error', 'Undangan ini sudah tidak berlaku.');
        } catch (\App\Domain\Course\Exceptions\InvitationExpiredException) {
            return back()->with('error', 'Undangan ini sudah kadaluarsa.');
        } catch (\App\Domain\Enrollment\Exceptions\AlreadyEnrolledException) {
            return back()->with('error', 'Anda sudah terdaftar di kursus ini.');
        } catch (\App\Domain\Enrollment\Exceptions\CourseNotPublishedException) {
            return back()->with('error', 'Kursus ini belum dipublikasikan.');
        } catch (\App\Domain\Enrollment\Exceptions\EnrollmentDeadlinePassedException) {
            return back()->with('error', 'Batas waktu pendaftaran kursus ini sudah berakhir.');
        } catch (\App\Domain\Enrollment\Exceptions\EnrollmentCapacityExceededException) {
            return back()->with('error', 'Kuota pendaftaran kursus ini sudah penuh.');
        } catch (\App\Domain\Enrollment\Exceptions\PaymentRequiredException) {
            return back()->with('error', 'Kursus ini berbayar. Silakan selesaikan pembayaran terlebih dahulu.');
        }
    }

    /**
     * Decline a course invitation.
     */
    public function declineInvitation(Request $request, CourseInvitation $invitation): RedirectResponse
    {
        $user = $request->user();

        if ($invitation->user_id !== $user->id) {
            abort(403);
        }

        if ($invitation->status !== 'pending') {
            return back()->with('error', 'Undangan ini sudah tidak berlaku.');
        }

        $invitation->update([
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        return redirect()
            ->route('learner.dashboard')
            ->with('success', 'Undangan ditolak.');
    }
}
