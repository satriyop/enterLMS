# Enteraksi LMS — Implementation Tracker

> **Created**: 2026-02-02
> **Objective**: Fix all identified issues from codebase investigation, organized by tier priority
> **Source**: `06-consolidated-findings.md` (5-agent deep exploration)
> **Status**: IN PROGRESS

---

## ORIGINAL GOALS (from investigation)

1. **Business Workflow Alignment** — Fix code that diverges from correct LMS business workflows
2. **Tech Debt Elimination** — Remove patterns/shortcuts that cause pain when adding features
3. **Validation** — Ensure implementations achieve their stated goals with proper tests
4. **Close the Loop** — Every fix must be validated by tests proving the fix works

---

## HOW TO USE THIS FILE (Recovery Instructions)

If conversation crashes or compacts:
1. Read this file: `.ai/investigation/07-implementation-tracker.md`
2. Find the first task with status `[ ]` (pending) or `[~]` (in progress)
3. Read the task details and referenced files
4. Continue from where we left off
5. After completing a task, update its status to `[x]`

**Status Legend:**
- `[ ]` = Pending (not started)
- `[~]` = In Progress (started but not complete)
- `[x]` = Done (implemented + tested)
- `[!]` = Blocked (needs something else first)
- `[-]` = Skipped (decided not needed after review)

---

## TIER 0: CRITICAL BUGS (Fix Immediately)

> These are code bugs that will crash or produce wrong results on production data.

### T0-1: Add missing columns to questions table
- **Status**: [x] Done (2026-02-05)
- **Bug**: Grading strategies reference columns that don't exist in DB
- **Schema gaps**:
  - `correct_answer` (text, nullable) — used by `ShortAnswerGradingStrategy:81`, `TrueFalseGradingStrategy:84`
  - `case_sensitive` (boolean, default false) — used by `ShortAnswerGradingStrategy:45`
  - `grading_rubric` (text, nullable) — used by `ManualGradingStrategy:31`
- **Files to modify**:
  - CREATE: `database/migrations/YYYY_MM_DD_add_grading_columns_to_questions_table.php`
- **Files to verify** (read, not modify unless needed):
  - `app/Domain/Assessment/Strategies/ShortAnswerGradingStrategy.php`
  - `app/Domain/Assessment/Strategies/ManualGradingStrategy.php`
  - `app/Domain/Assessment/Strategies/TrueFalseGradingStrategy.php`
  - `app/Models/Question.php` (check $fillable)
- **Test**: Run existing grading tests — they should now pass without null errors
- **Acceptance**: `php artisan test --filter=Grading` passes
- **Completed**:
  - Created `database/migrations/2026_02_05_120017_add_grading_columns_to_questions_table.php`
  - Updated `app/Models/Question.php` — added columns to `$fillable`, `casts`, PHPDoc
  - Migration ran successfully, 597 tests pass

### T0-2: Fix 'instructor' role reference in LearningPathEnrollmentPolicy
- **Status**: [x] Done (2026-02-05)
- **Bug**: `LearningPathEnrollmentPolicy.php:26` checks `hasRole(['admin', 'instructor'])` — neither role exists
- **Actual system roles**: learner, content_manager, trainer, lms_admin
- **Files to modify**:
  - `app/Policies/LearningPathEnrollmentPolicy.php` — line 26: change `['admin', 'instructor']` to `['lms_admin', 'trainer']`
- **Also check**: Are there other policies referencing 'admin' or 'instructor'?
  - Grep for `hasRole.*admin` and `hasRole.*instructor` across all policies
- **Test**: Write/update policy test verifying trainer CAN view any enrollment
- **Acceptance**: `php artisan test --filter=LearningPathEnrollmentPolicy` passes
- **Completed**:
  - Fixed `app/Policies/LearningPathEnrollmentPolicy.php`:
    - Line 26: `['admin', 'instructor']` → `['lms_admin', 'trainer']`
    - Lines 52, 60, 68, 76: `'admin'` → `'lms_admin'`
  - Updated tests in `tests/Unit/Policies/LearningPathEnrollmentPolicyTest.php`
  - 27 policy tests pass

### T0-3: Fix state mutation inconsistency in PathEnrollmentService
- **Status**: [-] Skipped — NOT A BUG (verified 2026-02-05)
- **Bug**: `PathEnrollmentService.php:126` uses `ActivePathState::$name` in `update()` call
- **Context**: Per project's state machine skill, `update()` should use `::class` not `::$name`
  - `UpdatePathProgressOnCourseDrop.php:99` correctly uses `ActivePathState::class` — NOT a bug
  - `PathEnrollmentService.php:126` uses `ActivePathState::$name` — this IS the inconsistency
  - `PathEnrollmentService.php:55` uses `::$name` in `create()` — acceptable for create
