<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Servers\EnterLmsAgentServer;
use App\Mcp\Tools\Agent\EnrollCourseTool;
use App\Mcp\Tools\Agent\MarkLessonCompleteTool;
use App\Mcp\Tools\Tutor\GetCourseOutlineTool;
use App\Mcp\Tools\Tutor\GetPublishedLessonTool;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Services\SeederLessonMedia;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function actingWithTutorRead(User $user): void
{
    Sanctum::actingAs($user, AgentAbility::tutorRead());
}

function actingWithFreeFlowAgent(User $user): void
{
    Sanctum::actingAs($user, AgentAbility::freeFlow());
}

function enrollOn(User $user, Course $course, string $status = 'active'): Enrollment
{
    $factory = Enrollment::factory();

    return match ($status) {
        'completed' => $factory->completed()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]),
        'dropped' => $factory->dropped()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]),
        default => $factory->active()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]),
    };
}

function publishedLessonArgs(User $user, Course $course, Lesson $lesson): array
{
    return [
        'user_id' => $user->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
    ];
}

function publishedCourseWithSecretLaterLesson(): array
{
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
    $current = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Apa itu agen',
        'description' => 'Agen berbeda dari chatbot biasa.',
        'order' => 1,
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'Agen menerima tujuan dan memakai alat.']],
            ]],
        ],
    ]);
    $later = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Kill switch',
        'description' => 'RAHASIA_LEMBAGA_KEMUDIAN',
        'order' => 2,
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'RAHASIA_LEMBAGA_KEMUDIAN']],
            ]],
        ],
    ]);

    return compact('course', 'section', 'current', 'later');
}

it('lets tutor.read fetch this published Lesson body as it is now', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current] = publishedCourseWithSecretLaterLesson();
    enrollOn($user, $course);

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $course, $current))
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($course, $current) {
            $json->where('ok', true)
                ->where('data.course_id', $course->id)
                ->where('data.lesson_id', $current->id)
                ->where('data.title', 'Apa itu agen')
                ->where('data.body_ready', true)
                ->where('data.content_type', 'text')
                ->missing('data.url')
                ->missing('data.path')
                ->missing('data.disk')
                ->etc();
        });
});

it('does not return a later Lesson body when reading the current Lesson', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current] = publishedCourseWithSecretLaterLesson();
    enrollOn($user, $course);

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $course, $current))
        ->assertOk()
        ->assertDontSee('RAHASIA_LEMBAGA_KEMUDIAN');
});

it('does not return another Course Lesson', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course] = publishedCourseWithSecretLaterLesson();

    $other = Course::factory()->published()->public()->create();
    $otherSection = CourseSection::factory()->create(['course_id' => $other->id]);
    $otherLesson = Lesson::factory()->text()->create([
        'course_section_id' => $otherSection->id,
        'title' => 'Foreign lesson',
        'description' => 'TUBUH_KURSUS_LAIN',
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'TUBUH_KURSUS_LAIN']],
            ]],
        ],
    ]);

    enrollOn($user, $course);
    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $course, $otherLesson))
        ->assertSee('Pelajaran tidak termasuk dalam Course ini.')
        ->assertDontSee('TUBUH_KURSUS_LAIN');
});

it('returns Course outline titles only', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current, 'later' => $later] = publishedCourseWithSecretLaterLesson();

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetCourseOutlineTool::class, [
        'course_id' => $course->id,
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($course, $current, $later) {
            $json->where('ok', true)
                ->where('data.course_id', $course->id)
                ->where('data.sections.0.lessons.0.id', $current->id)
                ->where('data.sections.0.lessons.0.title', $current->title)
                ->where('data.sections.0.lessons.1.id', $later->id)
                ->where('data.sections.0.lessons.1.title', $later->title)
                ->etc();
        })
        ->assertDontSee('RAHASIA_LEMBAGA_KEMUDIAN')
        ->assertDontSee('Agen menerima tujuan');
});

it('denies get-published-lesson without tutor.read', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current] = publishedCourseWithSecretLaterLesson();

    actingWithFreeFlowAgent($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $course, $current))
        ->assertSee("ability 'tutor.read'");
});

it('denies get-course-outline without tutor.read', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course] = publishedCourseWithSecretLaterLesson();

    actingWithFreeFlowAgent($user);

    EnterLmsAgentServer::tool(GetCourseOutlineTool::class, [
        'course_id' => $course->id,
    ])->assertSee("ability 'tutor.read'");
});

