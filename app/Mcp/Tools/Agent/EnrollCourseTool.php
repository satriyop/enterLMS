<?php

namespace App\Mcp\Tools\Agent;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Enrollment\Exceptions\AlreadyEnrolledException;
use App\Domain\Enrollment\Exceptions\CourseNotPublishedException;
use App\Domain\Enrollment\Exceptions\EnrollmentCapacityExceededException;
use App\Domain\Enrollment\Exceptions\EnrollmentDeadlinePassedException;
use App\Domain\Enrollment\Exceptions\PaymentRequiredException;
use App\Domain\Enrollment\Services\EnrollmentService;
use App\Domain\Shared\Exceptions\DomainException;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('enroll-course')]
#[Description('Enroll the token owner in a free published public course. Rejects paid courses when payments are enabled.')]
#[IsIdempotent]
class EnrollCourseTool extends Tool
{
    use AuditsAgentToolCalls;

    public function __construct(
        protected EnrollmentService $enrollmentService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::ENROLLMENT_WRITE)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            /** @var User $user */
            $user = $request->user();

            $validated = $request->validate([
                'course_id' => ['required', 'integer', 'exists:courses,id'],
            ], [
                'course_id.required' => 'course_id wajib diisi.',
            ]);

            $course = Course::query()->findOrFail($validated['course_id']);

            if ($course->isPaid()) {
                return Response::error(
                    'Kursus berbayar tidak dapat didaftarkan lewat agent. code=payment_required'
                );
            }

            if ($course->visibility === 'hidden') {
                return Response::error('Kursus tidak tersedia untuk self-enroll. code=not_visible');
            }

            try {
                $enrollment = $this->enrollmentService->enroll(
                    userId: $user->id,
                    courseId: $course->id,
                );
            } catch (PaymentRequiredException $e) {
                return Response::error('Kursus berbayar. code=payment_required');
            } catch (AlreadyEnrolledException $e) {
                return Response::error('Sudah terdaftar di kursus ini. code=already_enrolled');
            } catch (CourseNotPublishedException $e) {
                return Response::error('Kursus belum dipublikasikan. code=not_published');
            } catch (EnrollmentDeadlinePassedException $e) {
                return Response::error('Batas pendaftaran sudah lewat. code=deadline_passed');
            } catch (EnrollmentCapacityExceededException $e) {
                return Response::error('Kuota pendaftaran penuh. code=capacity_exceeded');
            } catch (DomainException $e) {
                return Response::error($e->getMessage().' code=domain_error');
            }

            return Response::structured([
                'ok' => true,
                'data' => [
                    'enrollment_id' => $enrollment->id,
                    'course_id' => $enrollment->course_id,
                    'status' => $enrollment->status->getValue(),
                    'progress_percentage' => $enrollment->progress_percentage,
                ],
            ]);
        });
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'course_id' => $schema->integer()->description('Course ID to enroll')->required(),
        ];
    }
}
