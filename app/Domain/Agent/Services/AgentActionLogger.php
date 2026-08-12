<?php

namespace App\Domain\Agent\Services;

use App\Models\AgentActionLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class AgentActionLogger
{
    /**
     * @param  array<string, mixed>|null  $arguments
     * @param  array<string, mixed>|null  $meta
     */
    public function log(
        string $tool,
        string $status,
        ?User $user = null,
        ?array $arguments = null,
        ?string $errorMessage = null,
        ?int $durationMs = null,
        ?array $meta = null,
    ): AgentActionLog {
        $token = $user?->currentAccessToken();

        $log = AgentActionLog::query()->create([
            'user_id' => $user?->id,
            'token_id' => $token?->id,
            'tool' => $tool,
            'status' => $status,
            'arguments' => $this->redactArguments($arguments),
            'error_message' => $errorMessage !== null ? mb_substr($errorMessage, 0, 1000) : null,
            'duration_ms' => $durationMs,
            'meta' => $meta,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent() ? mb_substr((string) request()->userAgent(), 0, 500) : null,
        ]);

        Log::channel('single')->info('agent.tool.'.$status, [
            'tool' => $tool,
            'user_id' => $user?->id,
            'token_id' => $token?->id,
            'duration_ms' => $durationMs,
            'error' => $errorMessage,
        ]);

        return $log;
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @param  array<string, mixed>|null  $arguments
     * @return T
     *
     * @throws Throwable
     */
    public function record(string $tool, ?User $user, ?array $arguments, callable $callback): mixed
    {
        $started = hrtime(true);

        try {
            $result = $callback();
            $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
            $this->log($tool, AgentActionLog::STATUS_SUCCESS, $user, $arguments, null, $durationMs);

            return $result;
        } catch (Throwable $e) {
            $durationMs = (int) ((hrtime(true) - $started) / 1_000_000);
            $this->log($tool, AgentActionLog::STATUS_ERROR, $user, $arguments, $e->getMessage(), $durationMs);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>|null  $arguments
     * @return array<string, mixed>|null
     */
    private function redactArguments(?array $arguments): ?array
    {
        if ($arguments === null) {
            return null;
        }

        $redacted = $arguments;
        foreach (['password', 'token', 'secret', 'authorization'] as $key) {
            if (array_key_exists($key, $redacted)) {
                $redacted[$key] = '[redacted]';
            }
        }

        return $redacted;
    }
}
