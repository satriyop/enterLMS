<?php

namespace App\Domain\Shared;

use InvalidArgumentException;

/**
 * Resolves a named install preset into capabilities (ADR 010).
 *
 * Called from config/lms.php at boot via fromEnvironment(). Runtime
 * code uses Academy, not this class, except tests swapping a preset.
 *
 * @phpstan-type AcademyFeatures array{
 *     offerings: bool,
 *     facilitators: bool,
 *     attendance: bool,
 *     letter_grades: bool,
 *     academic_calendar: bool,
 *     sso: bool
 * }
 * @phpstan-type AcademyLabels array{
 *     offering: string,
 *     facilitator: string,
 *     learner: string
 * }
 * @phpstan-type AcademyIdentity array{
 *     scheme: string,
 *     label: string
 * }
 * @phpstan-type AcademyBundle array{
 *     features: AcademyFeatures,
 *     labels: AcademyLabels,
 *     identity: AcademyIdentity
 * }
 * @phpstan-type AcademyResolved array{
 *     preset: string,
 *     features: AcademyFeatures,
 *     labels: AcademyLabels,
 *     identity: AcademyIdentity
 * }
 */
final class AcademyPresetCatalog
{
    public const DEFAULT = 'academy';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::bundles());
    }

    /**
     * @return array<string, AcademyBundle>
     */
    public static function bundles(): array
    {
        /** @var array<string, AcademyBundle> $bundles */
        $bundles = require config_path('lms-presets.php');

        return $bundles;
    }

    /**
     * @param  array<string, bool|null>  $featureOverrides
     * @param  array<string, string|null>  $labelOverrides
     * @param  array{scheme?: string|null, label?: string|null}  $identityOverrides
     * @return AcademyResolved
     */
    public static function resolve(
        string $preset,
        array $featureOverrides = [],
        array $labelOverrides = [],
        array $identityOverrides = [],
    ): array {
        $bundles = self::bundles();

        if (! isset($bundles[$preset])) {
            throw new InvalidArgumentException(
                "Unknown LMS preset [{$preset}]. Valid: ".implode(', ', array_keys($bundles)).'.'
            );
        }

        $bundle = $bundles[$preset];
        $features = $bundle['features'];

        foreach ($featureOverrides as $name => $value) {
            if (! array_key_exists($name, $features)) {
                throw new InvalidArgumentException("Unknown academy feature [{$name}].");
            }

            if ($value === null) {
                continue;
            }

            $features[$name] = $value;
        }

        $labels = $bundle['labels'];

        foreach ($labelOverrides as $name => $value) {
            if (! array_key_exists($name, $labels)) {
                throw new InvalidArgumentException("Unknown academy label [{$name}].");
            }

            if ($value === null || $value === '') {
                continue;
            }

            $labels[$name] = $value;
        }

        $identity = $bundle['identity'];

        $scheme = $identityOverrides['scheme'] ?? null;
        if (is_string($scheme) && $scheme !== '') {
            $identity['scheme'] = $scheme;
        }

        $identityLabel = $identityOverrides['label'] ?? null;
        if (is_string($identityLabel) && $identityLabel !== '') {
            $identity['label'] = $identityLabel;
        }

        return [
            'preset' => $preset,
            'features' => $features,
            'labels' => $labels,
            'identity' => $identity,
        ];
    }
}
