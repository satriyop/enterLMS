<?php

use App\Domain\Enrollment\DTOs\EnrollmentContext;
use App\Domain\Enrollment\Exceptions\EnrollmentCapacityExceededException;
use App\Domain\Enrollment\Services\EnrollmentService;
use App\Domain\Enrollment\States\ActiveState;
use App\Domain\Progress\DTOs\ProgressResult;
use App\Domain\Progress\Services\ProgressTrackingService;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\ScormPackage;
use App\Models\User;
use App\Models\XapiStatement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ---------------------------------------------------------------------------
// 1. Spatie state helpers (EnrollmentContext + progress courseCompleted)
// ---------------------------------------------------------------------------

it('builds EnrollmentContext with isActivelyEnrolled true for active Spatie state', function () {
    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    Enrollment::factory()->active()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);

    $context = EnrollmentContext::for($user, $course);

    expect($context->isActivelyEnrolled)->toBeTrue()
        ->and($context->hasAnyEnrollment)->toBeTrue();
});

it('builds EnrollmentContext with isActivelyEnrolled false for dropped enrollment', function () {
    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    Enrollment::factory()->dropped()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);

    $context = EnrollmentContext::for($user, $course);

    expect($context->isActivelyEnrolled)->toBeFalse()
        ->and($context->hasAnyEnrollment)->toBeTrue();
});

it('reports courseCompleted true when enrollment is completed via model helper path', function () {
    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    $enrollment = Enrollment::factory()->completed()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'progress_percentage' => 100,
    ]);
    $lesson = Lesson::factory()->create();
    // Attach lesson under course section so progress path is valid
    $section = \App\Models\CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson->update(['course_section_id' => $section->id]);

    $progress = app(ProgressTrackingService::class)->getOrCreateProgress($enrollment, $lesson);
    $progress->update(['is_completed' => true, 'completed_at' => now()]);

    $result = app(ProgressTrackingService::class)->completeLesson($enrollment->fresh(), $lesson);

    expect($result)->toBeInstanceOf(ProgressResult::class)
        ->and($result->courseCompleted)->toBeTrue();
});

// ---------------------------------------------------------------------------
// 2. SCORM path jail
// ---------------------------------------------------------------------------