it('forbids enroll and complete with a tutor.read token', function () {
    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->public()->create(['is_paid' => false]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(EnrollCourseTool::class, ['course_id' => $course->id])
        ->assertSee("ability 'agent:enrollment.write'");

    expect(Enrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->exists())
        ->toBeFalse();

    $enrollment = Enrollment::factory()->active()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);

    EnterLmsAgentServer::tool(MarkLessonCompleteTool::class, [
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson->id,
    ])->assertSee("ability 'agent:progress.write'");
});

it('issues tutor.read via artisan without free-flow abilities', function () {
    $user = User::factory()->create([
        'email' => 'tutor-runtime@example.com',
        'role' => 'lms_admin',
    ]);

    $this->artisan('agent:token', [
        'user' => 'tutor-runtime@example.com',
        '--name' => 'tutor',
        '--tutor-read' => true,
    ])->assertSuccessful();

    $token = $user->tokens()->first();
    expect($token->can(AgentAbility::TUTOR_READ))->toBeTrue()
        ->and($token->can(AgentAbility::ENROLLMENT_WRITE))->toBeFalse()
        ->and($token->can(AgentAbility::PROGRESS_WRITE))->toBeFalse()
        ->and($token->can(AgentAbility::CATALOG_READ))->toBeFalse();
});

it('does not bundle tutor.read into --free-flow or --all-abilities', function () {
    $user = User::factory()->create([
        'email' => 'free-flow-owner@example.com',
        'role' => 'learner',
    ]);

    $this->artisan('agent:token', [
        'user' => 'free-flow-owner@example.com',
        '--free-flow' => true,
    ])->assertSuccessful();

    expect($user->tokens()->first()->can(AgentAbility::TUTOR_READ))->toBeFalse()
        ->and($user->tokens()->first()->can(AgentAbility::ENROLLMENT_WRITE))->toBeTrue();

    $other = User::factory()->create([
        'email' => 'all-abilities@example.com',
        'role' => 'lms_admin',
    ]);

    $this->artisan('agent:token', [
        'user' => 'all-abilities@example.com',
        '--all-abilities' => true,
    ])->assertSuccessful();

    expect($other->tokens()->first()->can(AgentAbility::TUTOR_READ))->toBeFalse();
});

it('includes document PDF body so the Tutor can ground on a document Lesson', function () {
    Storage::fake('public');

    $user = User::factory()->learner()->create();
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

    $openClaw = 'OpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas, bukan tombol yang kamu tekan di Lesson.';
    $alat = 'Alat (tool): API, berkas, pencarian, atau konektor yang dipanggil agen di dalam loop.';

    (new SeederLessonMedia)->attachPdf(
        $lesson,
        'glosarium-agen-ai.pdf',
        'Glosarium Pengenalan Agen AI',
        [$openClaw, $alat],
    );

    enrollOn($user, $course);
    actingWithTutorRead($user);

    $expectedBody = $lesson->title."\n\n".$openClaw."\n\n".$alat;

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $course, $lesson))
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($course, $lesson, $expectedBody) {
            $json->where('ok', true)
                ->where('data.course_id', $course->id)
                ->where('data.lesson_id', $lesson->id)
                ->where('data.content_type', 'document')
                ->where('data.body_ready', true)
                ->where('data.body_text', $expectedBody)
                ->where('data.body_html', null)
                ->missing('data.url')
                ->missing('data.path')
                ->missing('data.disk')
                ->etc();
        })
        ->assertDontSee('RAHASIA_LEMBAGA_KEMUDIAN');
});

it('does not scrape a document PDF on read when stored body_text is missing', function () {
    Storage::fake('public');

    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'title' => 'Lembar glosarium agen',
        'description' => 'Satu halaman istilah yang dipakai academy ini.',
        'content_type' => 'document',
    ]);

    $media = (new SeederLessonMedia)->attachPdf(
        $lesson,
        'glosarium-agen-ai.pdf',
        'Glosarium Pengenalan Agen AI',
        ['OpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas.'],
    );
    $media->update(['custom_properties' => null]);

    enrollOn($user, $course);
    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $course, $lesson))
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($lesson) {
            $json->where('ok', true)
                ->where('data.body_ready', false)
                ->where('data.body_text', $lesson->title)
                ->where('data.body_html', null)
                ->etc();
        })
        ->assertDontSee('OpenClaw: runtime agen');
});