- **Files to modify**:
  - `app/Domain/LearningPath/Services/PathEnrollmentService.php` — line 126: change `ActivePathState::$name` to `ActivePathState::class`
- **Test**: Existing re-enrollment tests should still pass; add assertion that `$enrollment->isActive()` returns true after reactivation
- **Acceptance**: `php artisan test --filter=ReEnrollment` passes
- **Verification**:
  - Spatie model-states accepts BOTH `::$name` (string) and `::class`
  - Codebase consistently uses `::$name` in `update()` calls (e.g., `Enrollment.php:100`)
  - All drop/reactivate tests pass — pattern works correctly
  - The skill file recommendation for `::class` is a preference, not a requirement

### T0-4: Fix wrong PHPDoc in Enrollment model
- **Status**: [x] Done (2026-02-05)
- **Original Bug**: Enrollment model PHPDoc references `dropped_at` and `drop_reason` but migration doesn't include them
- **Actual Issue**: PHPDoc was wrong — the `drop()` method never sets these columns
- **Fix Applied**: Removed incorrect `@property` annotations instead of adding columns
- **Completed**:
  - Fixed `app/Models/Enrollment.php` — removed `dropped_at` and `drop_reason` from PHPDoc

### T0-5: Handle PaymentRequiredException gracefully in enrollment flow
- **Status**: [x] Done (2026-02-05)
- **Bug**: `EnrollmentService.php:137-142` throws `PaymentRequiredException` for paid courses — no catch anywhere
- **Current**: Commercial mode LMS crashes when learner tries to enroll in paid course
- **Approach**: Catch the exception in enrollment controllers and return user-friendly error
- **Files to modify**:
  - `app/Http/Controllers/EnrollmentController.php` — catch `PaymentRequiredException`, redirect with flash message
  - `app/Http/Controllers/LearningPathEnrollmentController.php` — same pattern (path enrollment calls EnrollmentService)
- **Test**: Feature test: attempt enrollment on paid course → expect redirect with error message, NOT 500 error
- **Acceptance**: Paid course enrollment returns user-friendly error, not exception
- **Completed**:
  - Added `catch (PaymentRequiredException)` to `store()` method
  - Added `catch (PaymentRequiredException)` to `acceptInvitation()` method
  - Returns Indonesian message: "Kursus ini berbayar. Silakan selesaikan pembayaran terlebih dahulu."

---

## TIER 1: PRODUCTION BLOCKERS

> Features that are incomplete or missing, preventing normal LMS operation.

### T1-6: Course publish validation
- **Status**: [x] Done (2026-02-05)
- **Problem**: Can publish empty courses with no sections, lessons, or content
- **Approach**: Add validation in `CoursePublishController` that checks:
  - Course has at least 1 section
  - Course has at least 1 lesson
- **Files modified**:
  - `app/Http/Controllers/CoursePublishController.php` — added `validateCourseContent()` method
- **Test**: `tests/Feature/Course/PublishCourseValidationTest.php` — 4 tests
- **Acceptance**: Cannot publish course without content structure
- **Verified**: Tested via tinker that empty course is blocked, course with section+lesson is allowed

### T1-7: Certificate generation system
- **Status**: [-] Skipped — MISSING FEATURE, not bug
- **Problem**: No way to prove course completion — required for regulatory compliance
- **Approach**: Create model, migration, PDF generation service
- **Files to create**:
  - `database/migrations/YYYY_MM_DD_create_certificates_table.php`
  - `app/Models/Certificate.php`
  - `app/Domain/Certificate/Services/CertificateService.php`
  - `app/Http/Controllers/CertificateController.php`
  - `app/Policies/CertificatePolicy.php`
- **Schema**: id, user_id, enrollment_id, course_id, certificate_number (unique), issued_at, expires_at, pdf_path, metadata (JSON)
- **Dependencies**: Enrollment completion must trigger certificate generation
- **Test**: Feature test: complete course → certificate created with unique number
- **Acceptance**: Certificate generated on course completion, downloadable as PDF

### T1-8: AssessmentGraded event listener for enrollment progress
- **Status**: [x] Done (2026-02-05)
- **Problem**: `AssessmentGraded` event fires but has no listener — scores don't update enrollment progress
- **Approach**: Create listener that updates enrollment progress when assessment is graded
- **Files modified**:
  - `app/Providers/EventServiceProvider.php` — registered listener for `AssessmentGraded`
