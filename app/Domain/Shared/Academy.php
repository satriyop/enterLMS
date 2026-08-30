<?php

namespace App\Domain\Shared;

use InvalidArgumentException;

/**
 * Academy-wide capabilities for this installation (ADR 010).
 *
 * Check features and labels. Do not branch on preset().
 *
 * @phpstan-import-type AcademyFeatures from AcademyPresetCatalog
 * @phpstan-import-type AcademyLabels from AcademyPresetCatalog
 * @phpstan-import-type AcademyIdentity from AcademyPresetCatalog
 */
final class Academy
{
    public static function preset(): string
    {
        return (string) config('lms.preset');
    }

    public static function enabled(string $feature): bool
    {
        $features = config('lms.features', []);

        if (! is_array($features) || ! array_key_exists($feature, $features)) {
            throw new InvalidArgumentException("Unknown academy feature [{$feature}].");
        }

        return (bool) $features[$feature];
    }

    public static function label(string $key): string
    {
        $labels = config('lms.labels', []);

        if (! is_array($labels) || ! array_key_exists($key, $labels)) {
            throw new InvalidArgumentException("Unknown academy label [{$key}].");
        }

        return (string) $labels[$key];
    }

    public static function identityScheme(): string
    {
        return (string) config('lms.identity.scheme');
    }

    public static function identityLabel(): string
    {
        return (string) config('lms.identity.label');
    }

    /**
     * Swap the resolved academy config for this request (tests).
     *
     * @param  array<string, bool|null>  $featureOverrides
     * @param  array<string, string|null>  $labelOverrides
     * @param  array{scheme?: string|null, label?: string|null}  $identityOverrides
     */
    public static function using(
        string $preset,
        array $featureOverrides = [],
        array $labelOverrides = [],
        array $identityOverrides = [],
    ): void {
        $resolved = AcademyPresetCatalog::resolve(
            $preset,
            $featureOverrides,
            $labelOverrides,
            $identityOverrides,
        );

        config([
            'lms.preset' => $resolved['preset'],
            'lms.features' => $resolved['features'],
            'lms.labels' => $resolved['labels'],
            'lms.identity' => $resolved['identity'],
        ]);
    }

    /**
     * Inertia payload. Omits the preset name so the UI cannot branch on it.
     *
     * @return array{
     *     features: AcademyFeatures,
     *     labels: AcademyLabels,
     *     identity: AcademyIdentity
     * }
     */
    public static function toInertia(): array
    {
        /** @var AcademyFeatures $features */
        $features = config('lms.features');
        /** @var AcademyLabels $labels */
        $labels = config('lms.labels');
        /** @var AcademyIdentity $identity */
        $identity = config('lms.identity');

        return [
            'features' => $features,
            'labels' => $labels,
            'identity' => $identity,
        ];
    }
}
