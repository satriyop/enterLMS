<?php

namespace App\Services;

/**
 * Turns a structured TipTap `diagram` node into an inline SVG figure.
 *
 * Seeded Lessons store boxes and edges as data. Connectors are orthogonal
 * (no diagonal through empty space or through other boxes).
 */
class LessonDiagramRenderer
{
    private int $boxW = 168;

    private int $boxH = 72;

    private int $gapX = 48;

    private int $gapY = 64;

    private int $pad = 24;

    private int $arrowShorten = 10;

    private int $minStem = 20;

    private int $laneSpacing = 22;

    /**
     * @param  array{
     *     caption?: string,
     *     alt?: string,
     *     nodes?: list<array{id?: string, label?: string, hint?: string}>,
     *     edges?: list<array{from?: string, to?: string, label?: string}>,
     *     columns?: int
     * }  $attrs
     */
    public function render(array $attrs): string
    {
        $nodes = $this->normalizeNodes($attrs['nodes'] ?? []);

        if ($nodes === []) {
            return '';
        }

        $caption = trim((string) ($attrs['caption'] ?? ''));
        $alt = trim((string) ($attrs['alt'] ?? $caption));
        $columns = (int) ($attrs['columns'] ?? 0);
        $columns = $columns > 0 ? min($columns, count($nodes)) : min(4, count($nodes));
        $columns = max(1, $columns);

        $edges = $this->normalizeEdges($attrs['edges'] ?? [], $nodes);

        $positions = [];
        $maxRow = 0;
        $index = 0;

        foreach ($nodes as $id => $node) {
            $col = $index % $columns;
            $row = intdiv($index, $columns);
            $positions[$id] = [
                'id' => $id,
                'x' => $this->pad + ($col * ($this->boxW + $this->gapX)),
                'y' => $this->pad + ($row * ($this->boxH + $this->gapY)),
                'row' => $row,
                'col' => $col,
                'label' => $node['label'],
                'hint' => $node['hint'],
            ];
            $maxRow = max($maxRow, $row);
            $index++;
        }

        $laneDrop = $this->arrowShorten + $this->minStem + 2;
        $lastRowBottom = $this->pad + ($maxRow * ($this->boxH + $this->gapY)) + $this->boxH;
        $edgeLaneY = $this->assignBelowLanes($edges, $positions, $maxRow, $lastRowBottom, $laneDrop);
        $maxLaneY = $lastRowBottom;
        foreach ($edgeLaneY as $laneY) {
            $maxLaneY = max($maxLaneY, $laneY);
        }

        $width = $this->pad + ($columns * $this->boxW) + (($columns - 1) * $this->gapX) + $this->pad;
        $height = $maxLaneY + 28 + $this->pad;

        $markerId = 'darrow-'.substr(hash('sha256', $caption.implode(',', array_keys($nodes))), 0, 10);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$width.' '.$height.'" width="'.$width.'" height="'.$height.'" role="img" aria-label="'.$this->xml($alt).'" class="lesson-diagram-svg">';
        $svg .= '<defs><marker id="'.$markerId.'" markerWidth="8" markerHeight="8" refX="6" refY="4" orient="auto"><path d="M0,0 L8,4 L0,8 z" fill="var(--primary)"/></marker></defs>';

        foreach ($edges as $edge) {
            if (! isset($positions[$edge['from']], $positions[$edge['to']])) {
                continue;
            }

            $key = $edge['from'].':'.$edge['to'];
            $svg .= $this->edgePath(
                $positions[$edge['from']],
                $positions[$edge['to']],
                $markerId,
                $edge['label'],
                $edgeLaneY[$key] ?? null,
            );
        }

        foreach ($positions as $box) {
            $svg .= $this->box($box);
        }

        $svg .= '</svg>';

        $figure = '<figure class="lesson-diagram">'.$svg;

        if ($caption !== '') {
            $figure .= '<figcaption>'.e($caption).'</figcaption>';
        }

        return $figure.'</figure>';
    }

    /**
     * @param  list<array{id?: string, label?: string, hint?: string}>  $raw
     * @return array<string, array{label: string, hint: string}>
     */
    private function normalizeNodes(array $raw): array
    {
        $nodes = [];
        $index = 0;

        foreach ($raw as $node) {
            if (! is_array($node)) {
                continue;
            }

            $id = trim((string) ($node['id'] ?? 'n'.$index));
            $label = trim((string) ($node['label'] ?? ''));

            if ($id === '' || $label === '') {
                $index++;

                continue;
            }

            $nodes[$id] = [
                'label' => $label,
                'hint' => trim((string) ($node['hint'] ?? '')),
            ];
            $index++;
        }

        return $nodes;
    }

