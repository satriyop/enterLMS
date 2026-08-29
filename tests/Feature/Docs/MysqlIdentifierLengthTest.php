<?php

/**
 * MySQL identifier names are 64 characters. Laravel's default FK/index names
 * are {table}_{column}_{type}. Long table+column pairs overflow on aidev
 * after CREATE TABLE succeeds, leaving an orphan table and a stuck migrate.
 */
it('keeps generated MySQL index and foreign-key names at or under 64 characters', function () {
    $limit = 64;
    $tooLong = [];

    foreach (glob(database_path('migrations/*.php')) as $file) {
        $text = file_get_contents($file);
        expect($text)->not->toBeFalse();

        $parts = preg_split("/Schema::(?:create|table)\('([^']+)'/", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false || count($parts) < 3) {
            continue;
        }

        for ($i = 1; $i < count($parts); $i += 2) {
            $table = $parts[$i];
            $body = $parts[$i + 1] ?? '';

            foreach (matchForeignKeys($table, $body) as $name) {
                if (strlen($name) > $limit) {
                    $tooLong[] = basename($file).": FK {$name} (".strlen($name).')';
                }
            }

            foreach (matchIndexNames($table, $body, 'unique') as $name) {
                if (strlen($name) > $limit) {
                    $tooLong[] = basename($file).": unique {$name} (".strlen($name).')';
                }
            }

            foreach (matchIndexNames($table, $body, 'index') as $name) {
                if (strlen($name) > $limit) {
                    $tooLong[] = basename($file).": index {$name} (".strlen($name).')';
                }
            }
        }
    }

    expect($tooLong)->toBeEmpty();
});

it('types last_lesson_id as unsigned bigint so MySQL can add the lessons FK', function () {
    $create = file_get_contents(database_path('migrations/2025_11_26_193402_create_enrollments_table.php'));
    $integrity = file_get_contents(database_path('migrations/2026_01_31_184913_add_integrity_constraints.php'));

    expect($create)->toContain("unsignedBigInteger('last_lesson_id')")
        ->and($create)->not->toContain("integer('last_lesson_id')")
        ->and($integrity)->toContain("unsignedBigInteger('last_lesson_id')->nullable()->change()");
});

/**
 * @return list<string>
 */
function matchForeignKeys(string $table, string $body): array
{
    $names = [];

    if (preg_match_all("/foreignId\('([^']+)'\)(.*?)(?:;|foreignId\(|unique\(|index\()/s", $body, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $column = $match[1];
            $chain = $match[2];
            if (! str_contains($chain, 'constrained(')) {
                continue;
            }
            if (preg_match("/indexName:\s*'([^']+)'/", $chain, $named)) {
                $names[] = $named[1];

                continue;
            }
            if (preg_match("/constrained\((?:'[^']+'|'[^']+',\s*'[^']+'),\s*'([^']+)'\)/", $chain, $named)) {
                $names[] = $named[1];

                continue;
            }
            $names[] = "{$table}_{$column}_foreign";
        }
    }

    if (preg_match_all("/->foreign\('([^']+)'(?:,\s*'([^']+)')?\)/", $body, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $names[] = $match[2] ?? "{$table}_{$match[1]}_foreign";
        }
    }

    return $names;
}

/**
 * @return list<string>
 */
function matchIndexNames(string $table, string $body, string $type): array
{
    $names = [];

    if (preg_match_all("/->{$type}\(\[([^\]]+)\](?:,\s*'([^']+)')?\)/", $body, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            if (isset($match[2]) && $match[2] !== '') {
                $names[] = $match[2];

                continue;
            }
            preg_match_all("/'([^']+)'/", $match[1], $cols);
            $names[] = $table.'_'.implode('_', $cols[1]).'_'.$type;
        }
    }

    if (preg_match_all("/->{$type}\('([^']+)'(?:,\s*'([^']+)')?\)/", $body, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $names[] = $match[2] ?? "{$table}_{$match[1]}_{$type}";
        }
    }

    return $names;
}
