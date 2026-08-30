<?php

/**
 * Domain and HTTP code must check capabilities, not the install preset name (ADR 010).
 *
 * Allowed to read the preset: Academy itself, and the installer command that writes .env.
 */

use Illuminate\Support\Str;

const PRESET_BRANCH_NEEDLES = [
    "config('lms.preset",
    'config("lms.preset',
    'Academy::preset(',
];

const PRESET_BRANCH_ALLOWED = [
    'app/Domain/Shared/Academy.php',
    'app/Console/Commands/LmsPresetCommand.php',
];

describe('install preset branching', function () {

    it('does not compare preset names in domain or HTTP code', function () {
        $found = [];
        $root = base_path('app');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace(DIRECTORY_SEPARATOR, '/', Str::after($file->getPathname(), base_path().DIRECTORY_SEPARATOR));

            if (in_array($path, PRESET_BRANCH_ALLOWED, true)) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());

            foreach (PRESET_BRANCH_NEEDLES as $needle) {
                if (! str_contains($source, $needle)) {
                    continue;
                }

                foreach (explode("\n", $source) as $number => $line) {
                    if (str_contains($line, $needle)) {
                        $found[] = "{$path}:".($number + 1).' — '.$needle;
                    }
                }
            }
        }

        expect($found)->toBe([], 'Check Academy::enabled() / Academy::label(), not the preset name (ADR 010).');
    });

});
