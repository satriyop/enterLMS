---
name: enteraksi-testing
description: Pest testing conventions and patterns for Enteraksi LMS. Use when writing tests, understanding test organization, or using factory states.
triggers:
  - write test
  - create test
  - pest test
  - factory state
  - test helper
  - policy test
  - authorization test
  - feature test
  - unit test
  - domain test
  - RefreshDatabase
  - test setup
---

# Enteraksi Testing Patterns

## Test Organization

```
tests/
├── Feature/                      # Integration tests (HTTP, database)
│   ├── Api/                      # API endpoint tests
│   ├── Auth/                     # Authentication flow tests
│   ├── Authorization/            # Policy integration tests
│   ├── ContentManagement/        # Content CRUD tests
│   ├── Journey/                  # User journey/E2E tests
│   ├── LearningPath/             # Learning path feature tests
│   ├── Settings/                 # User settings tests
│   └── *Test.php                 # Main feature tests
├── Unit/                         # Unit tests (with database)
│   ├── Domain/                   # Domain layer tests
│   │   ├── Assessment/Strategies/
│   │   ├── Progress/
│   │   └── Shared/
│   ├── Models/                   # Model-specific tests
│   ├── Policies/                 # Policy unit tests
│   └── Services/                 # Service tests
├── Pest.php                      # Global helpers & configuration
└── TestCase.php                  # Custom assertions & setup
```

*Note: RefreshDatabase is used in both Feature AND Unit tests in this project.

## Global Helpers (tests/Pest.php)

```php
// Authentication helpers
asRole('lms_admin')             // Create user with role, authenticate
asAdmin()                        // Shortcut for lms_admin
asContentManager()               // Shortcut for content_manager
asLearner()                      // Shortcut for learner

// Data setup helpers
createPublishedCourseWithContent($sectionCount, $lessonsPerSection)
// Returns Course with sections + lessons

createEnrolledLearner(?Course $course)
// Returns array{user: User, course: Course, enrollment: Enrollment}

// Assertion helpers
assertEventDispatched(string $eventClass, ?callable $callback)

// Service helpers
progressService()   // Returns ProgressTrackingService instance
```

## Custom Assertions (tests/TestCase.php)

```php
// Assert domain event was logged to domain_event_log table
$this->assertEventLogged('UserEnrolled', ['user_id' => $user->id]);

// Assert state transition was recorded
$this->assertStateTransition(
    modelType: Course::class,
    modelId: $course->id,
    fromState: 'draft',
    toState: 'published'
);

// Assert model has specific state
$this->assertModelState($enrollment, 'active');

// Helper: Create enrolled user (returns [User, Course, Enrollment])
[$user, $course, $enrollment] = $this->createEnrolledUser();

// Helper: Create user with role
$admin = $this->createUserWithRole('lms_admin');

// Helper: Create course with sections + lessons
$course = $this->createCourseWithContent(sectionCount: 2, lessonsPerSection: 3);
```

## Key Patterns

### 1. Feature Test (Class-Based)

```php
class EnrollmentLifecycleTest extends TestCase
{
    private User $learner;
    private Course $publicCourse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->learner = User::factory()->create(['role' => 'learner']);
        $this->publicCourse = Course::factory()->published()->create(['visibility' => 'public']);
    }

    public function test_learner_can_self_enroll_in_public_course(): void
    {
        $response = $this->actingAs($this->learner)
            ->post("/courses/{$this->publicCourse->id}/enroll");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->learner->id,
            'course_id' => $this->publicCourse->id,
            'status' => 'active',
        ]);
    }
}
```

### 2. Unit Test (Pest describe/it)

```php
describe('MultipleChoiceGradingStrategy', function () {
    beforeEach(function () {
        $this->strategy = new MultipleChoiceGradingStrategy;
    });

    it('grades correct single choice answer', function () {
        $question = Question::factory()->multipleChoice()->create(['points' => 10]);
        $correctOption = QuestionOption::factory()->correct()->create([
            'question_id' => $question->id,
        ]);

        $result = $this->strategy->grade($question, $correctOption->id);

        expect($result->isCorrect)->toBeTrue();
        expect($result->score)->toBe(10.0);
    });
});
```

### 3. Policy Testing

