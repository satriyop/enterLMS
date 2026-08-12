<?php

namespace App\Mcp\Tools\Agent;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Certificate\Services\CertificateService;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-user-training-status')]
#[Description('Summary of enrollments, completions, and certificates for a learner (compliance role-gated).')]
#[IsReadOnly]
#[IsIdempotent]
class GetUserTrainingStatusTool extends Tool
{
    use AuditsAgentToolCalls;

    public function __construct(
        protected CertificateService $certificateService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireComplianceAccess($request, AgentAbility::COMPLIANCE_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
            ], [
                'user_id.required' => 'user_id wajib diisi.',
            ]);

            $subject = User::query()->findOrFail($validated['user_id']);

            $enrollments = Enrollment::query()
                ->where('user_id', $subject->id)
                ->with(['course:id,title,slug'])
                ->orderByDesc('enrolled_at')
                ->get();

            $certificates = $this->certificateService->getUserCertificates($subject);

            return Response::structured([
                'ok' => true,
                'data' => [
                    'user' => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'email' => $subject->email,
                        'role' => $subject->role,
                        // no password / tokens
                    ],
                    'summary' => [
                        'enrollments_total' => $enrollments->count(),
                        'active' => $enrollments->filter(fn (Enrollment $e) => $e->isActive())->count(),
                        'completed' => $enrollments->filter(fn (Enrollment $e) => $e->isCompleted())->count(),
                        'dropped' => $enrollments->filter(fn (Enrollment $e) => $e->isDropped())->count(),
                        'certificates_active' => $certificates->count(),
                    ],
                    'enrollments' => $enrollments->map(fn (Enrollment $e) => [
                        'id' => $e->id,
                        'status' => $e->status->getValue(),
                        'progress_percentage' => $e->progress_percentage,
                        'enrolled_at' => $e->enrolled_at?->toIso8601String(),
                        'completed_at' => $e->completed_at?->toIso8601String(),
                        'course' => $e->course ? [
                            'id' => $e->course->id,
                            'title' => $e->course->title,
                            'slug' => $e->course->slug,
                        ] : null,
                    ])->values()->all(),
                    'certificates' => $certificates->map(fn ($c) => [
                        'id' => $c->id,
                        'certificate_number' => $c->certificate_number,
                        'type' => $c->type,
                        'status' => $c->status,
                        'certificable_title' => $c->certificable_title,
                        'issued_at' => $c->issued_at?->toIso8601String(),
                        'verification_code' => $c->verification_code,
                    ])->values()->all(),
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
            'user_id' => $schema->integer()->description('Learner user id')->required(),
        ];
    }
}
