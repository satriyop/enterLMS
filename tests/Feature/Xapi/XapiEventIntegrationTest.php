<?php

use App\Domain\Assessment\Events\AssessmentGraded;
use App\Domain\Enrollment\Events\CourseStarted;
use App\Domain\Enrollment\Events\EnrollmentCompleted;
use App\Domain\Progress\Events\LessonCompleted;
use App\Domain\Scorm\Events\ScormLessonCompleted;
use App\Domain\Xapi\Listeners\RecordXapiOnAssessmentGraded;
use App\Domain\Xapi\Listeners\RecordXapiOnCourseStarted;
use App\Domain\Xapi\Listeners\RecordXapiOnEnrollmentCompleted;
use App\Domain\Xapi\Listeners\RecordXapiOnLessonCompleted;
use App\Domain\Xapi\Listeners\RecordXapiOnScormCompleted;
use App\Domain\Xapi\Services\XapiStatementService;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\ScormPackage;
use App\Models\ScormScoTracking;
use App\Models\User;
use App\Models\XapiStatement;

// --- LessonCompleted → xAPI ---

it('records xAPI statement when lesson is completed', function () {
    $course = Course::factory()->published()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'title' => 'Pengenalan Anti Pencucian Uang',
    ]);
    $user = User::factory()->create(['role' => 'learner']);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    $event = new LessonCompleted($enrollment, $lesson, $user->id);
    $listener = app(RecordXapiOnLessonCompleted::class);
    $listener->handle($event);

    $statement = XapiStatement::latest('id')->first();

    expect($statement)
        ->verb_id->toBe(XapiStatementService::VERB_COMPLETED)
        ->verb_display->toBe('completed')
        ->object_name->toBe('Pengenalan Anti Pencucian Uang')
        ->actor_id->toBe($user->id)
        ->result_completion->toBeTrue()
        ->context_course_id->toBe($course->id)
        ->context_enrollment_id->toBe($enrollment->id);

    // Activity IRI should contain lesson ID
    expect($statement->object_id)->toContain("/activities/lesson/{$lesson->id}");
});

// --- EnrollmentCompleted → xAPI ---

it('records xAPI statement when enrollment is completed', function () {
    $course = Course::factory()->published()->create(['title' => 'Keamanan Siber Dasar']);
    $user = User::factory()->create(['role' => 'learner']);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
        'progress_percentage' => 100,
    ]);

    $event = new EnrollmentCompleted($enrollment, $user->id);
    $listener = app(RecordXapiOnEnrollmentCompleted::class);
    $listener->handle($event);

    $statement = XapiStatement::latest('id')->first();

    expect($statement)
        ->verb_id->toBe(XapiStatementService::VERB_COMPLETED)
        ->actor_id->toBe($user->id)
        ->result_completion->toBeTrue()
        ->context_course_id->toBe($course->id);

    expect($statement->object_id)->toContain("/activities/course/{$course->id}");
});

// --- CourseStarted → xAPI ---

it('records xAPI statement when course is started', function () {
    $course = Course::factory()->published()->create(['title' => 'Manajemen Risiko']);
    $user = User::factory()->create(['role' => 'learner']);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    $event = new CourseStarted($enrollment, $user->id);
    $listener = app(RecordXapiOnCourseStarted::class);
    $listener->handle($event);

    $statement = XapiStatement::latest('id')->first();

    expect($statement)
        ->verb_id->toBe(XapiStatementService::VERB_LAUNCHED)
        ->verb_display->toBe('launched')
        ->actor_id->toBe($user->id)
        ->context_course_id->toBe($course->id)
        ->context_enrollment_id->toBe($enrollment->id);
});

// --- AssessmentGraded → xAPI ---

