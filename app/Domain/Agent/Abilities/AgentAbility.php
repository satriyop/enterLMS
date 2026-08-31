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

    public const TUTOR_READ = 'tutor.read';

    public const AUTHOR_READ = 'author.read';

    /**
     * Safe default until B-013 tools ship: identity only.
     *
     * @return list<string>
     */
    public static function defaults(): array
    {
        return [
            self::PING,
        ];
    }

    /**
     * Full free-flow agent surface (catalog → enroll → progress). Use after B-013 tools exist.
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

    /**
     * Tutor runtime door. Never bundled into free-flow or --all-abilities.
     *
     * @return list<string>
     */
    public static function tutorRead(): array
    {
        return [
            self::TUTOR_READ,
        ];
    }

    /**
     * Author Agent door. Never bundled into free-flow, tutor.read, or --all-abilities.
     *
     * @return list<string>
     */
    public static function authorRead(): array
    {
        return [
            self::AUTHOR_READ,
        ];
    }

    public static function isValid(string $ability): bool
    {
        return in_array($ability, self::all(), true)
            || $ability === self::TUTOR_READ
            || $ability === self::AUTHOR_READ;
    }
}