    /**
     * @param  list<array{from?: string, to?: string, label?: string}>  $raw
     * @param  array<string, array{label: string, hint: string}>  $nodes
     * @return list<array{from: string, to: string, label: string}>
     */
    private function normalizeEdges(array $raw, array $nodes): array
    {
        $ids = array_keys($nodes);

        if ($raw === []) {
            $auto = [];

            for ($i = 0, $n = count($ids) - 1; $i < $n; $i++) {
                $auto[] = ['from' => $ids[$i], 'to' => $ids[$i + 1], 'label' => ''];
            }

            return $auto;
        }

        $edges = [];

        foreach ($raw as $edge) {
            if (! is_array($edge)) {
                continue;
            }

            $from = trim((string) ($edge['from'] ?? ''));
            $to = trim((string) ($edge['to'] ?? ''));

            if ($from === '' || $to === '' || ! isset($nodes[$from], $nodes[$to])) {
                continue;
            }

            $edges[] = [
                'from' => $from,
                'to' => $to,
                'label' => trim((string) ($edge['label'] ?? '')),
            ];
        }

        return $edges;
    }

    /**
     * @param  list<array{from: string, to: string, label: string}>  $edges
     * @param  array<string, array{id: string, x: int, y: int, row: int, col: int, label: string, hint: string}>  $positions
     * @return array<string, int>
     */
    private function assignBelowLanes(array $edges, array $positions, int $maxRow, int $lastRowBottom, int $laneDrop): array
    {
        $lanes = [];
        $skipOnLast = 0;
        $skipOnRow = [];
        $loopY = null;

        foreach ($edges as $edge) {
            $from = $positions[$edge['from']];
            $to = $positions[$edge['to']];
            if ($from['row'] !== $to['row']) {
                continue;
            }

            $dCol = $to['col'] - $from['col'];
            $key = $edge['from'].':'.$edge['to'];

            if ($dCol > 1) {
                if ($from['row'] === $maxRow) {
                    $lanes[$key] = $lastRowBottom + $laneDrop + ($skipOnLast * $this->laneSpacing);
                    $skipOnLast++;
                } else {
                    $n = $skipOnRow[$from['row']] ?? 0;
                    $lanes[$key] = $from['y'] + $this->boxH + $laneDrop + ($n * $this->laneSpacing);
                    $skipOnRow[$from['row']] = $n + 1;
                }
            } elseif ($dCol < -1) {
                $loopY ??= $lastRowBottom + $laneDrop + ($skipOnLast * $this->laneSpacing) + $this->laneSpacing;
                $lanes[$key] = $loopY;
            }
        }

        return $lanes;
    }

    /**
     * @param  array{id: string, x: int, y: int, row: int, col: int, label: string, hint: string}  $from
     * @param  array{id: string, x: int, y: int, row: int, col: int, label: string, hint: string}  $to
     */
    private function edgePath(
        array $from,
        array $to,
        string $markerId,
        string $label,
        ?int $belowLaneY,
    ): string {
        $points = $this->route($from, $to, $belowLaneY);
        $points = $this->shortenEnd($points, $this->arrowShorten);

        $d = '';
        foreach ($points as $i => $point) {
            $d .= ($i === 0 ? 'M ' : ' L ').$point[0].' '.$point[1];
        }

        $path = '<path fill="none" data-edge="'.$this->xml($from['id'].':'.$to['id']).'" d="'.$d.'" stroke="var(--primary)" stroke-width="2" marker-end="url(#'.$markerId.')"/>';

        if ($label !== '') {
            $path .= $this->labelOnPath($points, $label, min($from['y'] + $this->boxH, $to['y'] + $this->boxH));
        }

        return $path;
    }

    /**
     * @param  array{id: string, x: int, y: int, row: int, col: int, label: string, hint: string}  $from
     * @param  array{id: string, x: int, y: int, row: int, col: int, label: string, hint: string}  $to
     * @return list<array{0: int, 1: int}>
     */
    private function route(array $from, array $to, ?int $belowLaneY): array
    {
        $fromE = [$from['x'] + $this->boxW, $from['y'] + (int) round($this->boxH / 2)];
        $fromW = [$from['x'], $from['y'] + (int) round($this->boxH / 2)];
        $fromS = [$from['x'] + (int) round($this->boxW / 2), $from['y'] + $this->boxH];
        $fromN = [$from['x'] + (int) round($this->boxW / 2), $from['y']];
        $toE = [$to['x'] + $this->boxW, $to['y'] + (int) round($this->boxH / 2)];
        $toW = [$to['x'], $to['y'] + (int) round($this->boxH / 2)];
        $toN = [$to['x'] + (int) round($this->boxW / 2), $to['y']];
        $toS = [$to['x'] + (int) round($this->boxW / 2), $to['y'] + $this->boxH];

        $dRow = $to['row'] - $from['row'];
        $dCol = $to['col'] - $from['col'];

        if ($dRow === 0 && $dCol === 1) {
            return [$fromE, $toW];
        }

        if ($dRow === 0 && $dCol === -1) {
            return [$fromW, $toE];
        }

        if ($dRow === 0 && $dCol > 1) {
            $laneY = $belowLaneY ?? ($from['y'] + $this->boxH + $this->arrowShorten + $this->minStem + 2);

            return [
                $fromS,
                [$fromS[0], $laneY],
                [$toS[0], $laneY],
                $toS,
            ];
        }

        if ($dRow === 0 && $dCol < -1) {
            $laneY = $belowLaneY ?? ($from['y'] + $this->boxH + $this->arrowShorten + $this->minStem + 2);

            return [
                $fromS,
                [$fromS[0], $laneY],
                [$toS[0], $laneY],
                $toS,
            ];
        }

        if ($dRow === 1 && $dCol === 0) {
            return [$fromS, $toN];
        }

        if ($dRow === 1) {
            $laneY = $from['y'] + $this->boxH + (int) round($this->gapY / 2);

            return [
                $fromS,
                [$fromS[0], $laneY],
                [$toN[0], $laneY],
                $toN,
            ];
        }

        if ($dRow === -1 && $dCol === 0) {
            return [$fromN, $toS];
        }

        if ($dRow === -1) {
            $laneY = $to['y'] + $this->boxH + (int) round($this->gapY / 2);

            return [
                $fromN,
                [$fromN[0], $laneY],
                [$toS[0], $laneY],
                $toS,
            ];
        }

        $midY = (int) round(($fromE[1] + $toW[1]) / 2);

        return [
            $fromE,
            [$fromE[0] + 8, $fromE[1]],
            [$fromE[0] + 8, $midY],
            [$toW[0] - 8, $midY],
            [$toW[0] - 8, $toW[1]],
            $toW,
        ];
    }

