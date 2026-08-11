# Enteraksi LMS — Consolidated Investigation Findings

> **Generated**: 2026-02-02
> **Agents Used**: 5 parallel deep explorations
> **Files Analyzed**: 100+ across models, services, controllers, migrations, tests, frontend

---

## EXECUTIVE SUMMARY

The Enteraksi LMS has a **solid architectural foundation** with clean DDD patterns, state machines, strategy patterns, and good test coverage. However, it has **critical gaps** that prevent production readiness for a banking-centric LMS:

1. **Payment system is a blocking gate with no actual implementation**
2. **Schema mismatches** — code references columns that don't exist in DB
3. **No SCORM/xAPI compliance** — required for enterprise LMS integration
4. **No certificate generation** — required for regulatory compliance
5. **Incomplete audit trail** — insufficient for OJK/ISO27001 requirements
6. **Role system too simplistic** — 4 hardcoded roles, no granular permissions
7. **No multi-tenancy** — can't serve multiple organizations/departments

---

## CRITICAL BUGS (Fix Immediately)

### BUG-1: Schema Mismatch — Code References Non-Existent Columns
- **Where**: `ShortAnswerGradingStrategy.php:45` references `$question->case_sensitive`
- **Where**: `ShortAnswerGradingStrategy.php:81` references `$question->correct_answer`
- **Where**: `ManualGradingStrategy.php:31` references `$question->grading_rubric`
- **Where**: `TrueFalseGradingStrategy.php:84` references `$question->correct_answer`
- **Impact**: These grading strategies will crash on production data
- **Fix**: Add missing columns to questions table migration

### BUG-2: Broken Role Check — 'instructor' Role Doesn't Exist
- **Where**: `LearningPathEnrollmentPolicy.php:26` — `$user->hasRole(['admin', 'instructor'])`
- **Impact**: 'instructor' is NOT a valid role. System roles are: learner, content_manager, trainer, lms_admin
- **Fix**: Change 'instructor' to 'trainer' or 'content_manager'

### BUG-3: State Mutation Bug in Drop Listener
- **Where**: `UpdatePathProgressOnCourseDrop.php:99` — Uses `ActivePathState::class` when updating state
- **Where**: `PathEnrollmentService.php:126` — Same pattern
- **Impact**: State casting may fail or corrupt data (Spatie model-states gotcha)
- **Fix**: Use `ActivePathState::$name` (string) not the class

### BUG-4: Enrollment `dropped_at` Missing from Schema
- **Where**: Enrollment model references `dropped_at` but migration doesn't include it
- **Impact**: Can't audit when enrollments were dropped
- **Note**: LearningPathEnrollment HAS `dropped_at` but Enrollment doesn't

### BUG-5: Payment Gate Blocks Without Payment System
- **Where**: `EnrollmentService.php:137-142` — throws `PaymentRequiredException`
- **Impact**: Commercial mode LMS is completely broken — no way to pay
- **TODO comment at line 138**: "Check for payment record when payment system is implemented"

---

## DOMAIN-BY-DOMAIN FINDINGS

### Domain 1: Course Management & Content Delivery

**Strengths**: Rich model with state machine, proper cascade deletes, RequiresEagerLoading trait

**Critical Issues**:
| Issue | Location | Impact |
|-------|----------|--------|
| Published courses immutable | `PublishedState.canEdit()=false` | Can't fix typos in live courses |
| Can publish empty courses | No validation before publish | Broken content for learners |
| No SCORM/xAPI support | Zero implementation | Can't integrate with enterprise systems |
| No course versioning | No version columns | Can't safely iterate on content |
| No lesson-level status | Lessons inherit course state | Can't mark individual lessons as draft |
| Conference integration incomplete | Stores URL only, no API | No Zoom/Google Meet automation |
| No content validation service | None | Empty sections, missing media not caught |
| Lesson progress SCORM-incompatible | No suspend_data, completion_status | Can't export/report to standards |

### Domain 2: Enrollment & Payment Lifecycle

**Strengths**: Pessimistic locking, race condition guards, proper state transitions

**Critical Issues**:
| Issue | Location | Impact |
|-------|----------|--------|
| NO payment model/table | None exists | Can't process payments |
| NO refund flow | None exists | Can't refund dropped paid courses |
| NO waitlist/capacity | None exists | Can't limit enrollments |
| NO enrollment deadlines | No deadline columns | Can't auto-close enrollment |
| Invitation expiry race condition | Between lock and service call | Expired invitation may be accepted |
| Soft-deleted enrollments may be double-counted | No explicit scope exclusion | Reporting integrity risk |
| Missing `dropped_at` in enrollments | Migration vs model mismatch | Can't audit drop timestamps |

### Domain 3: Assessment, Grading & Progress

**Strengths**: Strategy pattern for grading, server-side time limits, 70/30 progress calculator

