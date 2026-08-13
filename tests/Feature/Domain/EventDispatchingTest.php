<?php

use App\Domain\Assessment\Events\AssessmentAttemptStarted;
use App\Domain\Assessment\Events\AssessmentAttemptSubmitted;
use App\Domain\Assessment\Events\AssessmentGraded;
use App\Domain\Enrollment\Events\CourseStarted;
use App\Domain\Enrollment\Events\EnrollmentCompleted;
use App\Domain\Enrollment\Events\UserDropped;
use App\Domain\Enrollment\Events\UserEnrolled;
use App\Domain\Enrollment\Events\UserReenrolled;
use App\Domain\LearningPath\Events\PathDropped;
use App\Domain\LearningPath\Events\PathEnrollmentCreated;
use App\Domain\Progress\Events\LessonCompleted;
use App\Domain\Progress\Events\ProgressUpdated;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Support\Facades\Event;

// =============================================================================
// Enrollment Domain Events
// =============================================================================

describe('Enrollment Events', function () {
    it('dispatches UserEnrolled when learner enrolls in a course', function () {
        Event::fake([UserEnrolled::class]);

        $learner = User::factory()->create(['role' => 'learner']);
        $course = createPublishedCourseWithContent();

        test()->actingAs($learner)->post("/courses/{$course->id}/enroll");

        Event::assertDispatched(UserEnrolled::class, function ($event) use ($learner, $course) {
            return $event->enrollment->user_id === $learner->id
                && $event->enrollment->course_id === $course->id;
        });
    });

    it('dispatches UserDropped when learner drops enrollment', function () {
        Event::fake([UserDropped::class]);

        $learner = User::factory()->create(['role' => 'learner']);
        $course = createPublishedCourseWithContent();
        Enrollment::factory()->active()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        test()->actingAs($learner)->delete("/courses/{$course->id}/unenroll");

        Event::assertDispatched(UserDropped::class, function ($event) use ($learner) {
            return $event->enrollment->user_id === $learner->id;
        });
    });

    it('dispatches EnrollmentCompleted when all lessons are completed', function () {
        Event::fake([EnrollmentCompleted::class, ProgressUpdated::class, LessonCompleted::class, CourseStarted::class]);

        $learner = User::factory()->create(['role' => 'learner']);
        $course = createPublishedCourseWithContent(1, 1);
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        $lesson = $course->lessons()->first();

        // Use the service directly to complete the lesson
        progressService()->completeLesson($enrollment, $lesson);

        Event::assertDispatched(EnrollmentCompleted::class, function ($event) use ($enrollment) {
            return $event->enrollment->id === $enrollment->id;
        });
    });

    it('dispatches CourseStarted on first progress update', function () {
        Event::fake([CourseStarted::class, ProgressUpdated::class]);

        $learner = User::factory()->create(['role' => 'learner']);
        $course = createPublishedCourseWithContent();
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        $lesson = $course->lessons()->first();

        // Use PATCH (the correct HTTP method for the progress route)
        test()->actingAs($learner)->patch(
            "/courses/{$course->id}/lessons/{$lesson->id}/progress",
            ['current_page' => 1]
        );

        Event::assertDispatched(CourseStarted::class, function ($event) use ($learner) {
            return $event->enrollment->user_id === $learner->id;
        });
    });

    it('dispatches UserReenrolled when learner re-enrolls after dropping', function () {
        Event::fake([UserReenrolled::class]);

        $learner = User::factory()->create(['role' => 'learner']);
        $course = createPublishedCourseWithContent();
        Enrollment::factory()->dropped()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        test()->actingAs($learner)->post("/courses/{$course->id}/reenroll");

        Event::assertDispatched(UserReenrolled::class, function ($event) use ($learner) {
            return $event->enrollment->user_id === $learner->id;
        });
    });
});

// =============================================================================
// Progress Domain Events
// =============================================================================

describe('Progress Events', function () {
    it('dispatches ProgressUpdated when lesson progress is updated', function () {
        Event::fake([ProgressUpdated::class, CourseStarted::class]);

        $learner = User::factory()->create(['role' => 'learner']);
        $course = createPublishedCourseWithContent();
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        $lesson = $course->lessons()->first();

        test()->actingAs($learner)->patch(
            "/courses/{$course->id}/lessons/{$lesson->id}/progress",
            ['current_page' => 2]
        );

        Event::assertDispatched(ProgressUpdated::class);
    });

    it('dispatches LessonCompleted when a lesson is completed via service', function () {
        Event::fake([LessonCompleted::class, ProgressUpdated::class, CourseStarted::class, EnrollmentCompleted::class]);

        $learner = User::factory()->create(['role' => 'learner']);
        $course = createPublishedCourseWithContent();
        $enrollment = Enrollment::factory()->active()->create([
            'user_id' => $learner->id,
            'course_id' => $course->id,
        ]);

        $lesson = $course->lessons()->first();

        progressService()->completeLesson($enrollment, $lesson);

        Event::assertDispatched(LessonCompleted::class, function ($event) use ($lesson) {
            return $event->lesson->id === $lesson->id;
        });
    });
});

