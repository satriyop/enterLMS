<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Services\TipTapRenderer;
use Database\Seeders\AiUseCaseCourseSeeder;
use Database\Seeders\FreeFlowDemoSeeder;

it('seeds an extensive Bahasa Indonesia Open Course covering the full model-building pipeline', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiUseCaseCourseSeeder::class);

    $course = Course::query()->where('title', AiUseCaseCourseSeeder::COURSE_TITLE)->first();

    expect($course)->not->toBeNull();

    $sections = CourseSection::query()
        ->where('course_id', $course->id)
        ->orderBy('order')
        ->get();

    expect($sections->pluck('title')->all())->toBe(AiUseCaseCourseSeeder::SECTION_TITLES)
        ->and($sections)->toHaveCount(6);

    $lessons = Lesson::query()
        ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
        ->where('course_sections.course_id', $course->id)
        ->whereNull('lessons.deleted_at')
        ->whereNull('course_sections.deleted_at')
        ->orderBy('course_sections.order')
        ->orderBy('lessons.order')
        ->get(['lessons.id', 'lessons.title', 'lessons.rich_content', 'lessons.estimated_duration_minutes']);

    expect($lessons->pluck('title')->all())->toBe(AiUseCaseCourseSeeder::LESSON_TITLES)
        ->and($lessons)->toHaveCount(30)
        ->and($lessons->count())->toBeGreaterThan(10)
        ->and((int) $course->estimated_duration_minutes)->toBeGreaterThan(90)
        ->and((int) $course->estimated_duration_minutes)->toBeGreaterThanOrEqual(360)
        ->and($lessons->sum('estimated_duration_minutes'))->toBeGreaterThanOrEqual(360);

    $bodies = $lessons->map(function (Lesson $lesson) {
        return $lesson->title.' '.(new TipTapRenderer)->plainText($lesson->rich_content);
    })->implode("\n");

    expect($bodies)
        ->toContain('klasifikasi tiket layanan')
        ->toContain('Biro Layanan')
        ->toContain('gak bisa login SSO')
        ->toContain('metrik')
        ->toContain('kebocoran')
        ->toContain('macro-F1')
        ->toContain('split temporal')
        ->toContain('serving')
        ->toContain('untuk')
        ->toContain('yang')
        ->toContain('tidak')
        ->not->toContain('```mermaid');

    $renderer = new TipTapRenderer;
    $intro = $lessons->firstWhere('title', 'Untuk siapa kursus ini');
    $deepdive = $lessons->firstWhere('title', 'Loop pelatihan: loss, batch, overfitting');

    expect($intro)->not->toBeNull()
        ->and($deepdive)->not->toBeNull();

    $introHtml = $renderer->render($intro->rich_content);
    $deepdiveHtml = $renderer->render($deepdive->rich_content);

    expect($introHtml)
        ->toContain('<h1>')
        ->toContain('<p>')
        ->toContain('<ul>')
        ->toContain('<svg')
        ->toContain('<figure class="lesson-diagram">')
        ->toContain('</svg><figcaption>')
        ->toContain('>Kasus<')
        ->toContain('Biro Layanan')
        ->not->toContain('Kasus + metrik')
        ->and($deepdiveHtml)
        ->toContain('<svg')
        ->toContain('<figure class="lesson-diagram">')
        ->toContain('</svg><figcaption>');

    $serving = $lessons->firstWhere('title', 'Kontrak serving: dari teks ke label');
    $servingHtml = $renderer->render($serving->rich_content);

    expect($servingHtml)
        ->toContain('<h1>')
        ->toContain('<p>')
        ->toContain('</svg><figcaption>')
        ->toContain('>Antrian<')
        ->toContain('>Fallback<')
        ->not->toContain('ragu/timeout');

    $taskLesson = $lessons->firstWhere('title', 'Klasifikasi, ekstraksi, atau generasi');
    $taskHtml = $renderer->render($taskLesson->rich_content);

    expect($taskHtml)
        ->toContain('<div class="lesson-table-wrap"><table>')
        ->toContain('<th>')
        ->toContain('Klasifikasi (kasus ini)')
        ->not->toContain('ini)Satu');

    $diagramLessons = $lessons->filter(function (Lesson $lesson) use ($renderer) {
        $html = $renderer->render($lesson->rich_content);

        return str_contains($html, '<svg') || str_contains($html, '<img');
    });

    expect($diagramLessons->pluck('title')->all())
        ->toEqualCanonicalizing(AiUseCaseCourseSeeder::DIAGRAM_LESSON_TITLES)
        ->and($diagramLessons->count())->toBeGreaterThanOrEqual(8);

    $quiz = Assessment::query()
        ->where('course_id', $course->id)
        ->where('title', AiUseCaseCourseSeeder::QUIZ_TITLE)
        ->with('questions')
        ->first();

    expect($quiz)->not->toBeNull()
        ->and($quiz->is_required)->toBeTrue()
        ->and($quiz->status)->toBe('published')
        ->and($quiz->questions->count())->toBeGreaterThanOrEqual(8);

    $questionText = $quiz->questions->pluck('question_text')->implode(' ');

    expect($questionText)
        ->toContain('tiket layanan')
        ->toContain('metrik')
        ->toContain('Split')
        ->toContain('kebocoran')
        ->toContain('yang');
});
