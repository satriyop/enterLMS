<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Services\TipTapRenderer;
use Database\Seeders\AiEngineerCourseSeeder;
use Database\Seeders\FreeFlowDemoSeeder;

it('seeds a three-day Bahasa Indonesia Open Course for the AI Engineer occupation', function () {
    $this->seed(FreeFlowDemoSeeder::class);
    $this->seed(AiEngineerCourseSeeder::class);

    $course = Course::query()->where('title', AiEngineerCourseSeeder::COURSE_TITLE)->first();

    expect($course)->not->toBeNull();

    $sections = CourseSection::query()
        ->where('course_id', $course->id)
        ->orderBy('order')
        ->get();

    expect($sections->pluck('title')->all())->toBe(AiEngineerCourseSeeder::SECTION_TITLES)
        ->and($sections)->toHaveCount(3);

    $lessons = Lesson::query()
        ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
        ->where('course_sections.course_id', $course->id)
        ->whereNull('lessons.deleted_at')
        ->whereNull('course_sections.deleted_at')
        ->orderBy('course_sections.order')
        ->orderBy('lessons.order')
        ->get(['lessons.id', 'lessons.title', 'lessons.rich_content', 'lessons.estimated_duration_minutes']);

    expect($lessons->pluck('title')->all())->toBe(AiEngineerCourseSeeder::LESSON_TITLES)
        ->and($lessons)->toHaveCount(23)
        ->and((int) $course->estimated_duration_minutes)->toBeGreaterThanOrEqual(1260)
        ->and($lessons->sum('estimated_duration_minutes'))->toBe(1260);

    $bodies = $lessons->map(function (Lesson $lesson) {
        return $lesson->title.' '.(new TipTapRenderer)->plainText($lesson->rich_content);
    })->implode("\n");

    expect($bodies)
        ->toContain('Kota Arunika')
        ->toContain('jalan di gang melati ambles')
        ->toContain('lembar September')
        ->toContain('8.000')
        ->toContain('3201013101900001')
        ->toContain('95 persen')
        ->toContain('TIK.DSC0704')
        ->toContain('SKEMA-45')
        ->toContain('J.62AIN00.002.1')
        ->toContain('J.62AIN00.004.1')
        ->toContain('J.62DMI00.015.1')
        ->toContain('J.62DMI00.020.1')
        ->toContain('J.62AIN00.015.1')
        ->toContain('J.62AIN00.017.1')
        ->toContain('J.62PDP00.011.1')
        ->toContain('J.62DMI00.021.1')
        ->toContain('macro-F1')
        ->toContain('mode bayangan')
        ->toContain('UU 27/2022')
        ->toContain('RoPA')
        ->toContain('paket siang')
        ->toContain('Rp2.500.000')
        ->toContain('Surveilan')
        ->toContain('untuk')
        ->toContain('yang')
        ->toContain('tidak')
        ->not->toContain('```mermaid')
        ->not->toContain('0,74')
        ->not->toContain('0,87')
        ->not->toContain('Encoder ditolak karena recall');

    $renderer = new TipTapRenderer;
    $intro = $lessons->firstWhere('title', AiEngineerCourseSeeder::PREVIEW_LESSON_TITLE);
    $install = $lessons->firstWhere('title', 'Memasang solusi AI ke layanan operasional');

    expect($intro)->not->toBeNull()
        ->and($install)->not->toBeNull();

    $introHtml = $renderer->render($intro->rich_content);
    $installHtml = $renderer->render($install->rich_content);

    expect($introHtml)
        ->toContain('<h1>')
        ->toContain('<p>')
        ->toContain('<ul>')
        ->toContain('<table>')
        ->toContain('<svg')
        ->toContain('<figure class="lesson-diagram">')
        ->toContain('>Hari 1<')
        ->toContain('J.62AIN00.002.1')
        ->and($installHtml)
        ->toContain('<svg')
        ->toContain('mode bayangan');

    $diagramLessons = $lessons->filter(function (Lesson $lesson) use ($renderer) {
        $html = $renderer->render($lesson->rich_content);

        return str_contains($html, '<svg');
    });

    expect($diagramLessons->pluck('title')->all())
        ->toEqualCanonicalizing(AiEngineerCourseSeeder::DIAGRAM_LESSON_TITLES);

    $quiz = Assessment::query()
        ->where('course_id', $course->id)
        ->where('title', AiEngineerCourseSeeder::QUIZ_TITLE)
        ->with('questions')
        ->first();

    expect($quiz)->not->toBeNull()
        ->and($quiz->is_required)->toBeTrue()
        ->and($quiz->status)->toBe('published')
        ->and($quiz->questions)->toHaveCount(10);

    $questionText = $quiz->questions->pluck('question_text')->implode(' ');

    expect($questionText)
        ->toContain('Kota Arunika')
        ->toContain('Mode bayangan')
        ->toContain('sertifikat BNSP')
        ->toContain('kelas mahal')
        ->toContain('0,74')
        ->toContain('yang')
        ->not->toContain('Encoder Arunika');

    $tugas = Assessment::query()
        ->where('course_id', $course->id)
        ->whereIn('title', [
            AiEngineerCourseSeeder::TUGAS_HARI_1,
            AiEngineerCourseSeeder::TUGAS_HARI_2,
            AiEngineerCourseSeeder::TUGAS_HARI_3,
        ])
        ->with('questions')
        ->get();

    expect($tugas)->toHaveCount(3)
        ->and($tugas->every(fn (Assessment $assessment) => $assessment->is_required && $assessment->status === 'published'))->toBeTrue()
        ->and($tugas->flatMap->questions->every(fn ($question) => $question->question_type === 'essay'))->toBeTrue();

    $hari1 = $tugas->firstWhere('title', AiEngineerCourseSeeder::TUGAS_HARI_1);
    $hari2 = $tugas->firstWhere('title', AiEngineerCourseSeeder::TUGAS_HARI_2);
    $hari3 = $tugas->firstWhere('title', AiEngineerCourseSeeder::TUGAS_HARI_3);

    expect($hari1)->not->toBeNull()
        ->and($hari2)->not->toBeNull()
        ->and($hari3)->not->toBeNull();

    $hari1Text = $hari1->questions->pluck('question_text')->implode(' ');
    $hari2Text = $hari2->questions->pluck('question_text')->implode(' ');
    $hari3Text = $hari3->questions->pluck('question_text')->implode(' ');

    expect($hari1Text)
        ->toContain('J.62AIN00.002.1')
        ->toContain('J.62AIN00.004.1')
        ->and($hari2Text)
        ->toContain('paket siang')
        ->toContain('J.62DMI00.015.1')
        ->toContain('J.62DMI00.020.1')
        ->toContain('0,87')
        ->not->toContain('0,74')
        ->and($hari3Text)
        ->toContain('J.62AIN00.015.1')
        ->toContain('J.62AIN00.017.1')
        ->toContain('J.62PDP00.011.1')
        ->toContain('J.62DMI00.021.1')
        ->toContain('ktp saya hilang')
        ->toContain('bantuan sosial')
        ->toContain('RoPA');
});
