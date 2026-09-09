<?php

namespace Tests\Support;

/**
 * Parse a Lesson diagram SVG and check connectors do not cut through boxes.
 */
final class SvgDiagramGeometry
{
    /**
     * @return array{
     *     boxes: list<array{x: float, y: float, w: float, h: float}>,
     *     segments: list<array{0: float, 1: float, 2: float, 3: float}>,
     *     edges: array<string, list<array{0: float, 1: float, 2: float, 3: float}>>,
     *     labels: list<array{x: float, y: float, text: string}>
     * }
     */
    public static function parse(string $html): array
    {
        $boxes = [];
        $segments = [];
        $edges = [];
        $labels = [];

        if (preg_match_all(
            '/<rect\b[^>]*\bx="([\d.]+)"[^>]*\by="([\d.]+)"[^>]*\bwidth="([\d.]+)"[^>]*\bheight="([\d.]+)"/',
            $html,
            $rects,
            PREG_SET_ORDER
        ) !== false) {
            foreach ($rects as $rect) {
                $boxes[] = [
                    'x' => (float) $rect[1],
                    'y' => (float) $rect[2],
                    'w' => (float) $rect[3],
                    'h' => (float) $rect[4],
                ];
            }
        }

        if (preg_match_all(
            '/<line\b[^>]*\bx1="([\d.]+)"[^>]*\by1="([\d.]+)"[^>]*\bx2="([\d.]+)"[^>]*\by2="([\d.]+)"/',
            $html,
            $lines,
            PREG_SET_ORDER
        ) !== false) {
            foreach ($lines as $line) {
                $segments[] = [
                    (float) $line[1],
                    (float) $line[2],
                    (float) $line[3],
                    (float) $line[4],
                ];
            }
        }

        if (preg_match_all('/<path\b([^>]*)>/', $html, $paths, PREG_SET_ORDER) !== false) {
            foreach ($paths as $path) {
                $attrs = $path[1];
                if (! str_contains($attrs, 'fill="none"') && ! str_contains($attrs, 'data-edge=')) {
                    continue;
                }
                if (! preg_match('/\bd="([^"]+)"/', $attrs, $dMatch)) {
                    continue;
                }

                $edgeSegments = self::pathToSegments($dMatch[1]);
                foreach ($edgeSegments as $segment) {
                    $segments[] = $segment;
                }

                if (preg_match('/data-edge="([^"]+)"/', $attrs, $edgeMatch) === 1) {
                    $edges[html_entity_decode($edgeMatch[1], ENT_QUOTES | ENT_XML1, 'UTF-8')] = $edgeSegments;
                }
            }
        }

        if (preg_match_all(
            '/<text\b([^>]*)>([^<]*)</',
            $html,
            $texts,
            PREG_SET_ORDER
        ) !== false) {
            foreach ($texts as $text) {
                if (! str_contains($text[1], 'diagram-edge-label')) {
                    continue;
                }
                if (preg_match('/\bx="([\d.]+)"/', $text[1], $xMatch) !== 1) {
                    continue;
                }
                if (preg_match('/\by="([\d.]+)"/', $text[1], $yMatch) !== 1) {
                    continue;
                }

                $labels[] = [
                    'x' => (float) $xMatch[1],
                    'y' => (float) $yMatch[1],
                    'text' => html_entity_decode($text[2], ENT_QUOTES | ENT_XML1, 'UTF-8'),
                ];
            }
        }

        return [
            'boxes' => $boxes,
            'segments' => $segments,
            'edges' => $edges,
            'labels' => $labels,
        ];
    }

    /**
     * @return list<array{0: float, 1: float, 2: float, 3: float}>
     */
    public static function pathToSegments(string $d): array
    {
        $segments = [];
        preg_match_all('/([MLHV])\s*([\d.\s,-]+)/i', $d, $commands, PREG_SET_ORDER);

        $x = 0.0;
        $y = 0.0;
        $started = false;

        foreach ($commands as $command) {
            $op = strtoupper($command[1]);
            $nums = preg_split('/[\s,]+/', trim($command[2])) ?: [];
            $nums = array_values(array_filter($nums, fn ($n) => $n !== ''));

            if ($op === 'M' && isset($nums[0], $nums[1])) {
                $x = (float) $nums[0];
                $y = (float) $nums[1];
                $started = true;

                continue;
            }

            if (! $started) {
                continue;
            }

            if ($op === 'L' && isset($nums[0], $nums[1])) {
                $nx = (float) $nums[0];
                $ny = (float) $nums[1];
                $segments[] = [$x, $y, $nx, $ny];
                $x = $nx;
                $y = $ny;
            } elseif ($op === 'H' && isset($nums[0])) {
                $nx = (float) $nums[0];
                $segments[] = [$x, $y, $nx, $y];
                $x = $nx;
            } elseif ($op === 'V' && isset($nums[0])) {
                $ny = (float) $nums[0];
                $segments[] = [$x, $y, $x, $ny];
                $y = $ny;
            }
        }

        return $segments;
    }

