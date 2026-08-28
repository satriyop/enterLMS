<?php

/**
 * The composed guidelines in CLAUDE.md must match their sources in .ai/guidelines/.
 *
 * Laravel Boost composes every file in `.ai/guidelines/` into the
 * `<laravel-boost-guidelines>` block of CLAUDE.md, under a `=== .ai/<name> rules ===`
 * heading. CLAUDE.md is therefore a *generated artefact*, and the file in
 * `.ai/guidelines/` is the source.
 *
 * Only the generated copy is loaded into an agent's context automatically. So when
 * the two drift, the stale copy wins over the corrected source and nothing says a
 * word. That is how a retired positioning survived: the source was never fixed,
 * and there was nothing that could have noticed if it had been.
 *
 * Regenerate after editing a guideline:
 *   php artisan boost:install
 */

use Illuminate\Support\Str;

const GUIDELINE_DIR = '.ai/guidelines';

/**
 * @return array<string, string> guideline name => trimmed source content
 */
function guidelineSources(): array
{
    $sources = [];

    foreach (glob(base_path(GUIDELINE_DIR.'/*.md')) ?: [] as $path) {
        $name = basename($path, '.md');
        $sources[$name] = trim((string) file_get_contents($path));
    }

    return $sources;
}

describe('agent guidelines', function () {

    it('composes at least one guideline into CLAUDE.md', function () {
        expect(guidelineSources())->not->toBeEmpty(
            'No guidelines found in '.GUIDELINE_DIR.'. If they moved, this gate is now blind.'
        );
    });

    it('keeps CLAUDE.md in sync with every guideline source', function () {
        $claude = (string) file_get_contents(base_path('CLAUDE.md'));

        $drifted = [];

        foreach (guidelineSources() as $name => $source) {
            $heading = "=== .ai/{$name} rules ===";

            if (! str_contains($claude, $heading)) {
                $drifted[] = "{$name}: not composed into CLAUDE.md (missing '{$heading}')";

                continue;
            }

            if (! str_contains($claude, $source)) {
                $drifted[] = "{$name}: CLAUDE.md copy differs from ".GUIDELINE_DIR."/{$name}.md";
            }
        }

        expect($drifted)->toBe([], 'CLAUDE.md is the copy that gets loaded, so a stale copy '
            .'silently outranks the corrected source. Run `php artisan boost:install` to recompose.');
    });

    it('keeps domain facts out of the guidelines', function () {
        // CONTEXT.md owns the domain. A guideline that names a Course, a role or a
        // frozen concept is a fact with a second home, and second homes go stale.
        $domainTerms = [
            'banking', 'perbankan', 'OJK', 'APU-PPT', 'Enteraksi',
            'Open Course', 'Restricted Course', 'Learning Path',
            'LMS Admin', 'OpenClaw', 'Hermes',
            'Tutor', 'LMS Agent', 'Grade Proposal', 'Conversation',
        ];

        $found = [];

        foreach (guidelineSources() as $name => $source) {
            // The prohibition itself has to be allowed to name what it prohibits.
            $prose = (string) preg_replace('/^.*Do not restate.*$/mi', '', $source);

            foreach ($domainTerms as $term) {
                if (Str::contains($prose, $term, ignoreCase: true)) {
                    $found[] = "{$name}: '{$term}'";
                }
            }
        }

        expect($found)->toBe([], 'These guidelines restate domain language. '
            .'Move it to CONTEXT.md and point at it instead — a copy here is loaded '
            .'every session and will outlive the decision that changes it.');
    });

});
