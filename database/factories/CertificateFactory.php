<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        $user = User::factory()->learner();
        $course = Course::factory()->published();

        return [
            'certificate_number' => Certificate::generateCertificateNumber(),
            'type' => Certificate::TYPE_COURSE_COMPLETION,
            'status' => Certificate::STATUS_ACTIVE,
            'certificable_type' => Course::class,
            'certificable_id' => $course,
            'user_id' => $user,
            'enrollment_id' => null,
            'recipient_name' => $this->faker->name(),
            'certificable_title' => $this->faker->sentence(3),
            'grade' => $this->faker->optional()->randomFloat(2, 60, 100),
            'progress_percentage' => 100,
            'issued_at' => now(),
            'expires_at' => null,
            'issued_by' => null,
            'verification_code' => Certificate::generateVerificationCode(),
        ];
    }

    /**
     * Certificate for a course completion.
     */
    public function courseCompletion(): static
    {
        return $this->state(fn () => [
            'type' => Certificate::TYPE_COURSE_COMPLETION,
        ]);
    }

    /**
     * Certificate for assessment pass.
     */
    public function assessmentPass(): static
    {
        return $this->state(fn () => [
            'type' => Certificate::TYPE_ASSESSMENT_PASS,
        ]);
    }

    /**
     * Certificate for learning path completion.
     */
    public function learningPathCompletion(): static
    {
        return $this->state(fn () => [
            'type' => Certificate::TYPE_LEARNING_PATH_COMPLETION,
        ]);
    }

    /**
     * Active certificate.
     */
    public function active(): static
    {
        return $this->state(fn () => [
            'status' => Certificate::STATUS_ACTIVE,
        ]);
    }

    /**
     * Revoked certificate.
     */
    public function revoked(): static
    {
        return $this->state(fn () => [
            'status' => Certificate::STATUS_REVOKED,
            'revoked_at' => now(),
            'revoked_by' => User::factory()->lmsAdmin(),
            'revocation_reason' => 'Certificate revoked for testing',
        ]);
    }

    /**
     * Expired certificate.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => Certificate::STATUS_EXPIRED,
            'expires_at' => now()->subDays(30),
        ]);
    }

    /**
     * Certificate with expiration date.
     */
    public function withExpiration(int $days = 365): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->addDays($days),
        ]);
    }

    /**
     * Certificate for a specific enrollment.
     */
    public function forEnrollment(Enrollment $enrollment): static
    {
        return $this->state(fn () => [
            'user_id' => $enrollment->user_id,
            'certificable_type' => Course::class,
            'certificable_id' => $enrollment->course_id,
            'enrollment_id' => $enrollment->id,
            'recipient_name' => $enrollment->user->name,
            'certificable_title' => $enrollment->course->title,
            'progress_percentage' => $enrollment->progress_percentage,
        ]);
    }
}
