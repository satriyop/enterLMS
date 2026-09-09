<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Services\TipTapRenderer;
use Database\Seeders\AiUseCaseCourseSeeder;
use Database\Seeders\FreeFlowDemoSeeder;
use Tests\Support\SvgDiagramGeometry;

it('renders every Course diagram with orthogonal arrows that miss other boxes', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiUseCaseCourseSeeder::class);

    $course = Course::query()->where('title', AiUseCaseCourseSeeder::COURSE_TITLE)->first();
    $renderer = new TipTapRenderer;
    $dump = '';
    $checked = 0;

    $lessons = Lesson::query()
        ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
        ->where('course_sections.course_id', $course->id)
        ->orderBy('course_sections.order')
        ->orderBy('lessons.order')
        ->get(['lessons.id', 'lessons.title', 'lessons.rich_content']);

    foreach ($lessons as $lesson) {
        $html = $renderer->render($lesson->rich_content);

        if (! str_contains($html, '<figure class="lesson-diagram">')) {
            continue;
        }

        $checked++;
        $dump .= '<h2>'.e($lesson->title).'</h2>'.$html;

        $parsed = SvgDiagramGeometry::parse($html);
        $problems = SvgDiagramGeometry::problems($parsed);

        expect($problems)->toBe([], $lesson->title.': '.implode('; ', $problems));
        expect($html)->toContain('</svg><figcaption>');

        foreach ($parsed['edges'] as $key => $segments) {
            $railY = SvgDiagramGeometry::railY($segments);
            if ($railY === null) {
                continue;
            }

            $stem = SvgDiagramGeometry::lastSegmentLength($segments);
            if ($stem > 0.5 && $stem < 20.0) {
                expect($stem)->toBeGreaterThanOrEqual(20.0, $lesson->title.' '.$key.' stem');
            }
        }

        foreach ($parsed['labels'] as $label) {
            if (in_array($label['text'], ['ragu', 'ulang', 'yakin', 'bocor', 'sah', 'lulus', 'gagal', 'epoch', 'cocok', 'salah'], true)) {
                expect(SvgDiagramGeometry::labelHitsBox($label['x'], $label['y'], $parsed['boxes']))
                    ->toBeFalse($lesson->title.' label '.$label['text'].' overlaps a box');
            }
        }
    }

    expect($checked)->toBe(count(AiUseCaseCourseSeeder::DIAGRAM_LESSON_TITLES));

    $dumpPath = getenv('DIAGRAM_DUMP');
    if (is_string($dumpPath) && $dumpPath !== '') {
        file_put_contents($dumpPath, '<!doctype html><meta charset="utf-8"><style>
            body{font-family:sans-serif;background:#f8f7f4;color:#191713;padding:24px}
            figure.lesson-diagram{margin:1.5rem 0;padding:1rem;background:#fff;border:1px solid #e4e0d7;border-radius:8px}
            figcaption{margin-top:.75rem;text-align:center;color:#5f5b52}
            svg{width:100%;height:auto}
        </style>'.$dump);
    }
});
