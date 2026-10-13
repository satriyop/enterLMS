<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Services\TipTapRenderer;
use Database\Seeders\AiEngineerCourseSeeder;
use Database\Seeders\FreeFlowDemoSeeder;
use Tests\Support\SvgDiagramGeometry;

it('renders every AI Engineer diagram with orthogonal arrows that miss other boxes', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiEngineerCourseSeeder::class);

    $course = Course::query()->where('title', AiEngineerCourseSeeder::COURSE_TITLE)->first();
    $renderer = new TipTapRenderer;
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
        $parsed = SvgDiagramGeometry::parse($html);
        $problems = SvgDiagramGeometry::problems($parsed);

        expect($problems)->toBe([], $lesson->title.': '.implode('; ', $problems));
        expect($html)->toContain('</svg><figcaption>');
    }

    expect($checked)->toBe(count(AiEngineerCourseSeeder::DIAGRAM_LESSON_TITLES));
});