    public static function isOrthogonal(float $x1, float $y1, float $x2, float $y2, float $tol = 0.6): bool
    {
        return abs($x1 - $x2) <= $tol || abs($y1 - $y2) <= $tol;
    }

    public static function segmentHitsBoxInterior(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        array $box,
        float $inset = 3.0
    ): bool {
        $left = $box['x'] + $inset;
        $right = $box['x'] + $box['w'] - $inset;
        $top = $box['y'] + $inset;
        $bottom = $box['y'] + $box['h'] - $inset;

        if ($right <= $left || $bottom <= $top) {
            return false;
        }

        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $u1 = 0.0;
        $u2 = 1.0;

        $p = [-$dx, $dx, -$dy, $dy];
        $q = [$x1 - $left, $right - $x1, $y1 - $top, $bottom - $y1];

        for ($i = 0; $i < 4; $i++) {
            if (abs($p[$i]) < 1e-9) {
                if ($q[$i] < 0) {
                    return false;
                }

                continue;
            }

            $r = $q[$i] / $p[$i];
            if ($p[$i] < 0) {
                if ($r > $u2) {
                    return false;
                }
                if ($r > $u1) {
                    $u1 = $r;
                }
            } else {
                if ($r < $u1) {
                    return false;
                }
                if ($r < $u2) {
                    $u2 = $r;
                }
            }
        }

        return $u1 < $u2;
    }

    /**
     * @param  array{boxes: list<array{x: float, y: float, w: float, h: float}>, segments: list<array{0: float, 1: float, 2: float, 3: float}>}  $parsed
     * @return list<string>
     */
    public static function problems(array $parsed): array
    {
        $problems = [];

        if ($parsed['boxes'] === [] || $parsed['segments'] === []) {
            $problems[] = 'diagram is missing boxes or connectors';

            return $problems;
        }

        foreach ($parsed['segments'] as $i => $seg) {
            [$x1, $y1, $x2, $y2] = $seg;

            if (hypot($x2 - $x1, $y2 - $y1) < 0.5) {
                continue;
            }

            if (! self::isOrthogonal($x1, $y1, $x2, $y2)) {
                $problems[] = "segment {$i} is diagonal ({$x1},{$y1})→({$x2},{$y2})";
            }

            $startBox = self::boxIndexAt($parsed['boxes'], $x1, $y1);
            $endBox = self::boxIndexAt($parsed['boxes'], $x2, $y2);

            foreach ($parsed['boxes'] as $b => $box) {
                if ($b === $startBox || $b === $endBox) {
                    continue;
                }

                if (self::segmentHitsBoxInterior($x1, $y1, $x2, $y2, $box)) {
                    $problems[] = "segment {$i} cuts through box {$b}";
                }
            }
        }

        return $problems;
    }

    /**
     * @param  list<array{x: float, y: float, w: float, h: float}>  $boxes
     */
    public static function boxIndexAt(array $boxes, float $x, float $y, float $pad = 6.0): ?int
    {
        $best = null;
        $bestDist = PHP_FLOAT_MAX;

        foreach ($boxes as $i => $box) {
            $near = $x >= $box['x'] - $pad
                && $x <= $box['x'] + $box['w'] + $pad
                && $y >= $box['y'] - $pad
                && $y <= $box['y'] + $box['h'] + $pad;

            if (! $near) {
                continue;
            }

            $cx = $box['x'] + $box['w'] / 2;
            $cy = $box['y'] + $box['h'] / 2;
            $dist = hypot($x - $cx, $y - $cy);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $i;
            }
        }

        return $best;
    }

    /**
     * @param  list<array{0: float, 1: float, 2: float, 3: float}>  $segments
     */
    public static function lastSegmentLength(array $segments): float
    {
        if ($segments === []) {
            return 0.0;
        }

        $last = $segments[array_key_last($segments)];

        return hypot($last[2] - $last[0], $last[3] - $last[1]);
    }

    /**
     * Longest horizontal run on a polyline — the skip/loop rail.
     *
     * @param  list<array{0: float, 1: float, 2: float, 3: float}>  $segments
     */
    public static function railY(array $segments): ?float
    {
        $bestY = null;
        $bestLen = -1.0;

        foreach ($segments as $seg) {
            if (abs($seg[1] - $seg[3]) > 0.6) {
                continue;
            }

            $len = abs($seg[2] - $seg[0]);
            if ($len > $bestLen) {
                $bestLen = $len;
                $bestY = $seg[1];
            }
        }

        return $bestY;
    }

    /**
     * @param  list<array{x: float, y: float, w: float, h: float}>  $boxes
     */
    public static function labelHitsBox(float $x, float $y, array $boxes, float $font = 11.0): bool
    {
        $top = $y - $font;
        $bottom = $y;

        foreach ($boxes as $box) {
            $centerInside = $x >= $box['x'] && $x <= $box['x'] + $box['w'];
            $overlapY = $bottom >= $box['y'] && $top <= $box['y'] + $box['h'];
            if ($centerInside && $overlapY) {
                return true;
            }
        }

        return false;
    }
}
