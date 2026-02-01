# Enteraksi LMS — Top 5 High-Impact Improvements

> Analysis date: 2026-02-01
> Scope: Feature velocity, stability, business alignment, consistency

---

## Executive Summary

After deep analysis of the codebase (21 models, 30 controllers, 49 pages, 118 test files), these are the 5 improvements ranked by **compounding impact** — each one makes future development faster, safer, and more consistent.

---

## #1. JsonResource Transformation Layer (CRITICAL)

**Impact:** Feature velocity, API readiness, security, consistency
**Current state:** ~30 `Inertia::render()` calls pass raw Eloquent models directly to frontend

### The Problem

```php
// AssessmentController — raw model to frontend
return Inertia::render('assessments/Index', [
    'course' => $course,           // raw Course model (ALL attributes)
    'assessments' => $assessments, // raw paginated models
]);
```

- **Security risk**: Every model attribute (including `deleted_at`, internal IDs, timestamps) sent to browser
- **Coupling**: Frontend TypeScript types must mirror DB columns exactly — any migration breaks frontend
- **No API path**: If mobile app or external integration needed, every controller needs rework
- **Payload waste**: Sending unnecessary data (user passwords_hash excluded by $hidden, but other models may not)

### What Exists Today

Only 11 Resources for 21 models:
- Course: `HomeCourseResource`, `DashboardCourseResource` (used for specific views)
- LearningPath: `LearningPathBrowseResource`, `LearningPathShowResource`
- Enrollment: `PathEnrollmentBasicResource`, `DashboardEnrollmentResource`
- Invitation: `CourseInvitationResource` (has N+1 bug — no `whenLoaded()`)

**Missing resources for:** Assessment, AssessmentAttempt, Lesson, CourseSection, Question, User, Category, Tag, CourseRating, LessonProgress, Media

### Fix Plan

**Phase 1a — Core models (Assessment, Lesson, User)**
- Create `AssessmentResource`, `AssessmentIndexResource`
- Create `LessonResource`, `LessonShowResource`
- Create `UserResource`, `UserSummaryResource`
- Refactor controllers to use resources in `Inertia::render()`
- Fix `CourseInvitationResource` N+1 (`whenLoaded()` guards)

**Phase 1b — Supporting models**
- Create `CourseSectionResource`, `QuestionResource`, `QuestionOptionResource`
- Create `CategoryResource`, `TagResource`
- Create `CourseRatingResource`, `MediaResource`

**Phase 1c — Standardize all controllers**
- Audit every `Inertia::render()` call
- Replace raw model props with Resource responses
- Update frontend types to match Resource output (not DB columns)

### Success Criteria
- Zero raw Eloquent models in `Inertia::render()` calls
- All resources use `whenLoaded()` for optional relations
- Frontend types generated from Resource output, not DB schema

---

## #2. Frontend Convention Standardization (HIGH)

**Impact:** Feature velocity, developer onboarding, consistency
**Current state:** 3 different route import patterns, duplicate type definitions, no centralized labels

### The Problem

**Three Wayfinder import styles coexist:**
```typescript
// Style 1: Destructured actions (courses/Index.vue)
import { index, create, show } from '@/actions/App/Http/Controllers/CourseController';

// Style 2: Default controller import (enrollments/Index.vue, admin pages)
import MyLearningController from '@/actions/App/Http/Controllers/MyLearningController';

// Style 3: Named routes (Dashboard.vue, auth pages)
import { dashboard } from '@/routes';
import { index as coursesIndex } from '@/routes/courses';
```

**Duplicate types — same type defined in page AND in types/:**
- `CourseListItem` in `courses/Index.vue` AND `types/models/course.ts`
- `UserListItem` in `admin/users/Index.vue`
- `TagListItem` in `admin/tags/Index.vue`

**Status enum mismatches:**
- Frontend `EnrollmentStatus` has `pending`, `suspended`, `cancelled` — backend only has `active`, `completed`, `dropped`

### Fix Plan