it('records passed xAPI statement when assessment score meets passing score', function () {
    $course = Course::factory()->published()->create();
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'title' => 'Quiz AML',
        'passing_score' => 70,
    ]);
    $user = User::factory()->create(['role' => 'learner']);
    $attempt = AssessmentAttempt::factory()->graded()->create([
        'assessment_id' => $assessment->id,
        'user_id' => $user->id,
        'score' => 85,
        'max_score' => 100,
        'passed' => true,
    ]);

    // Reload attempt with assessment eager-loaded with sum
    $attempt = AssessmentAttempt::query()
        ->with(['assessment' => fn ($q) => $q->withSum('questions', 'points')])
        ->find($attempt->id);

    $event = new AssessmentGraded($attempt, $user->id);
    $listener = app(RecordXapiOnAssessmentGraded::class);
    $listener->handle($event);

    $statement = XapiStatement::latest('id')->first();

    expect($statement)
        ->verb_id->toBe(XapiStatementService::VERB_PASSED)
        ->verb_display->toBe('passed')
        ->result_score_raw->toBe('85.00')
        ->result_success->toBeTrue()
        ->context_course_id->toBe($course->id);

    expect($statement->object_id)->toContain("/activities/assessment/{$assessment->id}");
});

it('records failed xAPI statement when assessment score is below passing', function () {
    $course = Course::factory()->published()->create();
    $assessment = Assessment::factory()->create([
        'course_id' => $course->id,
        'title' => 'Quiz Keamanan Data',
        'passing_score' => 70,
    ]);
    $user = User::factory()->create(['role' => 'learner']);
    $attempt = AssessmentAttempt::factory()->graded()->create([
        'assessment_id' => $assessment->id,
        'user_id' => $user->id,
        'score' => 50,
        'max_score' => 100,
        'passed' => false,
    ]);

    // Reload attempt with assessment eager-loaded with sum
    $attempt = AssessmentAttempt::query()
        ->with(['assessment' => fn ($q) => $q->withSum('questions', 'points')])
        ->find($attempt->id);

    $event = new AssessmentGraded($attempt, $user->id);
    $listener = app(RecordXapiOnAssessmentGraded::class);
    $listener->handle($event);

    $statement = XapiStatement::latest('id')->first();

    expect($statement)
        ->verb_id->toBe(XapiStatementService::VERB_FAILED)
        ->verb_display->toBe('failed')
        ->result_success->toBeFalse();
});

// --- ScormLessonCompleted → xAPI ---

it('records xAPI statement when SCORM lesson is completed', function () {
    $course = Course::factory()->published()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $scormPackage = ScormPackage::factory()->scorm12()->create();
    $lesson = Lesson::factory()->create([
        'course_section_id' => $section->id,
        'content_type' => 'scorm',
        'scorm_package_id' => $scormPackage->id,
        'title' => 'Modul Kepatuhan OJK',
    ]);
    $user = User::factory()->create(['role' => 'learner']);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);
    $tracking = ScormScoTracking::factory()->completedScorm12()->create([
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson->id,
        'scorm_package_id' => $scormPackage->id,
        'score_raw' => 90,
        'score_min' => 0,
        'score_max' => 100,
    ]);

    $event = new ScormLessonCompleted($enrollment, $lesson, $tracking, $user->id);
    $listener = app(RecordXapiOnScormCompleted::class);
    $listener->handle($event);

    $statement = XapiStatement::latest('id')->first();

    expect($statement)
        ->verb_id->toBe(XapiStatementService::VERB_COMPLETED)
        ->verb_display->toBe('completed')
        ->result_completion->toBeTrue()
        ->source->toBe('scorm')
        ->context_course_id->toBe($course->id)
        ->context_enrollment_id->toBe($enrollment->id);

    expect($statement->object_id)->toContain("/activities/lesson/{$lesson->id}");
});

// --- Integration: multiple events create multiple statements ---

it('generates distinct statements for different events', function () {
    $course = Course::factory()->published()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);
    $user = User::factory()->create(['role' => 'learner']);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
    ]);

    // Fire course started
    $courseStartedListener = app(RecordXapiOnCourseStarted::class);
    $courseStartedListener->handle(new CourseStarted($enrollment, $user->id));

    // Fire lesson completed
    $lessonCompletedListener = app(RecordXapiOnLessonCompleted::class);
    $lessonCompletedListener->handle(new LessonCompleted($enrollment, $lesson, $user->id));

    $statements = XapiStatement::all();

    expect($statements)->toHaveCount(2);

    $verbs = $statements->pluck('verb_id')->toArray();
    expect($verbs)->toContain(XapiStatementService::VERB_LAUNCHED);
    expect($verbs)->toContain(XapiStatementService::VERB_COMPLETED);

    // Each statement should have a unique statement_id
    $ids = $statements->pluck('statement_id')->unique();
    expect($ids)->toHaveCount(2);
});