```php
describe('CoursePolicy', function () {
    describe('update', function () {
        it('allows CM to update own draft course', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);
            $course = Course::factory()->draft()->create(['user_id' => $cm->id]);
            expect($cm->can('update', $course))->toBeTrue();
        });

        it('denies CM from updating published course', function () {
            $cm = User::factory()->create(['role' => 'content_manager']);
            $course = Course::factory()->published()->create(['user_id' => $cm->id]);
            expect($cm->can('update', $course))->toBeFalse();
        });

        it('allows lms_admin to update any course', function () {
            $admin = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->create();
            expect($admin->can('update', $course))->toBeTrue();
        });
    });
});
```

## Factory States Reference

### Course Factory
```php
Course::factory()->draft()->create();
Course::factory()->published()->create();
Course::factory()->archived()->create();
Course::factory()->public()->create();
Course::factory()->restricted()->create();
Course::factory()->beginner()->create();
Course::factory()->intermediate()->create();
Course::factory()->advanced()->create();

// Combine states
Course::factory()->published()->public()->beginner()->create();
```

### Enrollment Factory
```php
Enrollment::factory()->active()->create();
Enrollment::factory()->completed()->create();
Enrollment::factory()->dropped()->create();
```

### Question Factory
```php
Question::factory()->multipleChoice()->create();
Question::factory()->trueFalse()->create();
Question::factory()->essay()->create();
Question::factory()->shortAnswer()->create();
```

### QuestionOption Factory
```php
QuestionOption::factory()->correct()->create(['question_id' => $q->id]);
QuestionOption::factory()->incorrect()->create(['question_id' => $q->id]);
```

### Assessment Factory
```php
Assessment::factory()->published()->create();
Assessment::factory()->withQuestions(5)->create();
Assessment::factory()->timed(30)->create();
```

## Authorization Testing

### 403 vs 302: Know the Difference

- **403 Forbidden** = Policy returned `false` (correct authorization denial)
- **302 Redirect** = Validation failed or middleware redirect (wrong test!)

```php
// CORRECT: Tests authorization denial
->assertForbidden();  // 403

// WRONG: This tests validation, not authorization
->assertRedirect();   // 302
```

### Authorization Test Matrix

Always test role x state x ownership:

```php
describe('Course Update Authorization', function () {
    it('admin can update any draft course', fn() => ...);
    it('admin can update any published course', fn() => ...);
    it('CM can update own draft course', fn() => ...);
    it('CM cannot update own published course', fn() => ...);
    it('CM cannot update other draft course', fn() => ...);
    it('learner cannot update any course', fn() => ...);
});
```

### Cascade Authorization Testing

When child policies delegate to parent, test both:

```php
it('CM cannot edit published course metadata', fn() => ...assertForbidden());
it('CM cannot add section to published course', fn() => ...assertForbidden());
it('CM cannot add lesson to published course', fn() => ...assertForbidden());
```

## Testing Services

Services return Eloquent models directly:

```php
it('enrolls user in course', function () {
    $result = $enrollmentService->enroll(new CreateEnrollmentDTO(
        userId: $user->id,
        courseId: $course->id,
    ));

    // $result IS an Enrollment model
    expect($result)->toBeInstanceOf(Enrollment::class);
    expect($result->user_id)->toBe($user->id);
    expect($result->status->getValue())->toBe('active');

    // Access relationships directly on the model
    expect($result->course->title)->toBe($course->title);
});
```

## Testing Commands

```bash
php artisan test                                    # All tests
php artisan test tests/Feature/CourseTest.php       # Specific file
php artisan test --filter=test_learner_can_enroll   # Filter by name
php artisan test tests/Feature                      # All feature tests
php artisan test tests/Unit                         # All unit tests
php artisan test --parallel                         # Parallel execution
```

## Gotchas

1. **Use factory states** - `->published()->public()`, not manual attributes
2. **Use global helpers** - `asAdmin()`, `createEnrolledLearner()` reduce boilerplate
3. **assertForbidden for auth** - Not assertRedirect (that's validation)
4. **describe/it for unit tests** - Groups related tests logically
5. **Class-based for feature tests** - When you need setUp() with shared state
6. **Services return models** - Assert directly on Eloquent model, access relationships normally
7. **Always test state transitions** - Use `assertModelState()` and `assertStateTransition()`
8. **Test all role x state x ownership** - Authorization test matrix
