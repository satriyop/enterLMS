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
