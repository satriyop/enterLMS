<?php

namespace App\Mcp\Concerns;

use App\Domain\Agent\Services\AgentActionLogger;
use App\Models\AgentActionLog;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Throwable;

trait AuditsAgentToolCalls
{
    protected function requireAbility(Request $request, string $ability): ?Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            $this->logger()->log(
                tool: $this->name(),
                status: AgentActionLog::STATUS_DENIED,
                errorMessage: 'Unauthenticated',
            );

            return Response::error('Autentikasi diperlukan. Gunakan Bearer token Sanctum.');
        }

        if (! $user->tokenCan($ability)) {
            $this->logger()->log(
                tool: $this->name(),
                status: AgentActionLog::STATUS_DENIED,
                user: $user,
                arguments: $request->all(),
                errorMessage: "Missing ability: {$ability}",
            );

            return Response::error("Token tidak memiliki ability '{$ability}'.");
        }

        return null;
    }

    /**
     * Compliance tools: Sanctum ability + role (compliance_officer / auditor / lms_admin).
     */
    protected function requireComplianceAccess(Request $request, string $ability): ?Response
    {
        if ($denied = $this->requireAbility($request, $ability)) {
            return $denied;
        }

        /** @var User $user */
        $user = $request->user();

        if (! $user->canViewCompliance()) {
            $this->logger()->log(
                tool: $this->name(),
                status: AgentActionLog::STATUS_DENIED,
                user: $user,
                arguments: $request->all(),
                errorMessage: 'Role cannot view compliance',
            );

            return Response::error(
                'Akses compliance ditolak. Butuh role compliance_officer, auditor, atau lms_admin. code=role_forbidden'
            );
        }

        return null;
    }

    /**
     * @param  callable(): (Response|ResponseFactory)  $callback
     */
    protected function runAudited(Request $request, callable $callback): Response|ResponseFactory
    {
        $user = $request->user() instanceof User ? $request->user() : null;
        $started = hrtime(true);

        try {
            $response = $callback();
            $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);

            $this->logger()->log(
                tool: $this->name(),
                status: AgentActionLog::STATUS_SUCCESS,
                user: $user,
                arguments: $request->all(),
                durationMs: $durationMs,
            );

            return $response;
        } catch (Throwable $e) {
            $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);

            $this->logger()->log(
                tool: $this->name(),
                status: AgentActionLog::STATUS_ERROR,
                user: $user,
                arguments: $request->all(),
                errorMessage: $e->getMessage(),
                durationMs: $durationMs,
            );

            return Response::error($e->getMessage());
        }
    }

    protected function logger(): AgentActionLogger
    {
        return app(AgentActionLogger::class);
    }
}
