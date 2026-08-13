# EnterLMS - Architecture Documentation

> **Purpose**: This document serves as the primary technical reference for understanding the EnterLMS Learning Management System architecture. It is designed to help developers (human and AI agents) work efficiently, accurately, and with minimal assumptions.

---

## Table of Contents
1. [System Overview](#system-overview)
2. [Technology Stack](#technology-stack)
3. [Directory Structure](#directory-structure)
4. [Backend Architecture](#backend-architecture)
5. [Frontend Architecture](#frontend-architecture)
6. [Authentication & Authorization](#authentication--authorization)
7. [Key Services](#key-services)
8. [Configuration](#configuration)

---

## System Overview

**EnterLMS** is a Learning Management System (LMS) built for Indonesian banking/financial compliance training. It follows industry standards (SCORM, xAPI, LTI) and is designed with:

- **Mobile-first responsive UI** (Udemy-inspired)
- **Bahasa Indonesia** as primary language
- **Banking/OJK compliance** focus
- **Integration-ready** architecture (HRIS, ERP, video conferencing)

### Core Capabilities
| Module | Status | Description |
|--------|--------|-------------|
| User Management | Built | Roles, authentication, 2FA |
| Course Management | Built | CRUD, sections, lessons, media |
| Content Delivery | Built | Text, video, audio, PDF, YouTube, conferences |
| Assessment & Grading | Built | Quizzes, multiple question types, auto/manual grading |
| Progress Tracking | Built | Lesson progress, course completion |
| Learning Paths | Built | Multi-course sequences with prerequisites |
| Enrollment & Invitations | Built | Self-enrollment, invitations, CSV bulk import |
| Ratings & Reviews | Built | 5-star ratings with reviews |
| Certificate Management | Planned | Completion certificates |
| Communication | Planned | Forums, messaging, announcements |
| Reporting & Analytics | Planned | Dashboards, reports |

---

## Technology Stack

### Backend
| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Laravel | 12 |
| PHP | PHP | 8.4 |
| Authentication | Laravel Fortify | v1 |
| Database | MySQL/SQLite | - |
| State Machines | spatie/laravel-model-states | - |
| Static Analysis | Larastan (PHPStan) | v3 |
| Testing | Pest (PHPUnit) | v3 (v11) |
| Code Style | Laravel Pint | v1 |

### Frontend
| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Vue.js | 3 |
| Bridge | Inertia.js | v2 |
| Styling | Tailwind CSS | v4 |
| Language | TypeScript | - |
| Build Tool | Vite | - |
| Route Generation | Wayfinder | v0 |
| UI Components | Shadcn/ui (Vue) | - |

### Development Tools
| Tool | Purpose |
|------|---------|
| Laravel Sail | Docker development environment |
| Laravel Boost MCP | Documentation search, Tinker, Artisan |
| ESLint + Prettier | JS/TS code formatting |

---

## Directory Structure

```
enterlms/
├── app/
│   ├── Actions/Fortify/        # Authentication actions
│   ├── Domain/                 # Domain-Driven Design layer
│   │   ├── Assessment/         # Grading strategies, DTOs
│   │   ├── Course/             # State machine, events, services
│   │   ├── Enrollment/         # Service, states, events
│   │   ├── LearningPath/       # Path enrollment, prerequisites, progress
│   │   ├── Progress/           # Tracking service, calculators
│   │   └── Shared/             # Base contracts, value objects, logging
│   ├── Http/
│   │   ├── Controllers/        # 20 controllers
│   │   ├── Middleware/         # HandleAppearance, HandleInertiaRequests
│   │   ├── Requests/           # Form request classes
│   │   └── Resources/          # JsonResource for API/Inertia responses
│   ├── Models/                 # 20 Eloquent models (rich models with behavior)
│   ├── Policies/               # Authorization policies
│   ├── Providers/              # AppServiceProvider, FortifyServiceProvider
│   └── Services/               # MediaSeederHelper, TipTapRenderer
├── bootstrap/
│   └── app.php                 # Middleware & exception config
├── config/
│   └── fortify.php             # Authentication features config
├── database/
│   ├── factories/              # 19 model factories
│   ├── migrations/             # 38 migration files
│   └── seeders/                # 6 seeders
├── resources/
│   ├── css/                    # Tailwind CSS v4
│   └── js/
│       ├── components/         # Vue components (ui, crud, features, domain)
│       ├── composables/        # 20 composables (data, ui, utils, root)
│       ├── layouts/            # Layout components
│       ├── lib/                # Constants, formatters, utilities
│       ├── pages/              # Inertia page components
│       ├── stores/             # Pinia stores (complex editors only)
│       └── types/              # TypeScript definitions
├── routes/
│   ├── web.php                 # Main routes
│   ├── courses.php             # Course-related routes
│   ├── learning_paths.php      # Learning path + enrollment routes
│   └── settings.php            # User settings routes
├── tests/
│   ├── Feature/                # Feature tests (integration)
│   └── Unit/                   # Unit tests
└── .ai/                        # AI planning & documentation
```

---

## Backend Architecture

### Controllers (20 total)

#### Core Controllers
| Controller | File | Purpose |
|------------|------|---------|
| `HomeController` | `HomeController.php` | Landing page with featured courses, stats |
| `DashboardController` | `DashboardController.php` | Admin/instructor dashboard |
| `LearnerDashboardController` | `LearnerDashboardController.php` | Learner home with enrollments, invitations |

#### Course Management
| Controller | File | Purpose |
|------------|------|---------|
| `CourseController` | `CourseController.php` | Course CRUD, search, filtering |
| `CourseSectionController` | `CourseSectionController.php` | Section CRUD within courses |
| `LessonController` | `LessonController.php` | Lesson CRUD, content display |
| `LessonPreviewController` | `LessonPreviewController.php` | Free preview for non-enrolled |
| `LessonProgressController` | `LessonProgressController.php` | Progress tracking (page, media, completion) |
| `CoursePublishController` | `CoursePublishController.php` | Publish/unpublish/archive workflows |
| `CourseReorderController` | `CourseReorderController.php` | Drag-drop reordering |
| `CourseDurationController` | `CourseDurationController.php` | Duration recalculation |
| `MediaController` | `MediaController.php` | File uploads (video, audio, documents) |

#### Enrollment & Access
| Controller | File | Purpose |
|------------|------|---------|
| `EnrollmentController` | `EnrollmentController.php` | Enroll/unenroll, accept/decline invitations |
| `CourseInvitationController` | `CourseInvitationController.php` | Single/bulk invitations, learner search |
| `CourseRatingController` | `CourseRatingController.php` | Ratings and reviews |

#### Assessment System
| Controller | File | Purpose |
|------------|------|---------|
| `AssessmentController` | `AssessmentController.php` | Assessment CRUD, attempts, grading |
| `QuestionController` | `QuestionController.php` | Question management, bulk updates |

#### Learning Paths
| Controller | File | Purpose |
|------------|------|---------|
| `LearningPathController` | `LearningPathController.php` | Learning path CRUD, publish/unpublish, course ordering |
| `LearningPathEnrollmentController` | `LearningPathEnrollmentController.php` | Learner enrollment, progress, browse, drop |

#### Settings
| Controller | File | Purpose |
|------------|------|---------|
| `Settings/ProfileController` | `Settings/ProfileController.php` | User profile management |
| `Settings/PasswordController` | `Settings/PasswordController.php` | Password change |
| `Settings/TwoFactorAuthenticationController` | `Settings/TwoFactorAuthenticationController.php` | 2FA setup |

### Models (20 total)

See [DATA-MODEL.md](./DATA-MODEL.md) for complete model documentation including:
- All 20 models with relationships
- Casts, scopes, and accessors
- Rich behavior methods (state transitions, event dispatching)
- Database schema

### Request Validation

Form Request classes handle validation with Indonesian error messages:

| Domain | Requests |
|--------|----------|
| Course | `StoreCourseRequest`, `UpdateCourseRequest` |
| Section | `StoreSectionRequest`, `UpdateSectionRequest` |
| Lesson | `StoreLessonRequest`, `UpdateLessonRequest` |
| Media | `StoreMediaRequest` (dynamic file size by type) |
| Assessment | `StoreAssessmentRequest`, `UpdateAssessmentRequest` |
| Question | `StoreQuestionRequest`, `UpdateQuestionRequest` |
| Rating | `StoreRatingRequest`, `UpdateRatingRequest` |
| Invitation | `StoreCourseInvitationRequest`, `BulkCourseInvitationRequest` |
| Learning Path | `StoreLearningPathRequest`, `UpdateLearningPathRequest` |
| Settings | `ProfileUpdateRequest`, `TwoFactorAuthenticationRequest` |

---

## Domain Layer Architecture

The application uses Domain-Driven Design patterns in `app/Domain/` for complex business logic.

### Domain Structure

```
app/Domain/
├── Assessment/
│   ├── Contracts/              # GradingStrategyContract (strategy interface)
│   ├── DTOs/                   # GradingResult
│   ├── Exceptions/             # MaxAttemptsReachedException
│   ├── Services/               # GradingStrategyResolver
│   ├── Strategies/             # MultipleChoice, TrueFalse, ShortAnswer, Manual
│   └── ValueObjects/           # Score
├── Course/
│   ├── Events/                 # CoursePublished, CourseArchived, CourseUnpublished
│   ├── Services/               # CourseInvitationService, InvitationAcceptanceService
│   └── States/                 # DraftState, PublishedState, ArchivedState
├── Enrollment/
│   ├── DTOs/                   # CreateEnrollmentDTO, EnrollmentContext
│   ├── Events/                 # UserEnrolled, EnrollmentCompleted, UserDropped, UserReenrolled
│   ├── Exceptions/             # AlreadyEnrolledException, CourseNotPublishedException
│   ├── Listeners/              # SendWelcomeNotification, SendCompletionCongratulations
│   ├── Notifications/          # WelcomeToCourseMail, CourseCompletedMail
│   ├── Services/               # EnrollmentService (concrete, no contract)
│   └── States/                 # ActiveState, CompletedState, DroppedState
├── LearningPath/
│   ├── Contracts/              # PrerequisiteEvaluatorContract (strategy interface)
│   ├── DTOs/                   # PathProgressResult, PathEnrollmentResult
│   ├── Events/                 # PathCompleted, PathDropped, PathEnrollmentCreated
│   ├── Exceptions/             # PathNotPublishedException
│   ├── Listeners/              # UpdatePathProgressOnCourseDrop
│   ├── Notifications/          # PathCompletedMail
│   ├── Services/               # PathEnrollmentService, PathProgressService
│   ├── States/                 # ActivePathState, CompletedPathState, DroppedPathState,
│   │                           # LockedCourseState, AvailableCourseState, InProgressCourseState, CompletedCourseState
│   └── Strategies/             # Sequential, ImmediatePrevious, NoPrerequisite, PricingAware
├── Progress/
│   ├── Contracts/              # ProgressCalculatorContract (strategy interface)
│   ├── DTOs/                   # ProgressResult, ProgressUpdateDTO
│   ├── Events/                 # LessonCompleted, ProgressUpdated
│   ├── Listeners/              # RecalculateCourseProgress
│   ├── Services/               # ProgressTrackingService (concrete), ProgressCalculatorFactory
│   ├── Strategies/             # LessonBased, AssessmentInclusive, Weighted calculators
│   └── ValueObjects/           # Percentage
└── Shared/
    ├── Contracts/              # DomainEvent, StrategyContract
    ├── Exceptions/             # DomainException, InvalidStateTransitionException
    ├── Listeners/              # LogDomainEvent
    ├── Services/               # DomainLogger, MetricsService, HealthCheckService
    └── ValueObjects/           # Duration, Percentage
```

> **Important:** Only strategy interfaces use contracts (GradingStrategyContract, ProgressCalculatorContract, PrerequisiteEvaluatorContract). Service classes like EnrollmentService and ProgressTrackingService are injected as concrete classes directly—no service contracts.

### Key Design Patterns

#### Rich Models with Behavior

Models own their state transitions and dispatch events:

```php
// Course lifecycle (methods on model)
$course->publish($admin);   // Sets status, published_at, published_by
$course->unpublish();       // Reverts to draft
$course->archive();         // Archives course

// Enrollment lifecycle (methods dispatch events in DB::transaction)
$enrollment->drop($reason);          // → DroppedState, dispatches UserDropped
$enrollment->complete();              // → CompletedState, dispatches EnrollmentCompleted (idempotent)
$enrollment->reactivate($preserve);   // → ActiveState, dispatches UserReenrolled

// Learning Path enrollment
$pathEnrollment->drop($reason);   // → DroppedPathState, dispatches PathDropped
$pathEnrollment->complete();       // → CompletedPathState, dispatches PathCompleted
```

#### State Machine Pattern (spatie/laravel-model-states)

Course, Enrollment, and LearningPath entities use Spatie state machines:

```php
// Course states: draft → published ↔ archived
$course->isDraft();          // Check state
$course->canBeEdited();      // Delegates to state class

// Enrollment states: active → completed | dropped → active (re-enrollment)
$enrollment->isActive();
$enrollment->canAccessContent();   // Delegates to state class

// Learning Path states: active → completed | dropped
// Course Progress states: locked → available → in_progress → completed
```

#### Strategy Pattern (Progress Calculation, Grading)

Progress calculators are swappable strategies:

| Strategy | Description | Use Case |
|----------|-------------|----------|
| `LessonBasedProgressCalculator` | % of completed lessons | Simple courses |
| `AssessmentInclusiveProgressCalculator` | 70% lessons + 30% assessments | Courses with required assessments |
| `WeightedProgressCalculator` | Custom section weights | Complex curricula |

Grading strategies handle different question types:

| Strategy | Question Types | Auto-Grade |
|----------|---------------|------------|
| `MultipleChoiceGradingStrategy` | multiple_choice | Yes |
| `TrueFalseGradingStrategy` | true_false | Yes |
| `ShortAnswerGradingStrategy` | short_answer | Partial |
| `ManualGradingStrategy` | essay, file_upload, matching | No |

#### Service Layer Pattern

Domain services are concrete classes injected via constructor (no service contracts):

```php
// In controller constructor
public function __construct(
    protected EnrollmentService $enrollmentService,
    protected ProgressTrackingService $progressService,
) {}

// Services return Eloquent Models directly
$enrollment = $this->enrollmentService->enroll(new CreateEnrollmentDTO(
    userId: $user->id,
    courseId: $course->id,
));

// Progress tracking
$this->progressService->markLessonComplete($enrollment, $lesson);
$this->progressService->updatePageProgress($enrollment, $lesson, $page, $totalPages);
$progress = $this->progressService->calculateProgress($enrollment);
```

### Domain Events

Events are dispatched for significant state changes:

| Event | When Dispatched |
|-------|-----------------|
| `UserEnrolled` | User enrolls in course |
| `EnrollmentCompleted` | Course completion (100% progress) |
| `UserDropped` | User drops from course |
| `UserReenrolled` | Dropped enrollment reactivated |
| `LessonCompleted` | Lesson marked complete |
| `ProgressUpdated` | Progress percentage changes |
| `CoursePublished` | Course published by admin |
| `CourseArchived` | Course archived |
| `PathEnrollmentCreated` | User enrolls in learning path |
| `PathCompleted` | All required path courses completed |
| `PathDropped` | User drops from learning path |

---

## Frontend Architecture

### Page Structure

```
resources/js/pages/
├── Welcome.vue                 # Landing page
├── Dashboard.vue               # Admin dashboard
├── learner/
│   └── Dashboard.vue           # Learner home
├── auth/                       # 8 auth pages (login, register, 2FA, etc.)
├── courses/
│   ├── Index.vue               # Admin course list
│   ├── Create.vue              # Create course
│   ├── Edit.vue                # Edit course
│   ├── Show.vue                # Admin course detail
│   ├── Detail.vue              # Learner course detail
│   ├── Browse.vue              # Public course browsing
│   └── LessonPreview.vue       # Free preview
├── lessons/
│   ├── Show.vue                # Lesson viewer (all content types)
│   └── Edit.vue                # Edit lesson
├── assessments/
│   ├── Index.vue               # Assessment list
│   ├── Create.vue, Edit.vue    # CRUD
│   ├── Show.vue                # Assessment overview
│   ├── Questions.vue           # Question management
│   ├── Attempt.vue             # Take assessment
│   ├── AttemptComplete.vue     # Results
│   └── Grade.vue               # Grading interface
├── learning_paths/
│   ├── Index.vue, Create.vue, Edit.vue, Show.vue
└── settings/
    ├── Profile.vue, Password.vue, TwoFactor.vue, Appearance.vue
```

### Layout System

| Layout | Used For |
|--------|----------|
| `AppLayout.vue` | Admin/instructor authenticated pages |
| `AuthLayout.vue` | Authentication pages |
| `Navbar.vue` + `Footer.vue` | Public/learner pages |

### Component Library

Located in `resources/js/components/`:

| Category | Components |
|----------|------------|
| **UI Library** (`ui/`) | 20+ Shadcn/ui families (button, card, dialog, etc.) |
| **CRUD** (`crud/`) | PageHeader, FormSection, DataCard, EmptyState, Pagination |
| **Course** (`courses/`) | InvitationsTab, CsvImportDialog, MyLearningCard |
| **Lesson** (`lesson/`) | YouTubePlayer, PaginatedTextContent, PaginatedPDFContent |
| **Home** (`home/`) | HeroSection, FeaturedCourses, CategoryCard, Navbar, Footer |

### Composables

Organized in `data/`, `ui/`, `utils/`, and root directories:

| Category | Composables |
|----------|-------------|
| **Data** | `useCourse`, `useCourses`, `useEnrollment`, `useLesson`, `useAssessment` |
| **UI** | `useModal`, `useConfirmation`, `useToast`, `useSearch`, `usePagination`, `useTabs` |
| **Utils** | `useEventListener`, `useDebouncedWatch` |
| **Root** | `useAppearance`, `useInitials`, `useTwoFactorAuth`, `useLessonProgress`, `useLessonMediaProgress`, `useLessonPagination`, `useAssessmentTimer` |

### Data Flow Pattern

```
Backend (Laravel)                    Frontend (Vue)
─────────────────                    ──────────────
Controller                           Page Component
    │                                      │
    ├─ Authorize (Policy)                  │
    ├─ Query Data                          │
    ├─ Return Inertia::render()  ───────>  │
    │      with props                      │
    │                                      ├─ Receive props
    │                                      ├─ Render UI
    │                                      │
    │  <─────────────────────────────────  ├─ User action
    │      Inertia form/router             │
    │                                      │
    ├─ Validate (FormRequest)              │
    ├─ Execute action                      │
    └─ Redirect/Response  ──────────────>  └─ Update UI
```

---

## Authentication & Authorization

### Authentication (Fortify)

**Enabled Features:**
- User registration
- Password reset (email-based)
- Email verification
- Two-factor authentication (TOTP with confirmation)

**Rate Limiting:**
- Login: 5 requests/minute per email+IP
- 2FA: 5 requests/minute per session

### User Roles

| Role | Code | Capabilities |
|------|------|--------------|
| Learner | `learner` | Enroll, view content, take assessments, rate courses |
| Content Manager | `content_manager` | Create/manage own courses, cannot publish |
| Trainer | `trainer` | Same as content_manager + invite learners |
| LMS Admin | `lms_admin` | Full access, publish/archive, grade all |

**Helper Methods** (User model):
```php
$user->isLearner();
$user->isContentManager();
$user->isTrainer();
$user->isLmsAdmin();
$user->canManageCourses(); // content_manager, trainer, lms_admin
```

### Authorization Policies

| Policy | Model | Key Rules |
|--------|-------|-----------|
| `CoursePolicy` | Course | Published courses cannot be edited (except LMS Admin); Enrollment required for lesson access |
| `CourseSectionPolicy` | CourseSection | Delegates to parent Course policy |
| `LessonPolicy` | Lesson | Active enrollment required for learners |
| `AssessmentPolicy` | Assessment | Published assessments cannot be modified; Enrollment for attempts |
| `CourseRatingPolicy` | CourseRating | Ownership-based; Must be enrolled to rate |
| `CourseInvitationPolicy` | CourseInvitation | Course owner, LMS Admin, or Trainer can invite |
| `LearningPathPolicy` | LearningPath | Creator or LMS Admin can modify |

---

## Key Services

### Domain Services

Services are concrete classes—no service contracts. Only strategy interfaces use contracts.

| Service | Domain | Purpose |
|---------|--------|---------|
| `EnrollmentService` | Enrollment | Enroll users, check eligibility, manage re-enrollment |
| `ProgressTrackingService` | Progress | Track lesson/course progress, mark completions |
| `PathEnrollmentService` | LearningPath | Path enrollment, cascade course enrollment |
| `PathProgressService` | LearningPath | Path progress calculation, course unlocking |
| `CourseInvitationService` | Course | Invitation creation, bulk import |
| `InvitationAcceptanceService` | Course | Accept/decline invitations |
| `GradingStrategyResolver` | Assessment | Resolve grading strategy by question type |
| `ProgressCalculatorFactory` | Progress | Create progress calculator by strategy name |

**Usage Example:**
```php
// Inject concrete classes in controller constructor
public function __construct(
    protected EnrollmentService $enrollmentService,
) {}

// Services return Eloquent Models directly
$enrollment = $this->enrollmentService->enroll(new CreateEnrollmentDTO(
    userId: $user->id,
    courseId: $course->id,
));
// $enrollment is an Enrollment model instance
```

### Utility Services

| Service | Location | Purpose |
|---------|----------|---------|
| `MediaSeederHelper` | `app/Services/` | Media operations for seeders |
| `TipTapRenderer` | `app/Services/` | Convert TipTap JSON to HTML |
| `DomainLogger` | `app/Domain/Shared/Services/` | Structured domain event logging |
| `MetricsService` | `app/Domain/Shared/Services/` | Performance metrics tracking |
| `HealthCheckService` | `app/Domain/Shared/Services/` | System health checks |

---

## Configuration

### Key Config Files

| File | Purpose |
|------|---------|
| `config/fortify.php` | Authentication features, rate limits |
| `bootstrap/app.php` | Middleware stack, exception handling |
| `vite.config.ts` | Frontend build configuration |
| `components.json` | Shadcn/ui component configuration |

### Environment Variables (Key)

```env
APP_NAME=EnterLMS
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_DATABASE=enterlms

MAIL_MAILER=smtp  # For password reset, email verification
```

### Middleware Stack

1. Cookie Encryption (except `appearance`, `sidebar_state`)
2. `HandleAppearance` - Shares theme preference
3. `HandleInertiaRequests` - Inertia props (auth, flash messages)
4. `AddLinkHeadersForPreloadedAssets` - Performance

---

## Quick Reference

### Common Commands

```bash
# Development
php artisan serve          # Start Laravel server
npm run dev                # Start Vite dev server
composer run dev           # Start both

# Database
php artisan migrate        # Run migrations
php artisan db:seed        # Seed database
php artisan migrate:fresh --seed  # Reset and seed

# Testing
php artisan test           # Run all tests
php artisan test --filter=CourseTest  # Filter tests

# Code Quality
vendor/bin/pint            # Fix PHP code style
npm run lint               # Fix JS/TS code style

# Wayfinder
php artisan wayfinder:generate  # Regenerate route types
```

### Test Users (After Seeding)

| Role | Email | Password |
|------|-------|----------|
| Learner | `test@example.com` | `password` |
| Content Manager | `content@example.com` | `password` |
| Trainer | `trainer@example.com` | `password` |
| LMS Admin | `admin@example.com` | `password` |

---

## Related Documentation

- [DATA-MODEL.md](./DATA-MODEL.md) - Complete entity documentation
- [FEATURES.md](./FEATURES.md) - Feature flows and user journeys