// =============================================================================
// Assessment Domain Events
// =============================================================================

describe('Assessment Events', function () {
    beforeEach(function () {
        $this->contentManager = User::factory()->create(['role' => 'lms_admin']);
        $this->learner = User::factory()->create(['role' => 'learner']);

        $this->course = Course::factory()->published()->public()->create([
            'user_id' => $this->contentManager->id,
        ]);

        $section = CourseSection::factory()->create(['course_id' => $this->course->id]);
        Lesson::factory()->create(['course_section_id' => $section->id]);

        $this->assessment = Assessment::factory()->published()->create([
            'course_id' => $this->course->id,
            'max_attempts' => 3,
        ]);

        $this->question = Question::factory()->create([
            'assessment_id' => $this->assessment->id,
            'question_type' => 'multiple_choice',
            'points' => 10,
        ]);

        $this->correctOption = QuestionOption::factory()->correct()->create(['question_id' => $this->question->id]);
        QuestionOption::factory()->create(['question_id' => $this->question->id]);

        Enrollment::factory()->active()->create([
            'user_id' => $this->learner->id,
            'course_id' => $this->course->id,
        ]);
    });

    it('dispatches AssessmentAttemptStarted when learner starts an attempt', function () {
        Event::fake([AssessmentAttemptStarted::class]);

        test()->actingAs($this->learner)->post(
            "/courses/{$this->course->id}/assessments/{$this->assessment->id}/start"
        );

        Event::assertDispatched(AssessmentAttemptStarted::class);
    });

    it('dispatches AssessmentAttemptSubmitted when learner submits answers', function () {
        Event::fake([AssessmentAttemptSubmitted::class]);

        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->learner->id,
            'status' => 'in_progress',
        ]);

        test()->actingAs($this->learner)->post(
            "/courses/{$this->course->id}/assessments/{$this->assessment->id}/attempts/{$attempt->id}/submit",
            [
                'answers' => [
                    [
                        'question_id' => $this->question->id,
                        'answer_text' => $this->correctOption->option_text,
                    ],
                ],
            ]
        );

        Event::assertDispatched(AssessmentAttemptSubmitted::class);
    });

    it('dispatches AssessmentGraded when instructor grades answers', function () {
        Event::fake([AssessmentGraded::class]);

        $admin = User::factory()->create(['role' => 'lms_admin']);

        $attempt = AssessmentAttempt::factory()->create([
            'assessment_id' => $this->assessment->id,
            'user_id' => $this->learner->id,
            'status' => 'submitted',
        ]);

        $answer = AttemptAnswer::create([
            'assessment_attempt_id' => $attempt->id,
            'question_id' => $this->question->id,
            'answer_text' => 'Test answer',
        ]);

        $response = test()->actingAs($admin)->post(
            "/courses/{$this->course->id}/assessments/{$this->assessment->id}/attempts/{$attempt->id}/grade",
            [
                'grades' => [
                    [
                        'answer_id' => $answer->id,
                        'score' => 8,
                        'feedback' => 'Bagus',
                    ],
                ],
            ]
        );

        $response->assertRedirect();
        Event::assertDispatched(AssessmentGraded::class);
    });
});

// =============================================================================
// Learning Path Domain Events
// =============================================================================

describe('Learning Path Events', function () {
    it('dispatches PathEnrollmentCreated when learner enrolls in a path', function () {
        Event::fake([PathEnrollmentCreated::class]);

        $learner = User::factory()->create(['role' => 'learner']);
        $path = LearningPath::factory()->published()->create();
        $course = Course::factory()->published()->public()->create();
        $path->courses()->attach($course->id, ['position' => 1, 'is_required' => true]);

        test()->actingAs($learner)->post(
            route('learner.learning-paths.enroll', $path)
        );

        Event::assertDispatched(PathEnrollmentCreated::class, function ($event) use ($learner) {
            return $event->enrollment->user_id === $learner->id;
        });
    });

    it('dispatches PathDropped when learner drops path enrollment', function () {
        Event::fake([PathDropped::class]);

        $learner = User::factory()->create(['role' => 'learner']);
        $path = LearningPath::factory()->published()->create();
        $course = Course::factory()->published()->public()->create();
        $path->courses()->attach($course->id, ['position' => 1, 'is_required' => true]);

        LearningPathEnrollment::factory()->active()->create([
            'user_id' => $learner->id,
            'learning_path_id' => $path->id,
        ]);

        test()->actingAs($learner)->delete(
            route('learner.learning-paths.drop', $path)
        );

        Event::assertDispatched(PathDropped::class, function ($event) use ($learner) {
            return $event->enrollment->user_id === $learner->id;
        });
    });
});
