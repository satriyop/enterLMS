---
id: D-000
title: Investigation era (legacy) — completed work summary
status: done
completed: 2026-02
---

# D-000 — Legacy investigation summary

Sumber asli: `.ai/investigation/` (sekarang **arsip / di-ignore**).

## Completed (era Feb 2026)

### Bugs

- Grading columns on questions
- LearningPathEnrollmentPolicy wrong roles
- Enrollment PHPDoc cleanup
- PaymentRequiredException handled gracefully
- Course publish content validation
- AssessmentGraded → progress listener
- Course lifecycle notifications
- min_completion_percentage on path prereqs

### Features

- Enrollment deadline + capacity
- Optional path course opt-in
- Compliance audit reports
- Assessment IP/UA logging
- Payment foundation (model/service/UI — **gateway still open as B-001**)
- Deadline reminder scheduler
- Role enum expansion
- Bulk operations (partial/verify via code)
- Question bank
- Certificates (later marked done)
- Free-flow hardening seeder + journey test (2026-08)

## Still open after that era → now in `tasks/backlog/`

SSO, multi-tenancy, versioning, branching, discussion, SCORM harden, LTI, etc.

**Do not update this file as live tracker.** Use capability-map + backlog.
