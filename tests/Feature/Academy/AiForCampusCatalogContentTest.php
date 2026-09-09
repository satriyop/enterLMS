<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Services\TipTapRenderer;
use Database\Seeders\AiForCampusCourseSeeder;
use Database\Seeders\FreeFlowDemoSeeder;

it('seeds an extensive Bahasa Indonesia Unissula campus AI-adoption framework', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiForCampusCourseSeeder::class);

    $course = Course::query()->where('title', AiForCampusCourseSeeder::COURSE_TITLE)->first();

    expect($course)->not->toBeNull();

    $sections = CourseSection::query()
        ->where('course_id', $course->id)
        ->orderBy('order')
        ->get();

    expect($sections->pluck('title')->all())->toBe(AiForCampusCourseSeeder::SECTION_TITLES)
        ->and($sections)->toHaveCount(8);

    $lessons = Lesson::query()
        ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
        ->where('course_sections.course_id', $course->id)
        ->whereNull('lessons.deleted_at')
        ->whereNull('course_sections.deleted_at')
        ->orderBy('course_sections.order')
        ->orderBy('lessons.order')
        ->get(['lessons.id', 'lessons.title', 'lessons.content_type', 'lessons.youtube_url', 'lessons.rich_content', 'lessons.estimated_duration_minutes']);

    expect($lessons->pluck('title')->all())->toBe(AiForCampusCourseSeeder::LESSON_TITLES)
        ->and($lessons)->toHaveCount(34)
        ->and($lessons->count())->toBeGreaterThan(10)
        ->and((int) $course->estimated_duration_minutes)->toBeGreaterThan(90)
        ->and((int) $course->estimated_duration_minutes)->toBeGreaterThanOrEqual(360)
        ->and($lessons->sum('estimated_duration_minutes'))->toBeGreaterThanOrEqual(360);

    $bodies = $lessons->map(function (Lesson $lesson) {
        return $lesson->title.' '.(new TipTapRenderer)->plainText($lesson->rich_content);
    })->implode("\n");

    expect($bodies)
        ->toContain('Unissula')
        ->toContain('UNISSULA')
        ->toContain('peta jalan')
        ->toContain('YBWSA')
        ->toContain('BudAI')
        ->toContain('Khaira Ummah')
        ->toContain('Gema Kampus')
        ->toContain('Baca Tugas')
        ->toContain('Bu Laila')
        ->toContain('Panduan GenAI')
        ->toContain('Stefania Giannini')
        ->toContain('RSUD Banyumas')
        ->toContain('bukan RSI Sultan Agung')
        ->toContain('deliverable')
        ->toContain('Lembar keputusan')
        ->toContain('UNESCO')
        ->toContain('UU 27/2022')
        ->toContain('PDP')
        ->toContain('Permendikbudristek 39/2021')
        ->toContain('wewenang')
        ->toContain('untuk')
        ->toContain('yang')
        ->toContain('tidak')
        ->not->toContain('```mermaid');

    $renderer = new TipTapRenderer;
    $intro = $lessons->firstWhere('title', AiForCampusCourseSeeder::PREVIEW_LESSON_TITLE);
    $roadmap = $lessons->firstWhere('title', 'Peta jalan empat tahap Unissula');
    $deliverable = $lessons->firstWhere('title', 'Peta jalan 24 bulan Unissula');
    $tigaMeja = $lessons->firstWhere('title', 'Tiga meja: pengajaran, tata kelola, operasional');

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
        ->toContain('YBWSA')
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

    $facts = $lessons->firstWhere('title', 'Unissula sebagai kasus berjalan');
    $factsHtml = $renderer->render($facts->rich_content);

    expect($factsHtml)
        ->toContain('<div class="lesson-table-wrap"><table>')
        ->toContain('<th>')
        ->toContain('BAN-PT')
        ->not->toContain('```mermaid');

    $diagramLessons = $lessons->filter(function (Lesson $lesson) use ($renderer) {
        $html = $renderer->render($lesson->rich_content);

        return str_contains($html, '<svg') || str_contains($html, '<img');
    });

    expect($diagramLessons->pluck('title')->all())
        ->toEqualCanonicalizing(AiForCampusCourseSeeder::DIAGRAM_LESSON_TITLES)
        ->and($diagramLessons->count())->toBeGreaterThanOrEqual(8);

    $youtube = $lessons->where('content_type', 'youtube');

    expect($youtube->pluck('title')->all())
        ->toEqualCanonicalizing(AiForCampusCourseSeeder::YOUTUBE_LESSON_TITLES)
        ->and($youtube->pluck('youtube_url')->all())->toEqualCanonicalizing([
            AiForCampusCourseSeeder::YOUTUBE_UNESCO_GIANNINI,
            AiForCampusCourseSeeder::YOUTUBE_UNESCO_DECODING,
            AiForCampusCourseSeeder::YOUTUBE_PDP_RS_PENDIDIKAN,
        ]);

    $quiz = Assessment::query()
        ->where('course_id', $course->id)
        ->where('title', AiForCampusCourseSeeder::QUIZ_TITLE)
        ->with('questions')
        ->first();

    expect($quiz)->not->toBeNull()
        ->and($quiz->is_required)->toBeTrue()
        ->and($quiz->status)->toBe('published')
        ->and($quiz->questions->count())->toBeGreaterThanOrEqual(8);

    $questionText = $quiz->questions->pluck('question_text')->implode(' ');

    expect($questionText)
        ->toContain('Unissula')
        ->toContain('peta jalan')
        ->toContain('wewenang')
        ->toContain('risiko')
        ->toContain('Gema Kampus')
        ->toContain('yang');
});

it('replaces stale lesson bodies on a second seed even when titles match', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiForCampusCourseSeeder::class);

    $course = Course::query()->where('title', AiForCampusCourseSeeder::COURSE_TITLE)->first();
    $intro = $course->lessons()->where('lessons.title', AiForCampusCourseSeeder::PREVIEW_LESSON_TITLE)->first();

    expect($intro)->not->toBeNull();

    $intro->update([
        'rich_content' => [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => 'Teks lama tanpa konteks kampus.']],
                ],
            ],
        ],
    ]);

    expect(json_encode($intro->fresh()->rich_content))
        ->not->toContain('Unissula')
        ->not->toContain('peta jalan');

    $this->seed(AiForCampusCourseSeeder::class);

    $replaced = $course->lessons()->where('lessons.title', AiForCampusCourseSeeder::PREVIEW_LESSON_TITLE)->first();
    $json = json_encode($replaced?->rich_content ?? []);

    expect($json)
        ->toContain('Unissula')
        ->toContain('peta jalan')
        ->toContain('UNESCO')
        ->toContain('Gema Kampus');
});
