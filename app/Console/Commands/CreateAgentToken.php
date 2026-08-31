<?php

namespace App\Console\Commands;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Agent\Services\AgentTokenService;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CreateAgentToken extends Command
{
    protected $signature = 'agent:token
                            {user : User email or id}
                            {--name=hermes : Token name}
                            {--ability=* : Ability (repeatable). Default: agent:ping only}
                            {--free-flow : Issue full free-flow abilities (after B-013 tools)}
                            {--tutor-read : Issue tutor.read only (Tutor runtime; never bundled with --free-flow)}
                            {--author-read : Issue author.read only (Author Agent; never bundled with --free-flow or --tutor-read)}
                            {--all-abilities : Issue all known agent abilities}
                            {--expires= : Expiry datetime (Y-m-d or ISO8601)}
                            {--revoke= : Revoke token by id instead of creating}';

    protected $description = 'Create or revoke a Sanctum agent token for Hermes/OpenClaw MCP clients';

    public function handle(AgentTokenService $tokens): int
    {
        $user = $this->resolveUser($this->argument('user'));

        if ($user === null) {
            $this->error('User tidak ditemukan.');

            return self::FAILURE;
        }

        if ($revokeId = $this->option('revoke')) {
            $ok = $tokens->revoke($user, (int) $revokeId);
            if (! $ok) {
                $this->error("Token #{$revokeId} tidak ditemukan untuk user ini.");

                return self::FAILURE;
            }

            $this->info("Token #{$revokeId} dicabut untuk {$user->email}.");

            return self::SUCCESS;
        }

        $abilities = $this->resolveAbilities();
        $expiresAt = $this->option('expires')
            ? Carbon::parse((string) $this->option('expires'))
            : null;

        $newToken = $tokens->create(
            $user,
            (string) $this->option('name'),
            $abilities,
            $expiresAt,
        );

        $this->info("Agent token dibuat untuk {$user->email} (id={$user->id}).");
        $this->line('Token id: '.$newToken->accessToken->id);
        $this->line('Abilities: '.implode(', ', $abilities));
        $this->newLine();
        $this->warn('Plain text token (simpan sekarang, tidak bisa dilihat lagi):');
        $this->line($newToken->plainTextToken);

        return self::SUCCESS;
    }

    private function resolveUser(string $identifier): ?User
    {
        if (ctype_digit($identifier)) {
            return User::query()->find((int) $identifier);
        }

        return User::query()->where('email', $identifier)->first();
    }

    /**
     * @return list<string>
     */
    private function resolveAbilities(): array
    {
        if ($this->option('tutor-read')) {
            return AgentAbility::tutorRead();
        }

        if ($this->option('author-read')) {
            return AgentAbility::authorRead();
        }

        if ($this->option('all-abilities')) {
            return AgentAbility::all();
        }

        if ($this->option('free-flow')) {
            return AgentAbility::freeFlow();
        }

        /** @var list<string> $abilities */
        $abilities = $this->option('ability');

        if ($abilities === []) {
            return AgentAbility::defaults();
        }

        return $abilities;
    }
}
