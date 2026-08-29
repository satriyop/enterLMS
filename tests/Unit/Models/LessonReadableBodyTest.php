<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Media;
use App\Services\SeederLessonMedia;
use Illuminate\Support\Facades\Storage;

it('uses stored PDF text for a document Lesson and ignores the TipTap teaser', function () {
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'title' => 'Lembar glosarium agen',
        'description' => 'Satu halaman istilah yang dipakai academy ini.',
        'content_type' => 'document',
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'Unduh atau baca PDF ini. Learner, Tutor, Enrollment.']],
            ]],
        ],
    ]);

    Media::factory()->document()->withBodyText(
        'OpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas.'
    )->create([
        'mediable_type' => $lesson->getMorphClass(),
        'mediable_id' => $lesson->id,
    ]);

    $lesson = $lesson->fresh(['media']);

    expect($lesson->isBodyReady())->toBeTrue()
        ->and($lesson->readableBody())->toBe(
            "Lembar glosarium agen\n\nOpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas."
        )
        ->and($lesson->readableBody())->not->toContain('Satu halaman istilah')
        ->and($lesson->readableBody())->not->toContain('Learner, Tutor, Enrollment');
});

it('is not body-ready for a document Lesson without stored text even if a generated PDF sits on disk', function () {
    Storage::fake('public');

    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'title' => 'Lembar glosarium agen',
        'content_type' => 'document',
    ]);

    $media = (new SeederLessonMedia)->attachPdf(
        $lesson,
        'glosarium.pdf',
        'Glosarium Pengenalan Agen AI',
        ['OpenClaw: runtime agen.'],
    );
    $media->update(['custom_properties' => null]);

    $lesson = $lesson->fresh(['media']);

    expect($lesson->isBodyReady())->toBeFalse()
        ->and($lesson->readableBody())->toBe('Lembar glosarium agen')
        ->and($lesson->readableBody())->not->toContain('OpenClaw');
});

it('treats video and audio Lessons as ready without stored media text', function () {
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);

    $video = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'content_type' => 'video',
        'title' => 'Video agen',
        'description' => 'Catatan video.',
    ]);
    $audio = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'content_type' => 'audio',
        'title' => 'Audio agen',
        'description' => 'Catatan audio.',
    ]);

    expect($video->isBodyReady())->toBeTrue()
        ->and($audio->isBodyReady())->toBeTrue();
});
