<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Services\SeederLessonMedia;
use Illuminate\Support\Facades\Storage;

it('captures body_text for a generated PDF that is missing stored JSON', function () {
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
        ['OpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas.'],
    );
    $media->update(['custom_properties' => null]);

    $this->artisan('media:capture-body-text', ['--media' => $media->id])
        ->assertSuccessful();

    expect($media->fresh()->storedBodyText())->toContain('OpenClaw: runtime agen')
        ->and($media->fresh()->custom_properties['body_capture'])->toBe('ready');
});

it('does not overwrite non-empty body_text without --force', function () {
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

    $this->artisan('media:capture-body-text', ['--media' => $media->id])
        ->assertSuccessful();

    expect($media->fresh()->storedBodyText())->toBe('SOURCE_PARAGRAPH_UNIQUE');
});