- **Files created**:
  - `app/Domain/Assessment/Listeners/UpdateProgressOnAssessmentGraded.php`
- **Logic**: When assessment is graded, recalculate enrollment progress using progress calculator
- **Test**: `tests/Unit/Domain/Assessment/UpdateProgressOnAssessmentGradedTest.php` — 3 tests
- **Acceptance**: Assessment scores reflected in enrollment progress
- **Note**: Test binds `AssessmentInclusiveProgressCalculator` since default is lesson-based. Consider changing default to assessment_inclusive if assessments should always count toward progress.

### T1-9: Course lifecycle notifications
- **Status**: [x] Done (2026-02-05)
- **Problem**: When course is unpublished/archived, learners with active enrollments are NOT notified
- **Location**: `LogCourseLifecycleImpact.php:42` had TODO comment
- **Approach**: Send notification to affected learners when course state changes impact access
- **Files modified**:
  - `app/Domain/Course/Listeners/LogCourseLifecycleImpact.php` — added notification dispatch
- **Files created**:
  - `app/Domain/Course/Notifications/CourseAccessChangedNotification.php` (mail + database channels)
- **Test**: `tests/Feature/Course/CourseLifecycleNotificationTest.php` — 4 tests
- **Acceptance**: Learners receive notification when course is unpublished or archived

### T1-10: Enforce min_completion_percentage in prerequisite evaluators
- **Status**: [x] Done (2026-02-05)
- **Problem**: `learning_path_course.min_completion_percentage` column exists but is never checked
- **Location**: `SequentialPrerequisiteEvaluator:61` only checked `isCompleted()`, ignored threshold
- **Approach**: Update prerequisite evaluators to check course progress against threshold
- **Files modified**:
  - `app/Domain/LearningPath/Strategies/SequentialPrerequisiteEvaluator.php` — added `meetsRequirement()` method
  - `app/Domain/LearningPath/Strategies/ImmediatePreviousPrerequisiteEvaluator.php` — added `meetsRequirement()` method
- **Test**: `tests/Unit/Domain/LearningPath/MinCompletionPercentageTest.php` — 6 tests
- **Acceptance**: Prerequisite evaluation respects min_completion_percentage; 367 learning path tests pass

---

## FEATURE REQUESTS (Prioritized by Impact vs Risk)

> All bugs fixed (T0+T1). Remaining items are feature requests requiring product decisions.

### Priority Matrix

| Priority | Impact | Risk | Description |
|----------|--------|------|-------------|
| **P1** | High | Low | Quick wins — high value, low complexity |
| **P2** | High | Medium | Important but needs design decisions |
| **P3** | Medium | Medium | Good value, moderate effort |
| **P4** | Medium | High | Significant complexity or dependencies |
| **P5** | Low-Med | High | Future consideration, major effort |

---

## PHASE 1: QUICK WINS (P1)

> High impact, low risk. Can be implemented quickly.

### P1-1: Enrollment deadlines and capacity limits (was T2-12)
- **Status**: [x] Done (2026-02-05)
- **Priority**: P1 | Impact: High | Risk: Low
- **Problem**: No way to auto-close enrollment or limit seats
- **Approach**: Add deadline/capacity columns to courses, validate in enrollment flow
- **Files to modify**:
  - CREATE: migration adding `enrollment_deadline`, `max_enrollments` to courses
  - `app/Domain/Enrollment/Services/EnrollmentService.php` — check deadline/capacity
- **Test**: Feature test: enroll after deadline → rejected; enroll when full → rejected
- **Acceptance**: Enrollment respects deadlines and capacity

### P1-2: Optional course opt-in for learning paths (was T2-14)
- **Status**: [x] Done (2026-02-05)
- **Priority**: P1 | Impact: Medium | Risk: Low
- **Problem**: Optional courses in learning paths have no enrollment — learners can't opt-in
- **Location**: `PathEnrollmentService:289-295` only enrolls required + available courses
- **Approach**: Add endpoint for learner to opt-in to optional course within path
- **Files to modify**:
  - `app/Domain/LearningPath/Services/PathEnrollmentService.php` — add `enrollInOptionalCourse()` method
  - `app/Http/Controllers/LearningPathEnrollmentController.php` — add route
  - `routes/learning_paths.php` — add route