**Phase 2a — Standardize Wayfinder imports**
- Pick ONE pattern: destructured named imports (tree-shakable, most common)
- Refactor all pages to use: `import { index, store } from '@/actions/...'`
- Only use `@/routes/` for non-controller routes (auth, settings)
- Document the convention in CLAUDE.md frontend section

**Phase 2b — Consolidate TypeScript types**
- Move all inline page types to `types/models/`
- Remove duplicate type definitions from page files
- Sync frontend enums with backend state machines (remove phantom states)
- Add `PaginatedResponse<T>` generic type, use everywhere

**Phase 2c — Centralize label/badge functions**
- Move all inline `statusBadge()`, `getRoleBadge()` functions to `lib/formatters.ts`
- Remove duplicated formatting logic from page templates
- Single source of truth for Indonesian labels

### Success Criteria
- One Wayfinder import pattern across all pages
- Zero inline type definitions in page files
- All badge/label functions in `lib/formatters.ts`
- Frontend enums match backend exactly

---

## #3. Missing Model Policies (HIGH — Security)

**Impact:** Security, stability, authorization consistency
**Current state:** 7 models without policies; 3 of them have controllers

### The Problem

| Model | Has Controller? | Risk |
|-------|----------------|------|
| **Question** | ✅ QuestionController | Learners could CRUD questions without authorization |
| **Media** | ✅ MediaController | Unauthorized file upload/access |
| **LessonProgress** | ✅ LessonProgressController | Progress manipulation |
| QuestionOption | ❌ (via Question) | Low — always accessed through parent |
| AttemptAnswer | ❌ (via Attempt) | Low — always accessed through parent |
| LearningPathCourse | ❌ (pivot) | Low — managed by LP admin |
| LearningPathCourseProgress | ❌ (auto) | Low — system-managed |

The 3 models with controllers but NO policies (Question, Media, LessonProgress) rely on the parent resource's authorization. This works today but is fragile — any new endpoint on these models bypasses authorization.

### Fix Plan

**Phase 3a — Create policies for controller-backed models**
- `QuestionPolicy` — CRUD authorization scoped to course ownership
- `MediaPolicy` — upload/delete scoped to lesson/course ownership
- `LessonProgressPolicy` — mark complete scoped to enrollment

**Phase 3b — Wire policies into controllers**
- Add `Gate::authorize()` calls in QuestionController, MediaController, LessonProgressController
- Add policy tests (unit tests following existing patterns in tests/Unit/Policies/)

**Phase 3c — Audit existing authorization**
- Review all controllers for Gate::authorize calls
- Ensure nested route scoping is enforced (child belongs to parent)
- Run authorization test suite

### Success Criteria
- All controller-backed models have policies
- 100% policy test coverage (currently 13/13 policies tested — maintain this)
- No controller action without Gate::authorize

---

## #4. Soft Delete Admin UI & Trash Management (MEDIUM-HIGH)

**Impact:** Business alignment, admin UX, data recovery
**Current state:** 12 models use SoftDeletes — zero admin UI for trash/restore

### The Problem

```php
// 12 models implement SoftDeletes:
// User, Course, CourseSection, Lesson, Enrollment, CourseRating,
// Assessment, Question, QuestionOption, AssessmentAttempt, AttemptAnswer, LearningPath
```

But **no controller** calls `withTrashed()`, `onlyTrashed()`, or `restore()`. If an admin accidentally deletes a course with 50 lessons, there is NO way to recover it through the UI. The data exists in the database but is invisible.

For an LMS where course content represents significant investment, this is a business-critical gap.

### Fix Plan

**Phase 4a — Core trash/restore for high-value models**
- Add `trashed` filter to Course index (admin view)
- Add restore/force-delete actions to CourseController
- Add trash view for Learning Paths
- Cascade restore (Course → Sections → Lessons)

**Phase 4b — Admin trash dashboard**
- Create `Admin/TrashController` with index view showing all trashed items
- Group by model type (Courses, Assessments, Users, etc.)
- Bulk restore and force delete actions
- Add policy methods: `restore()`, `forceDelete()` (some policies already have them)

