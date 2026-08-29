<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Media;
use App\Models\User;
use App\Services\SeederLessonMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

it('includes tutor_body on document Lesson edit when PDF text is stored', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->draft()->create(['user_id' => $admin->id]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'title' => 'Lembar glosarium agen',
        'content_type' => 'document',
    ]);

    Media::factory()->document()->withBodyText(
        'OpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas.'
    )->create([
        'mediable_type' => $lesson->getMorphClass(),
        'mediable_id' => $lesson->id,
        'file_name' => 'glosarium-agen-ai.pdf',
        'custom_properties' => [
            'body_text' => 'OpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas.',
            'body_capture' => 'ready',
        ],
    ]);

    $this->actingAs($admin)
        ->get(route('lessons.edit', $lesson))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('lessons/Edit')
            ->where('tutor_body.ready', true)
            ->where('tutor_body.text', "Lembar glosarium agen\n\nOpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas.")
            ->where('tutor_body.capture.0.file_name', 'glosarium-agen-ai.pdf')
            ->where('tutor_body.capture.0.status', 'ready')
        );
});

it('does not require tutor_body on Lesson create', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->draft()->create(['user_id' => $admin->id]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);

    $this->actingAs($admin)
        ->get(route('sections.lessons.create', $section))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('lessons/Edit')
            ->where('lesson', null)
            ->missing('tutor_body')
        );
});

it('does not leak tutor_body to the Learner Lesson page', function () {
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'content_type' => 'document',
        'is_free_preview' => false,
    ]);

    ['user' => $user] = createEnrolledLearner($course);

    $this->actingAs($user)
        ->get(route('courses.lessons.show', [$course, $lesson]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('lessons/Show')
            ->missing('tutor_body')
        );
});

it('includes captured tutor_body on edit after a generated PDF upload', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->draft()->create(['user_id' => $admin->id]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'title' => 'Lembar glosarium agen',
        'content_type' => 'document',
    ]);

    $binary = (new SeederLessonMedia)->pdf(
        'Glosarium Pengenalan Agen AI',
        ['OpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas.'],
    );

    $this->actingAs($admin)
        ->postJson('/media', [
            'file' => UploadedFile::fake()->createWithContent('glosarium.pdf', $binary),
            'mediable_type' => 'lesson',
            'mediable_id' => $lesson->id,
            'collection_name' => 'document',
        ])
        ->assertCreated();

    $this->actingAs($admin)
        ->get(route('lessons.edit', $lesson))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('lessons/Edit')
            ->where('tutor_body.ready', true)
            ->where('tutor_body.capture.0.status', 'ready')
        );
});
