<?php

namespace App\Domain\Agent\Abilities;

/**
 * Sanctum personal-access-token abilities for agent MCP clients (Depth B).
 */
final class AgentAbility
{
    public const PING = 'agent:ping';

    public const CATALOG_READ = 'agent:catalog.read';

    public const COURSE_READ = 'agent:course.read';

    public const ENROLLMENT_READ = 'agent:enrollment.read';

    public const ENROLLMENT_WRITE = 'agent:enrollment.write';

    public const PROGRESS_READ = 'agent:progress.read';

    public const PROGRESS_WRITE = 'agent:progress.write';

    public const COMPLIANCE_READ = 'agent:compliance.read';

    /**
     * Abilities available for free-flow agent tokens (B-012 + B-013).
     *
     * @return list<string>
     */
    public static function freeFlow(): array
    {
        return [
            self::PING,
            self::CATALOG_READ,
            self::COURSE_READ,
            self::ENROLLMENT_READ,
            self::ENROLLMENT_WRITE,
            self::PROGRESS_READ,
            self::PROGRESS_WRITE,
        ];
    }

    /**
     * All known agent abilities.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PING,
            self::CATALOG_READ,
            self::COURSE_READ,
            self::ENROLLMENT_READ,
            self::ENROLLMENT_WRITE,
            self::PROGRESS_READ,
            self::PROGRESS_WRITE,
            self::COMPLIANCE_READ,
        ];
    }

    public static function isValid(string $ability): bool
    {
        return in_array($ability, self::all(), true);
    }
}
