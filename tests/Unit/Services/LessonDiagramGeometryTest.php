<?php

use App\Services\TipTapRenderer;
use Tests\Support\SvgDiagramGeometry;

function renderDiagramHtml(array $attrs): string
{
    return (new TipTapRenderer)->render([
        'type' => 'doc',
        'content' => [[
            'type' => 'diagram',
            'attrs' => $attrs,
        ]],
    ]);
}

function assertDiagramGeometryClean(string $html, string $label): void
{
    expect($html)->toContain('<figure class="lesson-diagram">')->toContain('<svg');

    $problems = SvgDiagramGeometry::problems(SvgDiagramGeometry::parse($html));

    expect($problems)->toBe([], $label.': '.implode('; ', $problems));
}

it('routes the six-step pipeline wrap without diagonals or box cuts', function () {
    $html = renderDiagramHtml([
        'caption' => 'Pipeline yang akan kamu pelajari: dari kasus ke iterasi, selalu untuk tiket layanan.',
        'nodes' => [
            ['id' => 'kasus', 'label' => 'Kasus', 'hint' => 'metrik'],
            ['id' => 'data', 'label' => 'Data', 'hint' => 'split'],
            ['id' => 'model', 'label' => 'Model', 'hint' => 'baseline'],
            ['id' => 'latih', 'label' => 'Latih', 'hint' => 'loss'],
            ['id' => 'eval', 'label' => 'Evaluasi', 'hint' => 'macro-F1'],
            ['id' => 'serve', 'label' => 'Serving', 'hint' => 'iterasi'],
        ],
        'columns' => 3,
    ]);

    assertDiagramGeometryClean($html, 'pipeline wrap');
    expect($html)->toContain('</svg><figcaption>')->toContain('Kasus')->toContain('Serving');
});

it('routes a same-row serving branch around the middle antrian box', function () {
    $html = renderDiagramHtml([
        'caption' => 'Kontrak hidup: tiket masuk, prediksi atau fallback manusia.',
        'nodes' => [
            ['id' => 'api', 'label' => 'API tiket', 'hint' => 'judul+isi'],
            ['id' => 'mdl', 'label' => 'Model vN', 'hint' => '≤200 ms'],
            ['id' => 'q', 'label' => 'Antrian', 'hint' => 'label'],
            ['id' => 'fb', 'label' => 'Fallback', 'hint' => 'ragu'],
        ],
        'edges' => [
            ['from' => 'api', 'to' => 'mdl'],
            ['from' => 'mdl', 'to' => 'q', 'label' => 'yakin'],
            ['from' => 'mdl', 'to' => 'fb', 'label' => 'ragu'],
            ['from' => 'fb', 'to' => 'q'],
        ],
        'columns' => 4,
    ]);

    $parsed = SvgDiagramGeometry::parse($html);
    expect($parsed['boxes'])->toHaveCount(4);

    assertDiagramGeometryClean($html, 'serving branch');

    $antrian = $parsed['boxes'][2];
    $throughMiddle = 0;
    foreach ($parsed['segments'] as $seg) {
        if (SvgDiagramGeometry::segmentHitsBoxInterior($seg[0], $seg[1], $seg[2], $seg[3], $antrian)) {
            $start = SvgDiagramGeometry::boxIndexAt($parsed['boxes'], $seg[0], $seg[1]);
            $end = SvgDiagramGeometry::boxIndexAt($parsed['boxes'], $seg[2], $seg[3]);
            if ($start !== 2 && $end !== 2) {
                $throughMiddle++;
            }
        }
    }

    expect($throughMiddle)->toBe(0, 'branch to Fallback must not draw through Antrian');

    $skip = $parsed['edges']['mdl:fb'] ?? [];
    expect($skip)->not->toBeEmpty()
        ->and(SvgDiagramGeometry::lastSegmentLength($skip))->toBeGreaterThanOrEqual(20.0);

    $ragu = collect($parsed['labels'])->firstWhere('text', 'ragu');
    expect($ragu)->not->toBeNull()
        ->and(SvgDiagramGeometry::labelHitsBox($ragu['x'], $ragu['y'], $parsed['boxes']))->toBeFalse();
});

