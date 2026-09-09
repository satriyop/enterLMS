<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Services\TipTapRenderer;
use Database\Seeders\AiForCorporateCourseSeeder;
use Database\Seeders\FreeFlowDemoSeeder;

it('seeds an extensive Bahasa Indonesia NEX corporate AI-adoption framework', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiForCorporateCourseSeeder::class);

    $course = Course::query()->where('title', AiForCorporateCourseSeeder::COURSE_TITLE)->first();

    expect($course)->not->toBeNull();

    $sections = CourseSection::query()
        ->where('course_id', $course->id)
        ->orderBy('order')
        ->get();

    expect($sections->pluck('title')->all())->toBe(AiForCorporateCourseSeeder::SECTION_TITLES)
        ->and($sections)->toHaveCount(8);

    $lessons = Lesson::query()
        ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
        ->where('course_sections.course_id', $course->id)
        ->whereNull('lessons.deleted_at')
        ->whereNull('course_sections.deleted_at')
        ->orderBy('course_sections.order')
        ->orderBy('lessons.order')
        ->get(['lessons.id', 'lessons.title', 'lessons.rich_content', 'lessons.estimated_duration_minutes']);

    expect($lessons->pluck('title')->all())->toBe(AiForCorporateCourseSeeder::LESSON_TITLES)
        ->and($lessons)->toHaveCount(32)
        ->and($lessons->count())->toBeGreaterThan(10)
        ->and((int) $course->estimated_duration_minutes)->toBeGreaterThan(90)
        ->and((int) $course->estimated_duration_minutes)->toBeGreaterThanOrEqual(360)
        ->and($lessons->sum('estimated_duration_minutes'))->toBeGreaterThanOrEqual(360);

    $bodies = $lessons->map(function (Lesson $lesson) {
        return $lesson->title.' '.(new TipTapRenderer)->plainText($lesson->rich_content);
    })->implode("\n");

    expect($bodies)
        ->toContain('NEX')
        ->toContain('Energi Masa Depan')
        ->toContain('peta jalan')
        ->toContain('nex-project')
        ->toContain('deliverable')
        ->toContain('Lembar keputusan')
        ->toContain('UNESCO')
        ->toContain('UU 27/2022')
        ->toContain('PDP')
        ->toContain('wewenang')
        ->toContain('untuk')
        ->toContain('yang')
        ->toContain('tidak')
        ->not->toContain('```mermaid');

    $renderer = new TipTapRenderer;
    $intro = $lessons->firstWhere('title', AiForCorporateCourseSeeder::PREVIEW_LESSON_TITLE);
    $roadmap = $lessons->firstWhere('title', 'Peta jalan empat tahap NEX');
    $deliverable = $lessons->firstWhere('title', 'Peta jalan 24 bulan NEX');
    $tigaMeja = $lessons->firstWhere('title', 'Tiga meja: operasional, tata kelola, SDM');

    expect($intro)->not->toBeNull()
        ->and($roadmap)->not->toBeNull()
        ->and($deliverable)->not->toBeNull()
        ->and($tigaMeja)->not->toBeNull();

    $introHtml = $renderer->render($intro->rich_content);
    $roadmapHtml = $renderer->render($roadmap->rich_content);
    $deliverableHtml = $renderer->render($deliverable->rich_content);
    $tigaMejaHtml = $renderer->render($tigaMeja->rich_content);

    expect($introHtml)
        ->toContain('<h1>')
        ->toContain('<p>')
        ->toContain('<ul>')
        ->toContain('<svg')
        ->toContain('<figure class="lesson-diagram">')
        ->toContain('</svg><figcaption>')
        ->toContain('Direksi')
        ->and($roadmapHtml)
        ->toContain('<svg')
        ->toContain('<figure class="lesson-diagram">')
        ->toContain('</svg><figcaption>')
        ->toContain('>Fondasi<')
        ->toContain('>Pilot<')
        ->and($deliverableHtml)
        ->toContain('<svg')
        ->toContain('<figure class="lesson-diagram">')
        ->toContain('</svg><figcaption>')
        ->and($tigaMejaHtml)
        ->toContain('<svg')
        ->toContain('<figure class="lesson-diagram">');

    $facts = $lessons->firstWhere('title', 'NEX Energi Masa Depan sebagai kasus berjalan');
    $factsHtml = $renderer->render($facts->rich_content);

    expect($factsHtml)
        ->toContain('<div class="lesson-table-wrap"><table>')
        ->toContain('<th>')
        ->toContain('nex-project')
        ->not->toContain('```mermaid');

    $diagramLessons = $lessons->filter(function (Lesson $lesson) use ($renderer) {
        $html = $renderer->render($lesson->rich_content);

        return str_contains($html, '<svg') || str_contains($html, '<img');
    });

    expect($diagramLessons->pluck('title')->all())
        ->toEqualCanonicalizing(AiForCorporateCourseSeeder::DIAGRAM_LESSON_TITLES)
        ->and($diagramLessons->count())->toBeGreaterThanOrEqual(8);

    $quiz = Assessment::query()
        ->where('course_id', $course->id)
        ->where('title', AiForCorporateCourseSeeder::QUIZ_TITLE)
        ->with('questions')
        ->first();

    expect($quiz)->not->toBeNull()
        ->and($quiz->is_required)->toBeTrue()
        ->and($quiz->status)->toBe('published')
        ->and($quiz->questions->count())->toBeGreaterThanOrEqual(8);

    $questionText = $quiz->questions->pluck('question_text')->implode(' ');

    expect($questionText)
        ->toContain('peta jalan')
        ->toContain('NEX')
        ->toContain('wewenang')
        ->toContain('risiko')
        ->toContain('yang');
});

it('replaces stale lesson bodies on a second seed even when titles match', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiForCorporateCourseSeeder::class);

    $course = Course::query()->where('title', AiForCorporateCourseSeeder::COURSE_TITLE)->first();
    $intro = $course->lessons()->where('lessons.title', AiForCorporateCourseSeeder::PREVIEW_LESSON_TITLE)->first();

    expect($intro)->not->toBeNull();

    $intro->update([
        'rich_content' => [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => 'Teks lama tanpa konteks korporat.']],
                ],
            ],
        ],
    ]);

    expect(json_encode($intro->fresh()->rich_content))
        ->not->toContain('NEX')
        ->not->toContain('peta jalan');

    $this->seed(AiForCorporateCourseSeeder::class);

    $replaced = $course->lessons()->where('lessons.title', AiForCorporateCourseSeeder::PREVIEW_LESSON_TITLE)->first();
    $json = json_encode($replaced?->rich_content ?? []);

    expect($json)
        ->toContain('NEX')
        ->toContain('peta jalan')
        ->toContain('Energi Masa Depan');
});