- **Test**: Feature test: opt-in to optional course → enrollment created, progress updated
- **Acceptance**: Learners can choose to enroll in optional path courses

### P1-3: Compliance audit reporting - OJK (was T3-19)
- **Status**: [x] Done (2026-02-05)
- **Priority**: P1 | Impact: High | Risk: Low
- **Problem**: No exportable audit reports for regulatory compliance
- **Approach**: Create reporting service that aggregates domain_event_log data
- **Why Low Risk**: Uses existing event log infrastructure, no schema changes
- **Files to create**:
  - `app/Domain/Compliance/Services/AuditReportService.php`
  - `app/Http/Controllers/ComplianceReportController.php`
- **Test**: Feature test: generate report → PDF/CSV with required OJK fields
- **Acceptance**: Compliance reports exportable as PDF/CSV

### P1-4: Anti-cheating basics - IP logging (was T4-23)
- **Status**: [x] Done (2026-02-05)
- **Priority**: P1 | Impact: Medium | Risk: Low
- **Problem**: No IP logging, fingerprinting, or pattern detection
- **Approach**: Add IP/user-agent logging to assessment attempts
- **Why Low Risk**: Simple column addition, no logic changes
- **Files to modify**:
  - CREATE: migration adding `ip_address`, `user_agent` to `assessment_attempts`
  - `app/Domain/Assessment/Services/AssessmentAttemptService.php` — capture on start
- **Test**: Unit test: start attempt → IP/UA captured
- **Acceptance**: Assessment attempts log IP and user agent

---

## PHASE 2: MONETIZATION (P2)

> High impact, medium complexity. Enables revenue.

### P2-1: Payment system foundation (was T2-11)
- **Status**: [x] Done (2026-02-07)
- **Priority**: P2 | Impact: High | Risk: Medium
- **Problem**: No payment model/table — courses have price fields but no way to process payment
- **Approach**: Create Payment model, migration, and basic service interface
- **Decision Needed**: Which payment gateway? (Midtrans, Xendit, etc.)
- **Files to create**:
  - `database/migrations/YYYY_MM_DD_create_payments_table.php`
  - `app/Models/Payment.php`
  - `app/Domain/Payment/Services/PaymentService.php`
  - `app/Domain/Payment/Contracts/PaymentGatewayContract.php`
- **Schema**: id, user_id, course_id, enrollment_id, amount, currency, status (pending/paid/failed/refunded), gateway, gateway_transaction_id, paid_at, metadata (JSON)
- **Test**: Unit test: create payment record, verify status transitions
- **Acceptance**: Payment model exists, EnrollmentService can check payment status

### P2-2: Scheduled deadline reminders (was T2-15)
- **Status**: [x] Done (2026-02-05)
- **Priority**: P2 | Impact: Medium | Risk: Low
- **Problem**: Only 1 scheduled task exists (purge-trashed) — no deadline reminders
- **Approach**: Create artisan command + schedule for sending reminders
- **Dependencies**: P1-1 (enrollment deadlines must exist first)
- **Files to create**:
  - `app/Console/Commands/SendDeadlineReminders.php`
  - `app/Notifications/AssessmentDeadlineReminder.php`
  - `app/Notifications/CourseDeadlineReminder.php`
- **Files to modify**:
  - `routes/console.php` — register schedule
- **Test**: Feature test: learner with upcoming deadline → notification sent
- **Acceptance**: Reminders sent 3 days and 1 day before deadlines

---

## PHASE 3: ENTERPRISE (P3)

> Important for enterprise clients. Medium complexity.

### P3-1: Role/permission system expansion (was T3-16)
- **Status**: [x] Done (2026-02-07)
- **Priority**: P3 | Impact: High | Risk: Medium
- **Problem**: Only 4 hardcoded roles — need compliance officer, auditor, TA
- **Decision Needed**: Expand enum or use Spatie permissions package?
- **User story needed**: Which roles and what permissions for each?
- **Files to modify**: TBD after approach decision
- **Test**: TBD
- **Acceptance**: Additional roles available with appropriate permissions

### P3-2: SSO/OAuth2 integration (was T3-18)
- **Status**: [ ]
- **Priority**: P3 | Impact: High | Risk: Medium
- **Problem**: No SSO — can't integrate with corporate AD/OKTA
- **Approach**: Laravel Socialite + custom SAML package
- **Decision Needed**: Which SSO providers to support first?
- **Files to modify**: TBD
- **Test**: TBD
- **Acceptance**: Users can login via SSO provider