**Critical Issues**:
| Issue | Location | Impact |
|-------|----------|--------|
| Schema mismatches (correct_answer, case_sensitive, grading_rubric) | Multiple strategies | Grading crashes on production |
| No question bank | Questions tied to assessments | Can't reuse questions across courses |
| Matching questions manual-only | ManualGradingStrategy | Should be auto-gradable |
| No certificate generation | Feature missing entirely | Can't prove course completion |
| Hardcoded 70/30 progress weighting | AssessmentInclusiveProgressCalculator | No per-course override |
| No anti-cheating beyond time limits | No IP logging, fingerprinting | Banking compliance risk |
| Partial submissions allowed | No configuration flag | Compliance training needs all answers |

### Domain 4: Learning Paths & Prerequisites

**Strengths**: Clean prerequisite strategy pattern, cross-domain event sync, good test coverage

**Critical Issues**:
| Issue | Location | Impact |
|-------|----------|--------|
| `min_completion_percentage` column unused | SequentialPrerequisiteEvaluator:61 | Grade thresholds not enforced |
| Optional courses not auto-enrolled | PathEnrollmentService:289-295 | Prerequisite chains break |
| N+1 queries in prerequisite evaluation | SequentialPrerequisiteEvaluator:57-59 | Performance degrades with large paths |
| No course branching support | Linear position only | Can't express "choose 1 of 3" |
| Prerequisite mode is path-level only | Single column | Can't mix sequential+open in same path |
| Async event sync creates stale data | Queue-based listeners | Learners see old progress |
| Payment gate incomplete | PricingAwarePrerequisiteEvaluator:29-35 | Always blocks paid courses |

### Domain 5: Auth, Policies, Events & Cross-Cutting

**Strengths**: 16 policies covering all models, domain events with audit logging, Fortify 2FA

**Critical Issues**:
| Issue | Location | Impact |
|-------|----------|--------|
| Only 4 hardcoded roles | Enum in migration | No compliance officer, auditor, TA roles |
| No SSO/OAuth | Zero implementation | Can't integrate with corporate AD/OKTA |
| No multi-tenancy | No organization scoping | Can't isolate departments/branches |
| 'instructor' role bug | LearningPathEnrollmentPolicy:26 | Dead code, never matches |
| No auth failure audit logging | Missing | OJK compliance gap |
| AssessmentGraded has no listeners | EventServiceProvider | Scores don't update enrollment progress |
| Course lifecycle notifications missing | TODO in LogCourseLifecycleImpact:32 | Learners lose access silently |
| Only 1 scheduled task | purge-trashed daily | No deadline reminders, no cert expiry |
| API has only health checks | routes/api.php | Can't integrate with HRIS/ERP |

---

## PRIORITIZED ACTION PLAN

### Tier 0: Critical Bugs (Fix Now)
1. Add missing schema columns (correct_answer, case_sensitive, grading_rubric to questions)
2. Fix 'instructor' → 'trainer' role name in LearningPathEnrollmentPolicy
3. Fix state mutation bug (use `::$name` not `::class`)
4. Add `dropped_at` column to enrollments table
5. Add PaymentRequiredException catch in EnrollmentController

### Tier 1: Production Blockers
6. Course publish validation (require sections, lessons, media)
7. Certificate generation system (model, table, PDF generation)
8. Assessment-to-enrollment progress integration (AssessmentGraded listener)
9. Lifecycle notifications (course unpublished/archived → notify learners)
10. Enforce `min_completion_percentage` in prerequisite evaluators

### Tier 2: Business Workflow Gaps
11. Payment system foundation (model, table, Midtrans integration)
12. Enrollment deadlines and capacity limits
13. Question bank (decouple questions from assessments)
14. Optional course opt-in UI for learning paths
15. Scheduled deadline reminders

### Tier 3: Compliance & Enterprise
16. Role/permission system expansion (compliance officer, auditor, TA)
17. Multi-tenancy foundation (organization scoping)
18. SSO/OAuth2 integration
19. Compliance audit reporting (OJK)
20. SCORM/xAPI compliance metadata

### Tier 4: Enhancement
21. Course versioning
22. Course branching in learning paths
23. Advanced anti-cheating (IP logging, proctoring hooks)
24. Bulk operations (enrollment, grading)
25. Discussion/messaging system

---

## TEST GAPS IDENTIFIED

| Area | What's Missing |
|------|---------------|
| **Course Publishing** | Can publish empty course (no validation test) |
| **Payment** | No payment verification tests at all |
| **Race Conditions** | Enrollment + invitation acceptance concurrent tests |
| **Concurrency** | 1000-learner simultaneous assessment attempt |
| **Certificate** | Feature doesn't exist, so no tests |
| **Prerequisite Thresholds** | `min_completion_percentage` not tested |
| **Cheating Detection** | No tests for suspicious patterns |
| **Cascade Effects** | Course archived → what happens to active enrollments? |
| **Soft Delete Scoping** | Do active queries exclude soft-deleted records? |
| **Bulk Operations** | No performance tests for large-scale operations |
