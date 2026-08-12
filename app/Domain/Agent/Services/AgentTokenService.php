<?php

namespace App\Domain\Agent\Services;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class AgentTokenService
{
    /**
     * @param  list<string>  $abilities
     */
    public function create(
        User $user,
        string $name,
        array $abilities,
        ?Carbon $expiresAt = null,
    ): NewAccessToken {
        $abilities = array_values(array_unique($abilities));

        if ($abilities === []) {
            throw ValidationException::withMessages([
                'abilities' => 'Minimal satu ability agent diperlukan.',
            ]);
        }

        foreach ($abilities as $ability) {
            if (! AgentAbility::isValid($ability)) {
                throw ValidationException::withMessages([
                    'abilities' => "Ability tidak dikenal: {$ability}",
                ]);
            }
        }

        return $user->createToken($name, $abilities, $expiresAt);
    }

    public function revoke(User $user, int $tokenId): bool
    {
        $deleted = $user->tokens()->whereKey($tokenId)->delete();

        return $deleted > 0;
    }
}
