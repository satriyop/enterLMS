<?php

namespace App\Services;

/**
 * Turns a structured TipTap `diagram` node into an inline SVG figure.
 *
 * Seeded Lessons store boxes and edges as data. The Learner sees a real
 * figure in `rich_content_html`, not mermaid or ASCII sitting in a code block.
 */
class LessonDiagramRenderer
{
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

        $boxW = 168;
        $boxH = 72;
        $gapX = 40;
        $gapY = 56;
        $pad = 20;

        $positions = [];
        $maxRow = 0;
        $index = 0;

        foreach ($nodes as $id => $node) {
            $col = $index % $columns;
            $row = intdiv($index, $columns);
            $positions[$id] = [
                'x' => $pad + ($col * ($boxW + $gapX)),
                'y' => $pad + ($row * ($boxH + $gapY)),
                'row' => $row,
                'col' => $col,
                'label' => $node['label'],
                'hint' => $node['hint'],
            ];
            $maxRow = max($maxRow, $row);
            $index++;
        }

        $width = $pad + ($columns * $boxW) + (($columns - 1) * $gapX) + $pad;
        $height = $pad + (($maxRow + 1) * $boxH) + ($maxRow * $gapY) + $pad + 8;

        $markerId = 'darrow-'.substr(hash('sha256', $caption.implode(',', array_keys($nodes))), 0, 10);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$width.' '.$height.'" width="'.$width.'" height="'.$height.'" role="img" aria-label="'.$this->xml($alt).'" class="lesson-diagram-svg">';
        $svg .= '<defs><marker id="'.$markerId.'" markerWidth="8" markerHeight="8" refX="6" refY="4" orient="auto"><path d="M0,0 L8,4 L0,8 z" fill="var(--primary)"/></marker></defs>';

        foreach ($edges as $edge) {
            if (! isset($positions[$edge['from']], $positions[$edge['to']])) {
                continue;
            }

            $svg .= $this->edgePath($positions[$edge['from']], $positions[$edge['to']], $boxW, $boxH, $markerId, $edge['label']);
        }

        foreach ($positions as $box) {
            $svg .= $this->box($box, $boxW, $boxH);
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
     * @param  array{x: int, y: int, row: int, col: int, label: string, hint: string}  $from
     * @param  array{x: int, y: int, row: int, col: int, label: string, hint: string}  $to
     */
    private function edgePath(array $from, array $to, int $boxW, int $boxH, string $markerId, string $label): string
    {
        $x1 = $from['x'] + $boxW;
        $y1 = $from['y'] + (int) round($boxH / 2);
        $x2 = $to['x'];
        $y2 = $to['y'] + (int) round($boxH / 2);

        if ($to['row'] > $from['row'] || $to['x'] <= $from['x']) {
            $x1 = $from['x'] + (int) round($boxW / 2);
            $y1 = $from['y'] + $boxH;
            $x2 = $to['x'] + (int) round($boxW / 2);
            $y2 = $to['y'];
        }

        $path = '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="var(--primary)" stroke-width="2" marker-end="url(#'.$markerId.')"/>';

        if ($label !== '') {
            $mx = (int) round(($x1 + $x2) / 2);
            $my = (int) round(($y1 + $y2) / 2) - 6;
            $path .= '<text x="'.$mx.'" y="'.$my.'" text-anchor="middle" font-size="11" fill="var(--muted-foreground)">'.$this->xml($label).'</text>';
        }

        return $path;
    }

    /**
     * @param  array{x: int, y: int, row: int, col: int, label: string, hint: string}  $box
     */
    private function box(array $box, int $boxW, int $boxH): string
    {
        $rx = $box['x'];
        $ry = $box['y'];
        $cx = $rx + (int) round($boxW / 2);
        $lines = $this->wrap($box['label'], 18);
        $hasHint = $box['hint'] !== '';
        $startY = $ry + ($hasHint ? 26 : 34);

        $svg = '<rect x="'.$rx.'" y="'.$ry.'" width="'.$boxW.'" height="'.$boxH.'" rx="8" fill="var(--surface)" stroke="var(--border-strong)" stroke-width="1.5"/>';

        foreach ($lines as $i => $line) {
            $svg .= '<text x="'.$cx.'" y="'.($startY + ($i * 14)).'" text-anchor="middle" font-size="12" font-weight="600" fill="var(--foreground)">'.$this->xml($line).'</text>';
        }

        if ($hasHint) {
            $svg .= '<text x="'.$cx.'" y="'.($ry + $boxH - 14).'" text-anchor="middle" font-size="10" fill="var(--muted-foreground)">'.$this->xml($box['hint']).'</text>';
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