it('lets tutor.read read a published restricted Course Lesson when the Learner was granted Enrollment', function () {
    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->restricted()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Kill switch',
        'description' => 'Wewenang Operator.',
    ]);

    enrollOn($user, $course);
    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $course, $lesson))
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($lesson) {
            $json->where('ok', true)
                ->where('data.lesson_id', $lesson->id)
                ->where('data.title', 'Kill switch')
                ->etc();
        });
});

it('requires user_id on get-published-lesson', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current] = publishedCourseWithSecretLaterLesson();
    enrollOn($user, $course);
    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, [
        'course_id' => $course->id,
        'lesson_id' => $current->id,
    ])
        ->assertSee('user_id wajib diisi.')
        ->assertDontSee('Agen menerima tujuan');
});

it('lets the runtime bearer read a Lesson for a named enrolled Learner', function () {
    $runtime = User::factory()->lmsAdmin()->create();
    $learner = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current] = publishedCourseWithSecretLaterLesson();
    enrollOn($learner, $course);

    actingWithTutorRead($runtime);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($learner, $course, $current))
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($current) {
            $json->where('ok', true)
                ->where('data.lesson_id', $current->id)
                ->where('data.body_ready', true)
                ->etc();
        });
});

it('refuses get-published-lesson without Enrollment and does not return the body', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current] = publishedCourseWithSecretLaterLesson();

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $course, $current))
        ->assertSee('Learner tidak terdaftar pada Course ini.')
        ->assertDontSee('Agen menerima tujuan');
});

it('refuses get-published-lesson for a dropped Enrollment and does not return the body', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current] = publishedCourseWithSecretLaterLesson();
    enrollOn($user, $course, 'dropped');

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $course, $current))
        ->assertSee('Enrollment sudah dinonaktifkan.')
        ->assertDontSee('Agen menerima tujuan');
});

it('refuses a Restricted Course Lesson the Learner was not granted', function () {
    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->restricted()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Kill switch',
        'description' => 'Wewenang Operator.',
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'RAHASIA_TERBATAS']],
            ]],
        ],
    ]);

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $course, $lesson))
        ->assertSee('Learner belum diberi akses ke Course terbatas ini.')
        ->assertDontSee('RAHASIA_TERBATAS');
});

it('refuses a Path-locked later Course Lesson and does not return the body', function () {
    $user = User::factory()->learner()->create();
    $first = Course::factory()->published()->public()->create();
    $later = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $later->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Pelajaran terkunci',
        'description' => 'TUBUH_PATH_TERKUNCI',
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'TUBUH_PATH_TERKUNCI']],
            ]],
        ],
    ]);

    $path = \App\Models\LearningPath::factory()->published()->create();
    $path->courses()->attach($first->id, ['position' => 1, 'is_required' => true]);
    $path->courses()->attach($later->id, ['position' => 2, 'is_required' => true]);

    $pathEnrollment = \App\Models\LearningPathEnrollment::factory()->active()->create([
        'user_id' => $user->id,
        'learning_path_id' => $path->id,
    ]);

    \App\Models\LearningPathCourseProgress::factory()->available()->create([
        'learning_path_enrollment_id' => $pathEnrollment->id,
        'course_id' => $first->id,
        'position' => 1,
        'course_enrollment_id' => enrollOn($user, $first)->id,
    ]);

    \App\Models\LearningPathCourseProgress::factory()->locked()->create([
        'learning_path_enrollment_id' => $pathEnrollment->id,
        'course_id' => $later->id,
        'position' => 2,
        'course_enrollment_id' => null,
    ]);

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $later, $lesson))
        ->assertSee('Pelajaran masih terkunci pada Learning Path.')
        ->assertDontSee('TUBUH_PATH_TERKUNCI');
});

it('lets a completed Enrollment still read the Lesson body', function () {
    $user = User::factory()->learner()->create();
    ['course' => $course, 'current' => $current] = publishedCourseWithSecretLaterLesson();
    enrollOn($user, $course, 'completed');

    actingWithTutorRead($user);

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, publishedLessonArgs($user, $course, $current))
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($current) {
            $json->where('ok', true)
                ->where('data.lesson_id', $current->id)
                ->where('data.body_ready', true)
                ->etc();
        });
});