it('routes a wrap-row connector in the gap between rows', function () {
    $html = renderDiagramHtml([
        'caption' => 'Wrap row',
        'nodes' => [
            ['id' => 'a', 'label' => 'A'],
            ['id' => 'b', 'label' => 'B'],
            ['id' => 'c', 'label' => 'C'],
            ['id' => 'd', 'label' => 'D'],
        ],
        'columns' => 3,
    ]);

    assertDiagramGeometryClean($html, 'wrap row');
});

it('routes a back-edge loop under the row', function () {
    $html = renderDiagramHtml([
        'caption' => 'Iterasi: hidup menghasilkan override, override menjadi data, data menjadi model vN+1.',
        'nodes' => [
            ['id' => 'hidup', 'label' => 'Serving vN', 'hint' => 'prediksi'],
            ['id' => 'ov', 'label' => 'Override', 'hint' => 'petugas'],
            ['id' => 'data', 'label' => 'Data baru', 'hint' => 'split baru'],
            ['id' => 'vnext', 'label' => 'Model vN+1', 'hint' => 'bayangan'],
        ],
        'edges' => [
            ['from' => 'hidup', 'to' => 'ov'],
            ['from' => 'ov', 'to' => 'data'],
            ['from' => 'data', 'to' => 'vnext'],
            ['from' => 'vnext', 'to' => 'hidup', 'label' => 'ulang'],
        ],
        'columns' => 4,
    ]);

    assertDiagramGeometryClean($html, 'back-edge loop');
    expect($html)->toContain('Serving vN')->toContain('Model vN+1');

    $parsed = SvgDiagramGeometry::parse($html);
    $loop = $parsed['edges']['vnext:hidup'] ?? [];
    expect($loop)->not->toBeEmpty()
        ->and(SvgDiagramGeometry::lastSegmentLength($loop))->toBeGreaterThanOrEqual(20.0);

    $ulang = collect($parsed['labels'])->firstWhere('text', 'ulang');
    expect($ulang)->not->toBeNull()
        ->and(SvgDiagramGeometry::labelHitsBox($ulang['x'], $ulang['y'], $parsed['boxes']))->toBeFalse();
});

it('offsets same-row skip rails so two skips are not collinear', function () {
    $html = renderDiagramHtml([
        'caption' => 'Metrik yang selaras: model diukur per kelas; bisnis diukur di antrian dan SLA.',
        'nodes' => [
            ['id' => 'pred', 'label' => 'Prediksi', 'hint' => 'label + skor'],
            ['id' => 'f1', 'label' => 'Macro-F1', 'hint' => '5 kelas'],
            ['id' => 'rec', 'label' => 'Recall mahal', 'hint' => 'perangkat'],
            ['id' => 'sla', 'label' => 'SLA antrian', 'hint' => 'hop & waktu'],
        ],
        'edges' => [
            ['from' => 'pred', 'to' => 'f1'],
            ['from' => 'pred', 'to' => 'rec'],
            ['from' => 'f1', 'to' => 'sla'],
            ['from' => 'rec', 'to' => 'sla'],
        ],
        'columns' => 4,
    ]);

    assertDiagramGeometryClean($html, 'metrics skips');

    $parsed = SvgDiagramGeometry::parse($html);
    $predRec = SvgDiagramGeometry::railY($parsed['edges']['pred:rec'] ?? []);
    $f1Sla = SvgDiagramGeometry::railY($parsed['edges']['f1:sla'] ?? []);

    expect($predRec)->not->toBeNull()
        ->and($f1Sla)->not->toBeNull()
        ->and($predRec)->not->toEqual($f1Sla);
});
