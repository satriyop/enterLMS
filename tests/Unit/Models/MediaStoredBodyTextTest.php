<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Media;
use App\Services\SeederLessonMedia;
use Illuminate\Support\Facades\Storage;

it('returns stored JSON text and never opens the disk', function () {
    Storage::fake('public');

    $media = Media::factory()->document()->withBodyText('OpenClaw: runtime agen.')->create();

    expect($media->storedBodyText())->toBe('OpenClaw: runtime agen.');
    Storage::disk('public')->assertMissing($media->path);
});

it('captures recoverable text from a generated PDF at write time', function () {
    Storage::fake('public');

    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'content_type' => 'document',
    ]);

    $media = (new SeederLessonMedia)->attachPdf(
        $lesson,
        'glosarium.pdf',
        'Glosarium Pengenalan Agen AI',
        [
            'OpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas.',
            'Alat (tool): API, berkas, pencarian.',
        ],
    );

    $source = $media->storedBodyText();
    $media->update(['custom_properties' => null]);
    $media->captureBody();

    expect($media->fresh()->storedBodyText())
        ->toContain('OpenClaw: runtime agen')
        ->toContain('Alat (tool):')
        ->and($media->fresh()->custom_properties['body_capture'])->toBe('ready')
        ->and($source)->toContain('OpenClaw: runtime agen');
});

it('stamps unsupported on non-PDF without clearing stored text', function () {
    $media = Media::factory()->audio()->withBodyText('Catatan transkrip.')->create([
        'custom_properties' => [
            'body_text' => 'Catatan transkrip.',
            'keep_me' => true,
        ],
    ]);

    $media->captureBody(true);

    $fresh = $media->fresh();

    expect($fresh->storedBodyText())->toBe('Catatan transkrip.')
        ->and($fresh->custom_properties['body_capture'])->toBe('unsupported')
        ->and($fresh->custom_properties['keep_me'])->toBeTrue();
});

it('does not overwrite non-empty seeder text unless forced', function () {
    Storage::fake('public');

    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'content_type' => 'document',
    ]);

    $media = (new SeederLessonMedia)->attachPdf(
        $lesson,
        'glosarium.pdf',
        'Glosarium Pengenalan Agen AI',
        ['SOURCE_PARAGRAPH_UNIQUE'],
    );

    $media->captureBody();

    expect($media->fresh()->storedBodyText())->toBe('SOURCE_PARAGRAPH_UNIQUE')
        ->and($media->fresh()->custom_properties['body_capture'])->toBe('ready');
});