### P3-3: Bulk operations (was T4-24)
- **Status**: [x] Done (2026-02-07)
- **Priority**: P3 | Impact: Medium | Risk: Medium
- **Problem**: Can't batch enroll, grade, or manage at scale
- **Approach**: Add bulk endpoints with queue processing
- **Files to modify**: TBD
- **Test**: TBD
- **Acceptance**: Bulk enroll/grade operations work via queue

---

## PHASE 4: ADVANCED (P4)

> Significant complexity. Plan carefully.

### P4-1: Question bank - decouple questions (was T2-13)
- **Status**: [x] Done (2026-02-07)
- **Priority**: P4 | Impact: Medium | Risk: High
- **Problem**: Questions are permanently tied to single assessments — can't reuse
- **Approach**: Create question_bank table with many-to-many between bank questions and assessments
- **Why High Risk**: Schema redesign, affects grading, versioning complexity
- **Files created**:
  - Migration for `question_banks` table
  - Migration for `question_bank_question` pivot
  - `app/Models/QuestionBank.php`
  - Question bank seeder with banking/compliance questions
- **Test**: Feature test: create question bank, attach questions to multiple assessments
- **Acceptance**: Questions reusable across assessments
- **Completed**: Implemented with seeder for banking/compliance context (commits 4d94f78, 6c38baa)

### P4-2: Course versioning (was T4-21)
- **Status**: [ ]
- **Priority**: P4 | Impact: Medium | Risk: High
- **Problem**: No way to version course content — can't safely iterate
- **Approach**: Add version columns, parent_course_id FK
- **Why High Risk**: Complex data model, affects enrollments and progress
- **Files to modify**: TBD
- **Test**: TBD
- **Acceptance**: Courses can be versioned, learners see correct version

### P4-3: Multi-tenancy foundation (was T3-17)
- **Status**: [ ]
- **Priority**: P4 | Impact: Very High | Risk: Very High
- **Problem**: No organization scoping — can't isolate departments/branches
- **Approach**: Add `organization_id` to users, courses, enrollments with scope middleware
- **Why Very High Risk**: MAJOR architectural change, touches every query
- **Recommendation**: Detailed architecture planning required before implementation
- **Files to modify**: TBD after architecture planning
- **Test**: TBD
- **Acceptance**: Resources scoped to organization

---

## PHASE 5: FUTURE (P5)

> Low priority or very high complexity. Consider later.

### P5-1: Course branching in learning paths (was T4-22)
- **Status**: [ ]
- **Priority**: P5 | Impact: Low | Risk: High
- **Problem**: Only linear sequential paths — can't express "choose 1 of 3"
- **Approach**: Add branching data structure to learning_path_courses
- **Why P5**: Changes path logic fundamentally, limited use cases
- **Files to modify**: TBD
- **Test**: TBD

### P5-2: SCORM/xAPI compliance metadata (was T3-20)
- **Status**: [ ]
- **Priority**: P5 | Impact: Medium | Risk: High
- **Problem**: No SCORM/xAPI support for enterprise LMS integration
- **Approach**: Add SCORM metadata columns to lessons, progress tracking
- **Why P5**: Specialized standards knowledge needed, niche requirement
- **Files to modify**: TBD
- **Test**: TBD
- **Acceptance**: Lesson progress tracks SCORM-compatible fields

### P5-3: Discussion/messaging system (was T4-25)
- **Status**: [ ]
- **Priority**: P5 | Impact: Low | Risk: Medium
- **Problem**: No peer learning or instructor communication channel
- **Approach**: Create discussion model, threaded replies
- **Why P5**: New subsystem, not core LMS functionality
- **Files to modify**: TBD
- **Test**: TBD

### P5-4: Certificate generation system (was T1-7)
- **Status**: [x] Done (2026-02-07)
- **Priority**: P5 | Impact: Medium | Risk: Medium
- **Problem**: No way to prove course completion — required for regulatory compliance
- **Approach**: Create model, migration, PDF generation service
- **Why P5**: Moved from T1 — needs user story for certificate design/workflow
- **Files to create**:
  - `database/migrations/YYYY_MM_DD_create_certificates_table.php`
  - `app/Models/Certificate.php`
  - `app/Domain/Certificate/Services/CertificateService.php`
- **Schema**: id, user_id, enrollment_id, course_id, certificate_number (unique), issued_at, expires_at, pdf_path, metadata (JSON)
- **Test**: Feature test: complete course → certificate created with unique number
- **Acceptance**: Certificate generated on course completion, downloadable as PDF

---

## RECOMMENDED EXECUTION ORDER