**Phase 4c — Soft delete consistency**
- Add soft delete cascade for Assessment → Questions → Options
- Add "Undo" toast after soft delete (immediate restore within 10s)
- Add scheduled cleanup: force-delete items trashed > 30 days

### Success Criteria
- Admin can view and restore trashed courses, lessons, assessments
- Cascade restore works (restoring course restores its sections + lessons)
- Trash dashboard shows all deleted content across models

---

## #5. N+1 Prevention & Eager Loading Enforcement (MEDIUM)

**Impact:** Stability, performance, scalability
**Current state:** 2 models use RequiresEagerLoading trait; several resources/models have N+1 risks

### The Problem

**Known N+1 risks:**
1. `CourseInvitationResource` — accesses `$this->user` and `$this->inviter` without `whenLoaded()`
2. `Assessment::canBeAttemptedBy()` — queries enrollment + attempts per assessment (if called in loop)
3. `LessonProgress::isMediaBased()` — accesses `$this->lesson->content_type` without checking if loaded

**RequiresEagerLoading only on 2/21 models:**
- Course, CourseSection use the trait
- Models with computed attributes/counts (Assessment, LearningPath, Lesson) don't

### Fix Plan

**Phase 5a — Fix known N+1 bugs**
- Fix `CourseInvitationResource`: wrap user/inviter in `whenLoaded()`
- Add eager loading check to `Assessment::canBeAttemptedBy()`
- Add eager loading check to `LessonProgress::isMediaBased()`

**Phase 5b — Expand RequiresEagerLoading**
- Add trait to Assessment (has `questions_count`, `attempts_count`)
- Add trait to LearningPath (has `courses_count`, `enrollments_count`)
- Add trait to Lesson (has `media` relation accessed in show)

**Phase 5c — Automated detection**
- Enable Laravel Debugbar/Telescope N+1 detection in local env
- Add `preventLazyLoading()` in AppServiceProvider for local/testing
- Document eager loading requirements in model PHPDoc

### Success Criteria
- Zero N+1 queries in resource classes
- `preventLazyLoading()` enabled in dev (catches new N+1 instantly)
- RequiresEagerLoading on all models with computed attributes

---

## Implementation Phases

### Phase 1 — Foundation (JsonResource + Critical Policies)
- [ ] 1a: Core JsonResources (Assessment, Lesson, User)
- [ ] 3a: Missing policies (Question, Media, LessonProgress)
- [ ] 5a: Fix known N+1 bugs

**Why first:** These are security and architectural foundations. Every feature built on top of raw models compounds the debt.

### Phase 2 — Consistency (Frontend + Resource rollout)
- [ ] 2a: Standardize Wayfinder imports
- [ ] 2b: Consolidate TypeScript types
- [ ] 1b: Supporting model resources

**Why second:** Once JsonResources exist, frontend types can be standardized to match. Doing this before more features prevents further inconsistency.

### Phase 3 — Business Value (Soft deletes + Polish)
- [ ] 4a: Core trash/restore for courses
- [ ] 2c: Centralize label/badge functions
- [ ] 1c: Standardize all controllers

**Why third:** Trash management requires working resource layer. Label centralization reduces frontend duplication.

### Phase 4 — Hardening (Prevention + Scale)
- [ ] 5b: Expand RequiresEagerLoading
- [ ] 5c: Automated N+1 detection
- [ ] 4b: Admin trash dashboard
- [ ] 3b-3c: Authorization audit
- [ ] 4c: Soft delete consistency + cleanup

**Why last:** These are preventive measures. They protect against future issues but don't block current development.

---

## Metrics to Track

| Metric | Current | Target |
|--------|---------|--------|
| Raw models in Inertia::render | ~30 calls | 0 |
| JsonResources | 11 | 21+ |
| Policies | 13/21 models | 16/21 (controller-backed) |
| Wayfinder import patterns | 3 styles | 1 style |
| Duplicate frontend types | ~5 | 0 |
| Trashable models with UI | 0/12 | 5/12 (high-value) |
| Models with RequiresEagerLoading | 2 | 6 |
| N+1 bugs in resources | 3 known | 0 |
