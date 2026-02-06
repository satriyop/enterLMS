<?php

namespace App\Http\Controllers;

use App\Domain\Certificate\Services\CertificateService;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService
    ) {}

    /**
     * List user's certificates.
     */
    public function index(Request $request): InertiaResponse
    {
        $certificates = $this->certificateService->getUserCertificates($request->user());

        return Inertia::render('certificates/Index', [
            'certificates' => $certificates,
        ]);
    }

    /**
     * Generate and download certificate for completed enrollment.
     */
    public function download(Request $request, Certificate $certificate): Response
    {
        Gate::authorize('download', $certificate);

        return $this->certificateService->generatePdf($certificate);
    }

    /**
     * Stream certificate PDF for viewing.
     */
    public function stream(Request $request, Certificate $certificate): Response
    {
        Gate::authorize('download', $certificate);

        return $this->certificateService->streamPdf($certificate);
    }

    /**
     * Request certificate for a completed course enrollment.
     */
    public function request(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'Anda tidak terdaftar di kursus ini.');
        }

        if (! $enrollment->isCompleted()) {
            return back()->with('error', 'Anda harus menyelesaikan kursus terlebih dahulu.');
        }

        // Check if certificate already exists
        $existingCertificate = $this->certificateService->getCertificateForEnrollment($enrollment);
        if ($existingCertificate) {
            return redirect()->route('certificates.download', $existingCertificate);
        }

        // Issue new certificate
        $certificate = $this->certificateService->issueCourseCompletionCertificate($enrollment);

        return redirect()
            ->route('certificates.download', $certificate)
            ->with('success', 'Sertifikat berhasil diterbitkan.');
    }

    /**
     * Public certificate verification page.
     */
    public function verifyForm(): InertiaResponse
    {
        return Inertia::render('certificates/Verify');
    }

    /**
     * Verify certificate by code.
     */
    public function verify(Request $request, string $code): InertiaResponse
    {
        $result = $this->certificateService->verify($code);

        return Inertia::render('certificates/VerifyResult', [
            'valid' => $result['valid'],
            'message' => $result['message'],
            'certificate' => $result['certificate'] ? [
                'certificate_number' => $result['certificate']->certificate_number,
                'recipient_name' => $result['certificate']->recipient_name,
                'certificable_title' => $result['certificate']->certificable_title,
                'issued_at' => $result['certificate']->issued_at->format('d M Y'),
                'status' => $result['certificate']->status,
                'grade' => $result['certificate']->grade,
            ] : null,
        ]);
    }
}
