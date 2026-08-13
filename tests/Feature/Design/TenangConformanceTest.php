<?php

/**
 * Tenang design conformance gate (ADR 007).
 *
 * The redesign tournament winner is a hybrid: Claude A's "Tenang" palette on
 * Claude B's shell. Its tokens were aliased onto the shadcn names, which meant
 * every existing `bg-card` kept painting the right colour -- so an unfinished
 * migration looked finished and nothing forced it to completion.
 *
 * This gate exists so that can't happen twice. It does not demand parity today;
 * it ratchets. Files that already violate are listed in the baseline and left
 * alone. A file not in the baseline must be clean, and a file that has been
 * cleaned must leave the baseline. Both directions fail loudly.
 *
 * Regenerate after fixing a batch:
 *   TENANG_UPDATE_BASELINE=1 php artisan test --filter="Tenang design conformance"
 */

use Illuminate\Support\Str;

const VUE_ROOT = 'resources/js';
const BASELINE = 'tests/Feature/Design/tenang-baseline.json';

/**
 * Stock Tailwind palette hues. Tenang defines its own semantic tokens
 * (`--ok`, `--warn`, `--gold`, …) and none of these belong in application code.
 */
const HUE_PATTERN = '/\b(?:bg|text|border|from|to|via|ring|fill|stroke|divide|outline|decoration|shadow|accent|caret)-'
    .'(?:red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|slate|gray|zinc|neutral|stone)'
    .'-\d{2,3}\b/';

/**
 * shadcn surface names. These are *aliased* to Tenang values so they paint
 * correctly, but they hide which surface level a component actually sits on.
 */
const ALIAS_PATTERN = '/\b(?:bg-card|bg-background|bg-muted|bg-accent|bg-secondary|bg-popover)\b/';

/**
 * Literal colours. Tenang keeps every colour in a token.
 * The lookbehind spares HTML entities such as `&#039;`.
 */
const HEX_PATTERN = '/(?<!&)#(?:[0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b/';

/**
 * @return list<string> repo-relative paths, sorted
 */
function conformanceFiles(): array
{
    $files = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(base_path(VUE_ROOT), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        // `.ts` matters as much as `.vue`: the worst offender this gate was
        // written for was lib/constants.ts, a colour table no template showed.
        if (! in_array($file->getExtension(), ['vue', 'ts'], true)) {
            continue;
        }

        $path = Str::after($file->getPathname(), base_path().DIRECTORY_SEPARATOR);
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        // `components/ui/` is the seam. Primitives are allowed to name raw
        // shadcn tokens, because translating them is precisely their job.
        if (str_starts_with($path, VUE_ROOT.'/components/ui/')) {
            continue;
        }

        // Wayfinder output — generated, gitignored, not ours to style.
        if (str_starts_with($path, VUE_ROOT.'/actions/') || str_starts_with($path, VUE_ROOT.'/routes/')) {
            continue;
        }

        // Frontend unit tests assert on colour strings; they are not UI.
        if (str_contains($path, '/__tests__/')) {
            continue;
        }

        // Dev-only `console.log('%c…')` styling. Never rendered to a user.
        if ($path === VUE_ROOT.'/lib/devtools.ts') {
            continue;
        }

        $files[] = $path;
    }

    sort($files);

    return $files;
}

/**
 * Vue `<style>` blocks are exempt from the hex rule: third-party concerns like
 * code-syntax highlighting carry their own palettes and are not part of the
 * design system.
 */
function conformanceSource(string $path): string
{
    $source = (string) file_get_contents(base_path($path));

    return (string) preg_replace('/<style\b[^>]*>.*?<\/style>/s', '', $source);
}

/**
 * @return array{hue: list<string>, alias: list<string>, hex: list<string>}
 */
function conformanceViolations(): array
{
    $found = ['hue' => [], 'alias' => [], 'hex' => []];

    foreach (conformanceFiles() as $path) {
        $source = conformanceSource($path);

        foreach (['hue' => HUE_PATTERN, 'alias' => ALIAS_PATTERN, 'hex' => HEX_PATTERN] as $rule => $pattern) {
            if (preg_match($pattern, $source) === 1) {
                $found[$rule][] = $path;
            }
        }
    }

    return $found;
}

/**
 * @return array{hue: list<string>, alias: list<string>, hex: list<string>}
 */
function conformanceBaseline(): array
{
    $decoded = json_decode((string) file_get_contents(base_path(BASELINE)), true);

    return [
        'hue' => $decoded['hue'] ?? [],
        'alias' => $decoded['alias'] ?? [],
        'hex' => $decoded['hex'] ?? [],
    ];
}

describe('Tenang design conformance', function () {

    /**
     * Rewriting the baseline goes through the same scanner the checks use, so
     * the recorded set can never drift from what is actually detected.
     */
    beforeEach(function () {
        if (getenv('TENANG_UPDATE_BASELINE') === false) {
            return;
        }

        $violations = conformanceViolations();

        file_put_contents(base_path(BASELINE), json_encode([
            '_' => 'Files that predate the Tenang conformance gate (ADR 007). This list may shrink, never grow.',
            'hue' => $violations['hue'],
            'alias' => $violations['alias'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    });

    it('has no new hardcoded Tailwind palette colours', function () {
        $new = array_values(array_diff(conformanceViolations()['hue'], conformanceBaseline()['hue']));

        expect($new)->toBe([], 'These files use stock Tailwind hues instead of Tenang tokens. '
            .'Map the meaning to a tone in resources/js/lib/constants.ts (TONES).');
    });

    it('has no new shadcn surface aliases', function () {
        $new = array_values(array_diff(conformanceViolations()['alias'], conformanceBaseline()['alias']));

        expect($new)->toBe([], 'These files name shadcn surfaces (bg-card, bg-muted, …). '
            .'Use the Tenang level the element actually sits on: bg-surface, bg-surface-2, bg-surface-3.');
    });

    it('has no literal hex colours outside <style> blocks', function () {
        expect(conformanceViolations()['hex'])->toBe([], 'Colour belongs in a token in resources/css/app.css.');
    });

    it('has a baseline with no stale entries', function () {
        $violations = conformanceViolations();
        $baseline = conformanceBaseline();

        $stale = [];

        foreach (['hue', 'alias'] as $rule) {
            foreach (array_diff($baseline[$rule], $violations[$rule]) as $path) {
                $stale[] = "{$rule}: {$path}";
            }
        }

        expect($stale)->toBe([], 'These files are clean now. Remove them from '.BASELINE
            .' so the ratchet cannot slip back.');
    });

});
