<?php

use App\Services\TipTapRenderer;

it('renders a TipTap image node as an img tag', function () {
    $html = (new TipTapRenderer)->render([
        'type' => 'doc',
        'content' => [[
            'type' => 'image',
            'attrs' => [
                'src' => '/storage/diagrams/pipeline.svg',
                'alt' => 'Alur data tiket layanan',
            ],
        ]],
    ]);

    expect($html)
        ->toContain('<img')
        ->toContain('src="/storage/diagrams/pipeline.svg"')
        ->toContain('alt="Alur data tiket layanan"')
        ->not->toContain('javascript:');
});

it('drops javascript image sources', function () {
    $html = (new TipTapRenderer)->render([
        'type' => 'doc',
        'content' => [[
            'type' => 'image',
            'attrs' => [
                'src' => 'javascript:alert(1)',
                'alt' => 'bahaya',
            ],
        ]],
    ]);

    expect($html)->not->toContain('<img')->not->toContain('javascript:');
});

it('renders a structured diagram node as an svg figure', function () {
    $html = (new TipTapRenderer)->render([
        'type' => 'doc',
        'content' => [[
            'type' => 'diagram',
            'attrs' => [
                'caption' => 'Pipeline kasus tiket layanan',
                'nodes' => [
                    ['id' => 'kasus', 'label' => 'Kasus', 'hint' => 'metrik SLA'],
                    ['id' => 'data', 'label' => 'Data'],
                    ['id' => 'model', 'label' => 'Model'],
                    ['id' => 'eval', 'label' => 'Evaluasi'],
                ],
            ],
        ]],
    ]);

    expect($html)
        ->toContain('<figure class="lesson-diagram">')
        ->toContain('<svg')
        ->toContain('Pipeline kasus tiket layanan')
        ->toContain('Kasus')
        ->toContain('Evaluasi')
        ->not->toContain('```mermaid');
});

it('wraps tables so Lesson CSS can space cells', function () {
    $html = (new TipTapRenderer)->render([
        'type' => 'doc',
        'content' => [[
            'type' => 'table',
            'content' => [[
                'type' => 'tableRow',
                'content' => [
                    ['type' => 'tableHeader', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Tugas']]]]],
                    ['type' => 'tableHeader', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Keluaran']]]]],
                    ['type' => 'tableHeader', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Evaluasi']]]]],
                ],
            ], [
                'type' => 'tableRow',
                'content' => [
                    ['type' => 'tableCell', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Klasifikasi (kasus ini)']]]]],
                    ['type' => 'tableCell', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Satu kelas atau ragu']]]]],
                    ['type' => 'tableCell', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Macro-F1, recall mahal']]]]],
                ],
            ]],
        ]],
    ]);

    expect($html)
        ->toContain('<div class="lesson-table-wrap"><table>')
        ->toContain('</th>')
        ->toContain('</td>')
        ->toContain('Klasifikasi (kasus ini)')
        ->not->toContain('ini)Satu');
});

it('includes diagram captions in plain text for the Tutor body', function () {
    $text = (new TipTapRenderer)->plainText([
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'Lihat diagram.']],
            ],
            [
                'type' => 'diagram',
                'attrs' => [
                    'caption' => 'Loop latih-uji',
                    'nodes' => [
                        ['id' => 'latih', 'label' => 'Latih'],
                        ['id' => 'uji', 'label' => 'Uji'],
                    ],
                ],
            ],
        ],
    ]);

    expect($text)->toContain('Lihat diagram.')->toContain('Loop latih-uji');
});