    /**
     * @param  list<array{0: int, 1: int}>  $points
     * @return list<array{0: int, 1: int}>
     */
    private function shortenEnd(array $points, int $by): array
    {
        $n = count($points);
        if ($n < 2) {
            return $points;
        }

        $a = $points[$n - 2];
        $b = $points[$n - 1];
        $dx = $b[0] - $a[0];
        $dy = $b[1] - $a[1];
        $len = hypot($dx, $dy);

        if ($len <= $by) {
            return $points;
        }

        $points[$n - 1] = [
            (int) round($b[0] - ($dx / $len) * $by),
            (int) round($b[1] - ($dy / $len) * $by),
        ];

        return $points;
    }

    /**
     * @param  list<array{0: int, 1: int}>  $points
     */
    private function labelOnPath(array $points, string $label, int $boxBottom): string
    {
        $best = null;
        $bestLen = -1.0;

        for ($i = 0; $i < count($points) - 1; $i++) {
            $x1 = $points[$i][0];
            $y1 = $points[$i][1];
            $x2 = $points[$i + 1][0];
            $y2 = $points[$i + 1][1];
            $horizontal = abs($y1 - $y2) < 1;
            $len = hypot($x2 - $x1, $y2 - $y1);

            if ($horizontal && $len > $bestLen) {
                $bestLen = $len;
                $belowLane = $y1 >= $boxBottom + 4;
                $best = [(int) round(($x1 + $x2) / 2), $belowLane ? $y1 + 14 : $y1 - 8];
            }
        }

        if ($best === null) {
            $mid = $points[(int) floor(count($points) / 2)];
            $best = [$mid[0], $mid[1] - 8];
        }

        return '<text class="diagram-edge-label" x="'.$best[0].'" y="'.$best[1].'" text-anchor="middle" font-size="11" fill="var(--muted-foreground)">'.$this->xml($label).'</text>';
    }

    /**
     * @param  array{id: string, x: int, y: int, row: int, col: int, label: string, hint: string}  $box
     */
    private function box(array $box): string
    {
        $rx = $box['x'];
        $ry = $box['y'];
        $cx = $rx + (int) round($this->boxW / 2);
        $lines = $this->wrap($box['label'], 16);
        $hasHint = $box['hint'] !== '';
        $startY = $ry + ($hasHint ? 28 : 40);

        $svg = '<rect data-box="'.$this->xml($box['id']).'" x="'.$rx.'" y="'.$ry.'" width="'.$this->boxW.'" height="'.$this->boxH.'" rx="8" fill="var(--surface)" stroke="var(--border-strong)" stroke-width="1.5"/>';

        foreach ($lines as $i => $line) {
            $svg .= '<text x="'.$cx.'" y="'.($startY + ($i * 14)).'" text-anchor="middle" font-size="12" font-weight="600" fill="var(--foreground)">'.$this->xml($line).'</text>';
        }

        if ($hasHint) {
            $hint = $this->wrap($box['hint'], 18)[0];
            $svg .= '<text x="'.$cx.'" y="'.($ry + $this->boxH - 14).'" text-anchor="middle" font-size="10" fill="var(--muted-foreground)">'.$this->xml($hint).'</text>';
        }

        return $svg;
    }

    /**
     * @return list<string>
     */
    private function wrap(string $text, int $max): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        if (mb_strlen($text) <= $max) {
            return [$text];
        }

        $words = explode(' ', $text);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;

            if (mb_strlen($candidate) > $max && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 2);
    }

    private function xml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
