<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Services\TipTapRenderer;
use Database\Seeders\AiForMilitaryCourseSeeder;
use Database\Seeders\FreeFlowDemoSeeder;

it('seeds an extensive Bahasa Indonesia TNI AD AI-adoption framework', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiForMilitaryCourseSeeder::class);

    $course = Course::query()->where('title', AiForMilitaryCourseSeeder::COURSE_TITLE)->first();

    expect($course)->not->toBeNull();

    $sections = CourseSection::query()
        ->where('course_id', $course->id)
        ->orderBy('order')
        ->get();

    expect($sections->pluck('title')->all())->toBe(AiForMilitaryCourseSeeder::SECTION_TITLES)
        ->and($sections)->toHaveCount(8);

    $lessons = Lesson::query()
        ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
        ->where('course_sections.course_id', $course->id)
        ->whereNull('lessons.deleted_at')
        ->whereNull('course_sections.deleted_at')
        ->orderBy('course_sections.order')
        ->orderBy('lessons.order')
        ->get(['lessons.id', 'lessons.title', 'lessons.rich_content', 'lessons.youtube_url', 'lessons.estimated_duration_minutes']);

    expect($lessons->pluck('title')->all())->toBe(AiForMilitaryCourseSeeder::LESSON_TITLES)
        ->and($lessons)->toHaveCount(32)
        ->and($lessons->count())->toBeGreaterThan(10)
        ->and((int) $course->estimated_duration_minutes)->toBeGreaterThan(90)
        ->and((int) $course->estimated_duration_minutes)->toBeGreaterThanOrEqual(360)
        ->and($lessons->sum('estimated_duration_minutes'))->toBeGreaterThanOrEqual(360);

    $bodies = $lessons->map(function (Lesson $lesson) {
        return $lesson->title.' '.(new TipTapRenderer)->plainText($lesson->rich_content);
    })->implode("\n");

    expect($bodies)
        ->toContain('TNI AD')
        ->toContain('Korem 074')
        ->toContain('Korem 072')
        ->toContain('Sisfomil')
        ->toContain('Sipamungkas')
        ->toContain('peta jalan')
        ->toContain('deliverable')
        ->toContain('Lembar keputusan')
        ->toContain('NATO')
        ->toContain('UU 27/2022')
        ->toContain('PDP')
        ->toContain('Perpres 95/2018')
        ->toContain('wewenang')
        ->toContain('use case')
        ->toContain('untuk')
        ->toContain('yang')
        ->toContain('tidak')
        ->toContain('Tonton, lalu jawab')
        ->toContain('Google Cloud')
        ->toContain('TVRI')
        ->toContain('ICRC')
        ->toContain('Kemkomdigi')
        ->not->toContain('```mermaid');

    $youtubeLessons = $lessons->filter(fn (Lesson $lesson) => filled($lesson->youtube_url));

    expect($youtubeLessons->pluck('title')->all())
        ->toEqualCanonicalizing(AiForMilitaryCourseSeeder::YOUTUBE_LESSON_TITLES)
        ->and($youtubeLessons->firstWhere('title', 'Bukan beli chatbot lalu selesai')?->youtube_url)
        ->toBe(AiForMilitaryCourseSeeder::YOUTUBE_AGENTS)
        ->and($youtubeLessons->firstWhere('title', 'Use case rapat, surat, dan pelatihan')?->youtube_url)
        ->toBe(AiForMilitaryCourseSeeder::YOUTUBE_TMMD)
        ->and($youtubeLessons->firstWhere('title', 'Use case yang tidak dimulai: intel, senjata, targeting')?->youtube_url)
        ->toBe(AiForMilitaryCourseSeeder::YOUTUBE_ICRC)
        ->and($youtubeLessons->firstWhere('title', 'SPBE, BSSN, dan vendor di luar kesatuan')?->youtube_url)
        ->toBe(AiForMilitaryCourseSeeder::YOUTUBE_KOMINFO_ETIKA);

    $renderer = new TipTapRenderer;
    $intro = $lessons->firstWhere('title', AiForMilitaryCourseSeeder::PREVIEW_LESSON_TITLE);
    $roadmap = $lessons->firstWhere('title', 'Peta jalan empat tahap TNI AD');
    $deliverable = $lessons->firstWhere('title', 'Peta jalan 24 bulan TNI AD');
    $tigaMeja = $lessons->firstWhere('title', 'Tiga meja: tugas, komando, personel');
    $useCase = $lessons->firstWhere('title', 'Cara menemukan use case AI yang andal');

    expect($intro)->not->toBeNull()
        ->and($roadmap)->not->toBeNull()
        ->and($deliverable)->not->toBeNull()
        ->and($tigaMeja)->not->toBeNull()
        ->and($useCase)->not->toBeNull();

    $introHtml = $renderer->render($intro->rich_content);
    $roadmapHtml = $renderer->render($roadmap->rich_content);
    $deliverableHtml = $renderer->render($deliverable->rich_content);
    $tigaMejaHtml = $renderer->render($tigaMeja->rich_content);
    $useCaseHtml = $renderer->render($useCase->rich_content);

    expect($introHtml)
        ->toContain('<h1>')
        ->toContain('<p>')
        ->toContain('<ul>')
        ->toContain('<svg')
        ->toContain('<figure class="lesson-diagram">')
        ->toContain('</svg><figcaption>')
        ->toContain('Danrem')
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
        ->toContain('<figure class="lesson-diagram">')
        ->and($useCaseHtml)
        ->toContain('<svg')
        ->toContain('<figure class="lesson-diagram">');

    $facts = $lessons->firstWhere('title', 'TNI AD sebagai kasus berjalan');
    $factsHtml = $renderer->render($facts->rich_content);

    expect($factsHtml)
        ->toContain('<div class="lesson-table-wrap"><table>')
        ->toContain('<th>')
        ->toContain('Sisfomil')
        ->not->toContain('```mermaid');

    $diagramLessons = $lessons->filter(function (Lesson $lesson) use ($renderer) {
        $html = $renderer->render($lesson->rich_content);

        return str_contains($html, '<svg') || str_contains($html, '<img');
    });

    expect($diagramLessons->pluck('title')->all())
        ->toEqualCanonicalizing(AiForMilitaryCourseSeeder::DIAGRAM_LESSON_TITLES)
        ->and($diagramLessons->count())->toBeGreaterThanOrEqual(8);

    $quiz = Assessment::query()
        ->where('course_id', $course->id)
        ->where('title', AiForMilitaryCourseSeeder::QUIZ_TITLE)
        ->with('questions')
        ->first();

    expect($quiz)->not->toBeNull()
        ->and($quiz->is_required)->toBeTrue()
        ->and($quiz->status)->toBe('published')
        ->and($quiz->questions->count())->toBeGreaterThanOrEqual(8);

    $questionText = $quiz->questions->pluck('question_text')->implode(' ');

    expect($questionText)
        ->toContain('peta jalan')
        ->toContain('use case')
        ->toContain('wewenang')
        ->toContain('risiko')
        ->toContain('yang');
});

it('replaces stale lesson bodies on a second seed even when titles match', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiForMilitaryCourseSeeder::class);

    $course = Course::query()->where('title', AiForMilitaryCourseSeeder::COURSE_TITLE)->first();
    $intro = $course->lessons()->where('lessons.title', AiForMilitaryCourseSeeder::PREVIEW_LESSON_TITLE)->first();

    expect($intro)->not->toBeNull();

    $intro->update([
        'rich_content' => [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => 'Teks lama tanpa konteks kesatuan.']],
                ],
            ],
        ],
    ]);

    expect(json_encode($intro->fresh()->rich_content))
        ->not->toContain('TNI AD')
        ->not->toContain('peta jalan');

    $this->seed(AiForMilitaryCourseSeeder::class);

    $replaced = $course->lessons()->where('lessons.title', AiForMilitaryCourseSeeder::PREVIEW_LESSON_TITLE)->first();
    $json = json_encode($replaced?->rich_content ?? []);

    expect($json)
        ->toContain('TNI AD')
        ->toContain('peta jalan')
        ->toContain('Korem 074');
});