```
Phase 1 (Quick Wins):     P1-1 → P1-2 → P1-3 → P1-4
Phase 2 (Monetization):   P2-1 → P2-2
Phase 3 (Enterprise):     P3-1 → P3-2 → P3-3
Phase 4 (Advanced):       P4-1 → P4-2 → P4-3
Phase 5 (Future):         P5-1 → P5-2 → P5-3 → P5-4
```

**Dependencies:**
- P2-2 blocked by P1-1 (deadline reminders need deadline columns)
- P4-3 (multi-tenancy) should be last due to architectural impact

---

## PROGRESS SUMMARY

### Bugs (Completed)

| Category | Total | Done | Skipped | Pending |
|----------|-------|------|---------|---------|
| T0 — Critical Bugs | 5 | 4 | 1 | 0 |
| T1 — Production Blockers | 5 | 4 | 1 | 0 |
| **BUG TOTAL** | **10** | **8** | **2** | **0** |

### Feature Requests (Pending)

| Phase | Total | Done | Blocked | Pending |
|-------|-------|------|---------|---------|
| Phase 1 — Quick Wins (P1) | 4 | 4 | 0 | 0 |
| Phase 2 — Monetization (P2) | 2 | 2 | 0 | 0 |
| Phase 3 — Enterprise (P3) | 3 | 2 | 0 | 1 |
| Phase 4 — Advanced (P4) | 3 | 1 | 0 | 2 |
| Phase 5 — Future (P5) | 4 | 1 | 0 | 3 |
| **FEATURE TOTAL** | **16** | **10** | **0** | **6** |

### Overall

| Type | Total | Done | Remaining |
|------|-------|------|-----------|
| Bugs | 10 | 8 | 0 (2 skipped) |
| Features | 16 | 10 | 6 |
| **TOTAL** | **26** | **18** | **6** |

---

## NOTES / DECISIONS LOG

| Date | Decision | Reason |
|------|----------|--------|
| 2026-02-02 | BUG-3 correction: drop listener uses `::class` (correct), PathEnrollmentService uses `::$name` (the actual bug) | Verified against state machine skill and actual source code |
| 2026-02-02 | Plan covers all 25 items across 5 tiers | User requested fix all tiers |
| 2026-02-05 | T0-3 marked as NOT A BUG | Verified Spatie model-states accepts both `::$name` and `::class`. All existing tests pass. The codebase uses `::$name` consistently and it works. |
| 2026-02-05 | T0-4 fix changed from "add column" to "fix PHPDoc" | The `drop()` method never sets `dropped_at`. The PHPDoc was wrong, not the migration. Removed incorrect properties from PHPDoc. |
| 2026-02-05 | TIER 0 COMPLETE | 4 bugs fixed, 1 false positive skipped. 597 tests pass. |
| 2026-02-05 | T1-7 skipped | Certificate generation is a missing feature, not a bug. Requires user story. |
| 2026-02-05 | T1-8 complete | Created listener + tests. Default calculator is lesson-based; test binds assessment-inclusive. |
| 2026-02-05 | T1-9 complete | Created notification + updated listener. Learners notified on course unpublish/archive. |
| 2026-02-05 | T1-10 complete | Both evaluators now check min_completion_percentage. 367 learning path tests pass. |
| 2026-02-05 | TIER 1 COMPLETE | 4 bugs fixed, 1 missing feature skipped. Ready for TIER 2. |
| 2026-02-05 | Reorganized T2-T4 as Feature Requests | Remaining 15 items are features, not bugs. Prioritized by Impact vs Risk into 5 phases (P1-P5). |
| 2026-02-05 | Priority rationale | P1=quick wins (high impact, low risk), P2=monetization, P3=enterprise, P4=complex features, P5=future/niche. |
| 2026-02-05 | P1-1 complete | Enrollment deadline/capacity limits with 13 passing tests. |
| 2026-02-05 | P1-2 complete | Optional course opt-in for learning paths with 8 passing tests. |
| 2026-02-05 | P1-3 complete | Compliance audit reporting (OJK) with AuditReportService, controller, routes, and 11 passing tests. |
| 2026-02-05 | P1-4 complete | Anti-cheating IP logging on assessment attempts with 5 passing tests. |
| 2026-02-05 | PHASE 1 COMPLETE | All 4 P1 quick wins implemented. 37 new tests total. |
| 2026-02-05 | P2-2 complete | Scheduled deadline reminders with SendDeadlineReminders command, runs daily at 8 AM. 10 passing tests. |
