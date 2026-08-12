<?php

namespace App\Domain\Certificate\Services;

use App\Domain\Certificate\Events\CertificateIssued;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

/**
 * Certificate generation and management service.
 */
class CertificateService
{
    /**
     * Issue a course completion certificate.
     */
    public function issueCourseCompletionCertificate(
        Enrollment $enrollment,
        ?User $issuedBy = null
    ): Certificate {
        // Check if certificate already exists
        $existingCertificate = Certificate::query()
            ->where('user_id', $enrollment->user_id)
            ->where('certificable_type', Course::class)
            ->where('certificable_id', $enrollment->course_id)
            ->where('type', Certificate::TYPE_COURSE_COMPLETION)
            ->active()
            ->first();

        if ($existingCertificate) {
            return $existingCertificate;
        }

        return DB::transaction(function () use ($enrollment, $issuedBy) {
            $course = $enrollment->course;
            $user = $enrollment->user;

            $certificate = Certificate::create([
                'certificate_number' => Certificate::generateCertificateNumber(),
                'type' => Certificate::TYPE_COURSE_COMPLETION,
                'status' => Certificate::STATUS_ACTIVE,
                'certificable_type' => Course::class,
                'certificable_id' => $course->id,
                'user_id' => $user->id,
                'enrollment_id' => $enrollment->id,
                'recipient_name' => $user->name,
                'certificable_title' => $course->title,
                'progress_percentage' => $enrollment->progress_percentage,
                'issued_at' => now(),
                'issued_by' => $issuedBy?->id,
                'verification_code' => Certificate::generateVerificationCode(),
            ]);

            CertificateIssued::dispatch($certificate);

            return $certificate;
        });
    }

    /**
     * Generate PDF for a certificate.
     */
    public function generatePdf(Certificate $certificate): Response
    {
        $data = $this->prepareCertificateData($certificate);

        $pdf = Pdf::loadView('pdf.certificate', $data);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download($this->getPdfFilename($certificate));
    }

    /**
     * Stream PDF to browser (inline view).
     */
    public function streamPdf(Certificate $certificate): Response
    {
        $data = $this->prepareCertificateData($certificate);

        $pdf = Pdf::loadView('pdf.certificate', $data);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream($this->getPdfFilename($certificate));
    }

    /**
     * Verify a certificate by verification code.
     *
     * @return array{valid: bool, certificate: Certificate|null, message: string}
     */
    public function verify(string $verificationCode): array
    {
        $certificate = Certificate::where('verification_code', $verificationCode)->first();

        if (! $certificate) {
            return [
                'valid' => false,
                'certificate' => null,
                'message' => 'Sertifikat tidak ditemukan.',
            ];
        }

        if ($certificate->isRevoked()) {
            return [
                'valid' => false,
                'certificate' => $certificate,
                'message' => 'Sertifikat ini telah dicabut.',
            ];
        }

        if ($certificate->isExpired()) {
            return [
                'valid' => false,
                'certificate' => $certificate,
                'message' => 'Sertifikat ini telah kedaluwarsa.',
            ];
        }

        return [
            'valid' => true,
            'certificate' => $certificate,
            'message' => 'Sertifikat ini valid dan aktif.',
        ];
    }

    /**
     * Revoke a certificate.
     */
    public function revoke(Certificate $certificate, User $revokedBy, string $reason): Certificate
    {
        return $certificate->revoke($revokedBy, $reason);
    }

    /**
     * Get certificates for a user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Certificate>
     */
    public function getUserCertificates(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return Certificate::query()
            ->forUser($user)
            ->active()
            ->orderByDesc('issued_at')
            ->get();
    }

    /**
     * Check if user has certificate for a course.
     */
    public function hasCertificateForCourse(User $user, Course $course): bool
    {
        return Certificate::query()
            ->where('user_id', $user->id)
            ->where('certificable_type', Course::class)
            ->where('certificable_id', $course->id)
            ->active()
            ->exists();
    }

    /**
     * Get certificate for enrollment if exists.
     */
    public function getCertificateForEnrollment(Enrollment $enrollment): ?Certificate
    {
        return Certificate::query()
            ->where('enrollment_id', $enrollment->id)
            ->active()
            ->first();
    }

    /**
     * Prepare data for certificate PDF view.
     *
     * @return array<string, mixed>
     */
    protected function prepareCertificateData(Certificate $certificate): array
    {
        return [
            'certificate' => $certificate,
            'recipient_name' => $certificate->recipient_name,
            'course_title' => $certificate->certificable_title,
            'certificate_number' => $certificate->certificate_number,
            'issued_at' => $certificate->issued_at->translatedFormat('d F Y'),
            'verification_code' => $certificate->verification_code,
            'verification_url' => $certificate->getVerificationUrl(),
            'grade' => $certificate->grade,
            'progress_percentage' => $certificate->progress_percentage,
        ];
    }

    /**
     * Get filename for PDF download.
     */
    protected function getPdfFilename(Certificate $certificate): string
    {
        $sanitizedName = preg_replace('/[^a-zA-Z0-9\-]/', '_', $certificate->recipient_name);

        return "sertifikat_{$certificate->certificate_number}_{$sanitizedName}.pdf";
    }
}
