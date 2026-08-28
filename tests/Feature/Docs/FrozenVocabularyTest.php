<?php

/**
 * Frozen domain vocabulary must not reappear in live code or live docs.
 *
 * Retired vocabulary must not reappear in live code or live docs. ADR 007
 * collapsed seven roles to two. What survived last time was everything no test
 * could see: placeholders, policy comments, seeders, and a requirement file
 * composed into CLAUDE.md.
 *
 * Nothing failed, because nothing was watching. This is what watches.
 *
 * Two places are exempt, for opposite reasons:
 *   - ADRs, migrations and .ai/ archives are *records*. They must name the frozen
 *     thing to say it was frozen; rewriting them would falsify the history.
 *   - BankingCourseSeeder is a deliberate shim that warns and delegates.
 */

use Illuminate\Support\Str;

/**
 * Vocabulary that must not reappear in live code or live docs.
 *
 * @var array<string, string>
 */
const FROZEN_TERMS = [
    'OJK' => 'retired; do not reintroduce',
    'APU-PPT' => 'retired; do not reintroduce',
    'perbankan' => 'retired; do not reintroduce',
    'banking' => 'retired; do not reintroduce',
    'enteraksi' => 'this academy is not that product; do not reintroduce',
    'content_manager' => 'ADR 007 collapsed roles to learner and lms_admin',
    'content manager' => 'ADR 007 collapsed roles to learner and lms_admin',
    'trainer' => 'ADR 007 collapsed roles to learner and lms_admin',
];

/**
 * Directories that hold live code and live documentation.
 *
 * @var list<string>
 */
const LIVE_ROOTS = ['app', 'resources/js', 'database', 'docs', '.ai/guidelines'];

/**
 * Records, not instructions. These must be free to name what they retired.
 *
 * @var list<string>
 */
const VOCABULARY_EXEMPT = [
    'docs/adr/',
    'database/migrations/',
    'database/seeders/BankingCourseSeeder.php',
    'docs/getting-started/roles.md',   // explains the collapse; must name the old roles
    'tests/Feature/Docs/',
];

/**
 * @return list<string> repo-relative paths of live source and docs
 */
function liveFiles(): array
{
    $files = [];

    foreach (LIVE_ROOTS as $root) {
        $base = base_path($root);

        if (! is_dir($base)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! in_array($file->getExtension(), ['php', 'vue', 'ts', 'md'], true)) {
                continue;
            }

            $path = str_replace(DIRECTORY_SEPARATOR, '/', Str::after($file->getPathname(), base_path().DIRECTORY_SEPARATOR));

            foreach (VOCABULARY_EXEMPT as $exempt) {
                if (str_starts_with($path, $exempt)) {
                    continue 2;
                }
            }

            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

describe('frozen vocabulary', function () {

    it('scans a meaningful number of files', function () {
        // A path typo would make every assertion below pass against nothing.
        expect(count(liveFiles()))->toBeGreaterThan(200);
    });

    it('does not reappear in live code or live docs', function () {
        $found = [];

        foreach (liveFiles() as $path) {
            $source = (string) file_get_contents(base_path($path));

            foreach (FROZEN_TERMS as $term => $why) {
                if (! Str::contains($source, $term, ignoreCase: true)) {
                    continue;
                }

                // A line may name a frozen term to say it is frozen.
                foreach (explode("\n", $source) as $number => $line) {
                    if (! Str::contains($line, $term, ignoreCase: true)) {
                        continue;
                    }

                    if (Str::contains($line, ['frozen', 'froze', 'retired', 'deprecated', 'do not reintroduce', 'ADR 007'], ignoreCase: true)) {
                        continue;
                    }

                    $found[] = "{$path}:".($number + 1)." — '{$term}' ({$why})";
                }
            }
        }

        expect($found)->toBe([], 'Frozen vocabulary is back in live code. '
            .'CONTEXT.md names what this product is; use its language, or add the file '
            .'to VOCABULARY_EXEMPT if it is a record rather than an instruction.');
    });

});