it('rejects SCORM content paths that escape the package root', function () {
    Storage::fake('local');

    $admin = User::factory()->lmsAdmin()->create();
    $package = ScormPackage::factory()->create([
        'disk' => 'local',
        'extraction_path' => 'scorm/packages/safe-pkg',
        'uploaded_by' => $admin->id,
    ]);

    Storage::disk('local')->put('scorm/packages/safe-pkg/index.html', '<html>ok</html>');
    Storage::disk('local')->put('secret.txt', 'should-not-leak');

    $this->actingAs($admin)
        ->get(route('scorm.player.content', [
            'scormPackage' => $package->id,
            'path' => '../secret.txt',
        ]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('scorm.player.content', [
            'scormPackage' => $package->id,
            'path' => 'index.html',
        ]))
        ->assertOk();
});

// ---------------------------------------------------------------------------
// 3. xAPI ownership + actor binding
// ---------------------------------------------------------------------------

it('binds xAPI actor to authenticated user and ignores spoofed actor_id', function () {
    $user = User::factory()->learner()->create([
        'name' => 'Learner Asli',
        'email' => 'asli@enterlms.test',
    ]);
    $victim = User::factory()->learner()->create([
        'name' => 'Korban',
        'email' => 'korban@enterlms.test',
    ]);

    $this->actingAs($user)
        ->postJson(route('api.xapi.statements.store'), [
            'verb_id' => 'http://adlnet.gov/expapi/verbs/experienced',
            'verb_display' => 'experienced',
            'object_id' => 'http://enterlms.test/activities/lesson/9',
            'actor_id' => $victim->id,
            'actor_name' => 'Spoofed Name',
            'actor_mbox' => 'mailto:spoof@evil.test',
        ])
        ->assertCreated()
        ->assertJsonPath('data.actor.name', 'Learner Asli');

    $statement = XapiStatement::latest('id')->first();
    expect($statement->actor_id)->toBe($user->id)
        ->and($statement->actor_name)->toBe('Learner Asli')
        ->and($statement->actor_mbox)->toBe('mailto:asli@enterlms.test');
});

it('rejects xAPI context_enrollment_id owned by another user', function () {
    $alice = User::factory()->learner()->create();
    $bob = User::factory()->learner()->create();
    $course = Course::factory()->published()->create(['visibility' => 'public']);
    $bobEnrollment = Enrollment::factory()->active()->create([
        'user_id' => $bob->id,
        'course_id' => $course->id,
    ]);

    $this->actingAs($alice)
        ->postJson(route('api.xapi.statements.store'), [
            'verb_id' => 'http://adlnet.gov/expapi/verbs/experienced',
            'verb_display' => 'experienced',
            'object_id' => 'http://enterlms.test/activities/lesson/1',
            'context_enrollment_id' => $bobEnrollment->id,
            'context_course_id' => $course->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['context_enrollment_id']);

    expect(XapiStatement::query()->where('actor_id', $alice->id)->count())->toBe(0);
});

it('accepts xAPI context when enrollment belongs to the actor', function () {
    $alice = User::factory()->learner()->create();
    $course = Course::factory()->published()->create(['visibility' => 'public']);
    $enrollment = Enrollment::factory()->active()->create([
        'user_id' => $alice->id,
        'course_id' => $course->id,
    ]);

    $this->actingAs($alice)
        ->postJson(route('api.xapi.statements.store'), [
            'verb_id' => 'http://adlnet.gov/expapi/verbs/experienced',
            'verb_display' => 'experienced',
            'object_id' => 'http://enterlms.test/activities/lesson/1',
            'context_enrollment_id' => $enrollment->id,
            'context_course_id' => $course->id,
        ])
        ->assertCreated();

    $statement = XapiStatement::latest('id')->first();
    expect($statement->actor_id)->toBe($alice->id)
        ->and($statement->context_enrollment_id)->toBe($enrollment->id)
        ->and($statement->context_course_id)->toBe($course->id);
});

it('scopes xAPI index to the authenticated learner', function () {
    $alice = User::factory()->learner()->create();
    $bob = User::factory()->learner()->create();

    XapiStatement::factory()->create([
        'actor_id' => $alice->id,
        'verb_id' => 'http://adlnet.gov/expapi/verbs/experienced',
        'object_id' => 'http://enterlms.test/a',
    ]);
    XapiStatement::factory()->create([
        'actor_id' => $bob->id,
        'verb_id' => 'http://adlnet.gov/expapi/verbs/experienced',
        'object_id' => 'http://enterlms.test/b',
    ]);

    $response = $this->actingAs($alice)
        ->getJson(route('api.xapi.statements.index'));

    $response->assertOk();
    $statements = collect($response->json('statements'));
    expect($statements)->toHaveCount(1)
        ->and($statements->first()['object']['id'])->toBe('http://enterlms.test/a')
        ->and($statements->pluck('object.id'))->not->toContain('http://enterlms.test/b');
});

// ---------------------------------------------------------------------------
// 4. Capacity lock + soft-delete re-enroll
// ---------------------------------------------------------------------------

it('enforces max_enrollments under sequential capacity pressure', function () {
    $course = Course::factory()->published()->create([
        'visibility' => 'public',
        'max_enrollments' => 2,
        'is_paid' => false,
    ]);

    $service = app(EnrollmentService::class);

    $u1 = User::factory()->learner()->create();
    $u2 = User::factory()->learner()->create();
    $u3 = User::factory()->learner()->create();

    $service->enroll($u1->id, $course->id);
    $service->enroll($u2->id, $course->id);

    expect(Enrollment::query()->where('course_id', $course->id)->where('status', ActiveState::$name)->count())
        ->toBe(2);

    expect(fn () => $service->enroll($u3->id, $course->id))
        ->toThrow(EnrollmentCapacityExceededException::class);
});

it('reactivates dropped enrollment instead of soft-delete lifecycle', function () {
    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->create([
        'visibility' => 'public',
        'is_paid' => false,
    ]);

    $enrollment = Enrollment::factory()->active()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);
    $enrollment->drop('test');

    $restored = app(EnrollmentService::class)->enroll($user->id, $course->id);

    expect($restored->id)->toBe($enrollment->id)
        ->and($restored->isActive())->toBeTrue();
});

it('rejects re-enroll when course is already at capacity including active seats', function () {
    $course = Course::factory()->published()->create([
        'visibility' => 'public',
        'is_paid' => false,
        'max_enrollments' => 1,
    ]);

    $holder = User::factory()->learner()->create();
    app(EnrollmentService::class)->enroll($holder->id, $course->id);

    $other = User::factory()->learner()->create();
    $dropped = Enrollment::factory()->dropped()->create([
        'user_id' => $other->id,
        'course_id' => $course->id,
    ]);

    expect(fn () => app(EnrollmentService::class)->enroll($other->id, $course->id))
        ->toThrow(EnrollmentCapacityExceededException::class);

    expect($dropped->fresh()->isDropped())->toBeTrue();
});

// ---------------------------------------------------------------------------
// 5. Home stats schema + single route registration
// ---------------------------------------------------------------------------

it('computes home stats from public visibility and real duration columns', function () {
    Cache::forget('home_stats');

    Course::factory()->published()->create([
        'visibility' => 'public',
        'estimated_duration_minutes' => 120,
        'manual_duration_minutes' => null,
    ]);
    Course::factory()->published()->create([
        'visibility' => 'public',
        'estimated_duration_minutes' => 60,
        'manual_duration_minutes' => 180,
    ]);
    // Hidden / wrong visibility must not count as "visible" (legacy bug)
    Course::factory()->published()->create([
        'visibility' => 'hidden',
        'estimated_duration_minutes' => 999,
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->where('stats.0.value', '2') // courses available
        ->where('stats.3.value', '5') // (120+180)/60 = 5 hours
    );
});

it('registers course and learning path routes only once', function () {
    $courseRoutes = collect(Route::getRoutes())->filter(
        fn ($route) => $route->getName() === 'courses.show'
    );
    $pathRoutes = collect(Route::getRoutes())->filter(
        fn ($route) => $route->getName() === 'learning-paths.show'
            || $route->getName() === 'learning_paths.show'
    );

    // Discover actual path show name
    $pathShowNames = collect(Route::getRoutes())
        ->map(fn ($r) => $r->getName())
        ->filter(fn ($n) => is_string($n) && str_contains($n, 'learning') && str_contains($n, 'show'))
        ->countBy();

    expect($courseRoutes)->toHaveCount(1);

    foreach ($pathShowNames as $name => $count) {
        expect($count)->toBe(1);
    }
});

// ---------------------------------------------------------------------------
// 6. Progress FormRequest authorize (policy, not always true)
// ---------------------------------------------------------------------------

it('treats paid courses as free while payments are disabled', function () {
    config()->set('lms.mode', 'commercial');
    config()->set('lms.payment.enabled', false);

    $course = Course::factory()->published()->create([
        'is_paid' => true,
        'price' => 250000,
        'visibility' => 'public',
    ]);

    expect($course->isPaid())->toBeFalse();

    $user = User::factory()->learner()->create();
    $enrollment = app(EnrollmentService::class)->enroll($user->id, $course->id);

    expect($enrollment->isActive())->toBeTrue();
});

it('uses assessment_inclusive as default progress calculator config', function () {
    expect(config('lms.progress_calculator'))->toBe('assessment_inclusive');
});

it('denies media progress update when learner is not enrolled', function () {
    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    $section = \App\Models\CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

    $this->actingAs($learner)
        ->patchJson(route('courses.lessons.progress.media', [$course, $lesson]), [
            'position_seconds' => 10,
            'duration_seconds' => 100,
        ])
        ->assertForbidden();
});
