<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\ScormPackage;
use App\Models\ScormScoTracking;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    // Build: course → section → scorm lesson → enrolled user
    $this->course = Course::factory()->published()->create();
    $this->section = CourseSection::factory()->create(['course_id' => $this->course->id]);
    $this->scormPackage = ScormPackage::factory()->scorm12()->create();
    $this->lesson = Lesson::factory()->create([
        'course_section_id' => $this->section->id,
        'content_type' => 'scorm',
        'scorm_package_id' => $this->scormPackage->id,
    ]);

    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->enrollment = Enrollment::factory()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
        'status' => 'active',
    ]);
});

// --- Initialize ---

it('initializes a SCORM session for enrolled learner', function () {
    $response = $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.initialize', $this->lesson));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('version', '1.2')
        ->assertJsonStructure(['tracking_id']);

    $this->assertDatabaseHas('scorm_sco_tracking', [
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson->id,
        'scorm_package_id' => $this->scormPackage->id,
    ]);
});

it('resumes a suspended SCORM session', function () {
    // First initialization
    ScormScoTracking::factory()->create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson->id,
        'scorm_package_id' => $this->scormPackage->id,
        'exit_type' => 'suspend',
        'suspend_data' => 'bookmark=page3',
    ]);

    $response = $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.initialize', $this->lesson));

    $response->assertOk()->assertJsonPath('success', true);

    // Entry should be 'resume' since exit_type was 'suspend'
    $tracking = ScormScoTracking::where('enrollment_id', $this->enrollment->id)
        ->where('lesson_id', $this->lesson->id)
        ->first();

    expect($tracking->entry)->toBe('resume');
});

it('rejects initialize for unenrolled user', function () {
    $otherUser = User::factory()->create(['role' => 'learner']);

    $this->actingAs($otherUser)
        ->postJson(route('scorm.runtime.initialize', $this->lesson))
        ->assertForbidden();
});

it('rejects initialize for non-scorm lesson', function () {
    $textLesson = Lesson::factory()->create([
        'course_section_id' => $this->section->id,
        'content_type' => 'text',
    ]);

    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.initialize', $textLesson))
        ->assertStatus(422);
});

// --- Get/Set Value ---

it('gets default CMI values before any interaction', function () {
    // Initialize session first
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.initialize', $this->lesson));

    $response = $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.getValue', $this->lesson), [
            'element' => 'cmi.core.lesson_status',
        ]);

    $response->assertOk()
        ->assertJsonPath('value', 'not attempted')
        ->assertJsonPath('error_code', '0');
});

it('sets and retrieves CMI values', function () {
    // Initialize
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.initialize', $this->lesson));

    // Set lesson_status
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.setValue', $this->lesson), [
            'element' => 'cmi.core.lesson_status',
            'value' => 'incomplete',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    // Set score
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.setValue', $this->lesson), [
            'element' => 'cmi.core.score.raw',
            'value' => '85',
        ])
        ->assertOk();

    // Commit to persist
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.commit', $this->lesson))
        ->assertOk();

    // Verify stored values
    $response = $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.getValue', $this->lesson), [
            'element' => 'cmi.core.score.raw',
        ]);

    $response->assertOk()->assertJsonPath('value', '85.00');
});

it('stores arbitrary CMI elements in JSON catchall', function () {
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.initialize', $this->lesson));

    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.setValue', $this->lesson), [
            'element' => 'cmi.interactions.0.id',
            'value' => 'question_1',
        ])
        ->assertOk();

    // Commit to persist
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.commit', $this->lesson));

    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.getValue', $this->lesson), [
            'element' => 'cmi.interactions.0.id',
        ])
        ->assertOk()
        ->assertJsonPath('value', 'question_1');
});

it('returns 404 for getValue without initialized session', function () {
    $otherUser = User::factory()->create(['role' => 'learner']);

    $this->actingAs($otherUser)
        ->postJson(route('scorm.runtime.getValue', $this->lesson), [
            'element' => 'cmi.core.lesson_status',
        ])
        ->assertNotFound();
});

// --- Commit ---

it('commits data successfully', function () {
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.initialize', $this->lesson));

    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.commit', $this->lesson))
        ->assertOk()
        ->assertJsonPath('success', true);
});

// --- Finish ---

it('finishes a SCORM session and marks completed', function () {
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.initialize', $this->lesson));

    // Set to completed status
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.setValue', $this->lesson), [
            'element' => 'cmi.core.lesson_status',
            'value' => 'completed',
        ]);

    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.setValue', $this->lesson), [
            'element' => 'cmi.core.session_time',
            'value' => '0000:15:30.00',
        ]);

    // Commit to persist the setValue changes
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.commit', $this->lesson));

    // Finish
    $response = $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.finish', $this->lesson));

    $response->assertOk()->assertJsonPath('success', true);

    // Verify tracking was updated
    $tracking = ScormScoTracking::where('enrollment_id', $this->enrollment->id)
        ->where('lesson_id', $this->lesson->id)
        ->first();

    expect($tracking)
        ->finished_at->not->toBeNull()
        ->lesson_status->toBe('completed');

    // Should have dispatched ScormLessonCompleted because lesson_status = 'completed'
    Event::assertDispatched(\App\Domain\Scorm\Events\ScormLessonCompleted::class);
});

it('does not dispatch completion event for incomplete session', function () {
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.initialize', $this->lesson));

    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.setValue', $this->lesson), [
            'element' => 'cmi.core.lesson_status',
            'value' => 'incomplete',
        ]);

    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.commit', $this->lesson));

    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.finish', $this->lesson));

    Event::assertNotDispatched(\App\Domain\Scorm\Events\ScormLessonCompleted::class);
});

// --- Validation ---

it('requires element parameter for getValue', function () {
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.initialize', $this->lesson));

    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.getValue', $this->lesson), [])
        ->assertUnprocessable();
});

it('requires element and value for setValue', function () {
    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.initialize', $this->lesson));

    $this->actingAs($this->learner)
        ->postJson(route('scorm.runtime.setValue', $this->lesson), [])
        ->assertUnprocessable();
});
