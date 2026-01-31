---
name: enteraksi-architecture
description: Domain-Driven Design patterns, service layer, rich models, DTOs, and JsonResource for Enteraksi LMS. Use when creating services, DTOs, or working with domain layer code.
triggers:
  - create service
  - new service
  - domain service
  - create dto
  - data transfer object
  - domain layer
  - bounded context
  - DomainServiceProvider
  - model behavior
  - rich model
  - json resource
  - api resource
---

# Enteraksi DDD Architecture

## Core Principles

1. **Services return Eloquent Models** - NOT DTOs or value objects
2. **Models own behavior** - state transitions, events dispatched from model methods
3. **JsonResource for API/Inertia responses** - Laravel's built-in transformation layer
4. **Input DTOs for service parameters** - `CreateEnrollmentDTO` pattern
5. **Contracts only for strategies** - NOT for single-implementation services
6. **No unnecessary abstraction** - if Laravel solves it, use Laravel

## Directory Structure

```
app/
├── Domain/
│   └── {BoundedContext}/
│       ├── Contracts/         # Strategy interfaces ONLY
│       ├── DTOs/              # Input DTOs (CreateXxxDTO, XxxContext)
│       ├── Events/            # Domain events
│       ├── Exceptions/        # Domain-specific exceptions
│       ├── Listeners/         # Event listeners
│       ├── Services/          # Service implementations + resolvers/factories
│       ├── States/            # Spatie state machine classes
│       └── Strategies/        # Strategy pattern implementations
├── Http/
│   ├── Controllers/           # Thin controllers (delegate to services)
│   └── Resources/             # JsonResource for API/Inertia transformation
│       ├── Dashboard/
│       ├── Enrollment/
│       ├── LearningPath/
│       └── Progress/
├── Models/                    # Rich models with behavior methods
└── Providers/
    └── DomainServiceProvider.php  # Strategy tags + observability singletons
```

## Bounded Contexts

| Context | Purpose | Services |
|---------|---------|----------|
| Assessment | Quizzes, grading, attempts | GradingStrategyResolver |
| Course | Course content, invitations | CourseInvitationService, InvitationAcceptanceService |
| Enrollment | User enrollments, lifecycle | EnrollmentService |
| LearningPath | Paths, prerequisites | PathEnrollmentService, PathProgressService, PrerequisiteEvaluatorFactory |
| Progress | Lesson progress, completion | ProgressCalculatorFactory, ProgressTrackingService |
| Shared | Cross-cutting: logging, metrics, events | DomainLogger, MetricsService, HealthCheckService |

## Key Patterns

### 1. Rich Models with Behavior

Models own their state transitions and dispatch events:

```php
// app/Models/Enrollment.php
class Enrollment extends Model
{
    use HasStates;

    public function drop(?string $reason = null): self
    {
        if (! $this->isActive()) {
            throw new InvalidStateTransitionException(/* ... */);
        }

        DB::transaction(function () use ($reason) {
            $this->update(['status' => DroppedState::$name]);
            UserDropped::dispatch($this, $reason);
        });

        return $this;
    }

    public function complete(): self
    {
        if ($this->isCompleted()) {
            return $this; // Idempotent
        }

        DB::transaction(function () {
            $this->update([
                'status' => CompletedState::$name,
                'completed_at' => now(),
            ]);
            EnrollmentCompleted::dispatch($this);
        });

        return $this;
    }

    public function reactivate(bool $preserveProgress = true, ?int $invitedBy = null): self
    {
        // Validates state, updates, dispatches UserReenrolled
        // ...
        return $this;
    }
}
```

### 2. Services Return Models

Services orchestrate business logic and return Eloquent models:

```php
// app/Domain/Enrollment/Services/EnrollmentService.php
class EnrollmentService
{
    public function enroll(CreateEnrollmentDTO $dto): Enrollment
    {
        // Validate, check for re-enrollment, create or reactivate
        return DB::transaction(function () use ($dto) {
            $enrollment = Enrollment::create([/* ... */]);
            UserEnrolled::dispatch($enrollment);
            return $enrollment;  // Return model directly
        });
    }

    public function canEnroll(User $user, Course $course): bool { /* ... */ }
    public function getActiveEnrollment(User $user, Course $course): ?Enrollment { /* ... */ }
}
```

**No service contracts** - services are injected directly via constructor:

```php
class EnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentService $enrollmentService
    ) {}
}
```

### 3. Input DTOs (Standalone Readonly Classes)

For structured service inputs - no base class needed:

```php
// app/Domain/Enrollment/DTOs/CreateEnrollmentDTO.php
final readonly class CreateEnrollmentDTO
{
    public function __construct(
        public int $userId,
        public int $courseId,
        public ?int $invitedBy = null,
        public ?DateTimeInterface $enrolledAt = null,
    ) {}

    /**
     * @param array{
     *     user_id: int,
     *     course_id: int,
     *     invited_by?: int|null,
     *     enrolled_at?: string|null
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            courseId: $data['course_id'],
            invitedBy: $data['invited_by'] ?? null,
            enrolledAt: isset($data['enrolled_at'])
                ? new DateTimeImmutable($data['enrolled_at'])
                : null,
        );
    }
}
```

**Also used for authorization context** to avoid N+1 in policies:

```php
// app/Domain/Enrollment/DTOs/EnrollmentContext.php
final readonly class EnrollmentContext
{
    public static function for(User $user, Course $course): self
    {
        // Pre-fetch enrollment state in one query
    }
}
```

### 4. JsonResource for API/Inertia Responses

Controllers transform models using Laravel's built-in `JsonResource`:

```php
// app/Http/Resources/Dashboard/DashboardEnrollmentResource.php
class DashboardEnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course' => new DashboardCourseResource($this->course),
            'status' => (string) $this->status,
            'progress_percentage' => $this->progress_percentage,
            // ...
        ];
    }
}

// In controller:
return Inertia::render('Learner/Dashboard', [
    'enrollments' => DashboardEnrollmentResource::collection($enrollments),
]);
```

### 5. Controller Pattern

Thin controllers that delegate to services and transform with resources:

```php
class EnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentService $enrollmentService
    ) {}

    public function store(Request $request, Course $course): RedirectResponse
    {
        $enrollment = $this->enrollmentService->enroll(
            new CreateEnrollmentDTO(
                userId: $request->user()->id,
                courseId: $course->id,
            )
        );

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Berhasil mendaftar ke kursus.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $enrollment = $request->user()->enrollments()
            ->where('course_id', $course->id)
            ->firstOrFail();

        $enrollment->drop(); // Model owns behavior

        return redirect()
            ->route('learner.dashboard')
            ->with('success', 'Pendaftaran kursus dibatalkan.');
    }
}
```

### 6. DomainServiceProvider

Registers **strategy tags** and **observability singletons** only:

```php
class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerGradingStrategies();      // Tag + GradingStrategyResolver singleton
        $this->registerProgressCalculators();     // Tag + ProgressCalculatorFactory singleton
        $this->registerPrerequisiteEvaluators();  // Tag + PrerequisiteEvaluatorFactory singleton
        $this->registerObservabilityServices();   // DomainLogger, MetricsService, etc.
    }
}
```

### 7. Contracts Only for Strategies

Contracts exist **only** where multiple implementations are swapped:

| Contract | Implementations |
|----------|----------------|
| `GradingStrategyContract` | MultipleChoice, TrueFalse, ShortAnswer, Manual |
| `ProgressCalculatorContract` | LessonBased, Weighted, AssessmentInclusive |
| `PrerequisiteEvaluatorContract` | Sequential, ImmediatePrevious, NoPrerequisite, PricingAware |

**Do NOT create contracts for single-implementation services.**

## What Goes Where

| Need | Where |
|------|-------|
| Service input validation | Input DTO (`CreateEnrollmentDTO`) |
| API/Inertia response transformation | `JsonResource` in `app/Http/Resources/` |
| State transitions | Model methods (`$model->drop()`) |
| Business validation | Service (`validateEnrollment()`) |
| Events | Model methods dispatch after state change |
| Query helpers | Service (`getActiveEnrollment()`) |
| Authorization context | Context DTO (`EnrollmentContext`) |
| Pluggable algorithms | Strategy + Contract + Factory/Resolver |

## Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Service | `{Name}Service` | `EnrollmentService` |
| Input DTO | `{Action}{Entity}DTO` | `CreateEnrollmentDTO` |
| Context DTO | `{Entity}Context` | `EnrollmentContext` |
| JsonResource | `{Context}{Entity}Resource` | `DashboardEnrollmentResource` |
| Strategy Contract | `{Name}Contract` | `GradingStrategyContract` |
| Strategy Resolver | `{Name}Resolver` | `GradingStrategyResolver` |
| Strategy Factory | `{Name}Factory` | `ProgressCalculatorFactory` |
| Model Behavior | verb method | `drop()`, `complete()`, `reactivate()` |

## Anti-Patterns (DON'T)

```php
// DON'T: Create contracts for single implementations
interface EnrollmentServiceContract { }  // Unnecessary

// DON'T: Wrap models in result DTOs
final readonly class EnrollmentResult {
    public EnrollmentData $enrollment;  // Just return the model
}

// DON'T: Service methods for state transitions
$enrollmentService->drop($enrollment);  // Model owns this

// DON'T: Use Spatie Data (not in this project)
class EnrollmentData extends Data { }  // Use JsonResource instead
```
