# General Requirement

## Scope authority

`CONTEXT.md` at the repo root owns the domain language; `docs/adr/` owns the decisions.
This file carries only build requirements that cut across every feature.

Where this file and `CONTEXT.md` disagree, `CONTEXT.md` wins and this file is the bug.

## Overview of the application

- EnterLMS is an academy for the people who run and build Satriyo's AI product family
  (Enteraksi first). It is not a generic AI school and not a control plane for live
  agents (ADR 004).
- Two roles are modelled: **Learner** and **LMS Admin**. Tenant Admin, Tenant Owner and
  Operator are Enteraksi roles, named so we can talk about the people; ADR 005 phases
  them in.
- The public catalog lists **Open Courses** only — a Learner may self-enroll.
  **Restricted Courses** and **Learning Paths** are granted by LMS Admin.
- Agent runtimes (OpenClaw, Hermes) are Course subjects, never systems operated from
  inside this academy.

## Build requirements

- Primary language is Bahasa Indonesia. Seed data uses Indonesian names and context.
- Responsive UI is mandatory.
- Every feature ships with tests. See the test enforcement rules in `CLAUDE.md`.
- Input validated and sanitised. Authorization is enforced by policy, never by hiding UI.
- The design conforms to the Tenang hybrid: semantic tokens and editorial type, no stock
  Tailwind hues (ADR 007). The gate is `tests/Feature/Design/TenangConformanceTest.php`.
- Lesson forms are text, video, audio, document, YouTube, and conference.

## Out of scope

Frozen or deleted. Do not build against these, and do not restore them without an ADR:

- **Banking / OJK / APU-PPT** compliance domain — frozen (ADR 004)
- **Payment, SCORM, Question Bank** — deleted with the frozen scope, not deprecated
  (ADR 007). A priced Course has no self-serve path; LMS Admin grants Enrollment.
- **LTI, xAPI, HRIS/ERP integration, MOOC import** — never built, not in this phase
- **Unified Enteraksi login** — later. Public Learners register here.
- **Tenant-facing Restricted Courses** — handover for Tenant Admins is not in this phase.

## Key modules

1. User Management
2. Course Management
3. Content Delivery
4. Assessment & Grading
5. Progress Tracking & Reporting
6. Certificate Management
7. Enrollment
8. Communication (notifications; forums and messaging are aspirational)
