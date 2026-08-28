---
id: B-017
title: Grade Proposal on non-deterministic answers
status: todo
priority: P0
area: assessment
depends_on: [B-016]
---

# B-017 — Grade Proposal

Read `CONTEXT.md` and [ADR 001](../../docs/adr/001-ai-first-class-lms.md) first.

## Problem

Answers that already return `requires_manual_grading` wait on LMS Admin with no suggestion. The vision has a Grade Proposal; the product does not.

## Goal

A single-shot propose in Assessment suggests a score and feedback. LMS Admin accepts or rejects. Until they accept, it is not a grade. Not a Conversation, not `TutorRuntime`.

## Scope

- [ ] Grade Proposal on every answer that already requires LMS Admin (short-answer with no acceptable list, essay, long_answer, file_upload, code, matching)
- [ ] MC/TF and exact short-answer unchanged — no Proposal
- [ ] Learner sees waiting, not the proposed score/feedback, until accept
- [ ] Reject: still ungraded; LMS Admin may grade by hand or request another Proposal
- [ ] New Learner submit replaces the pending Proposal (one per answer, not a queue)
- [ ] Assessment-owned propose call; may use a model; must not write Tutor turns
- [ ] LMS Admin may read the Lesson Conversation while grading (Policy from B-016)

## Out of scope

- Tutor completing a Lesson
- Hermes grader skill (later, same Assessment door)
- Changing progress calculation (ADR 008)
- Showing Proposals to the Learner before accept

## Acceptance

1. Short-answer with no acceptable-answer list gets a Grade Proposal instead of only “memerlukan penilaian manual.”
2. Essay / other `requires_manual_grading` types get a Proposal too. MC/TF do not.
3. Learner does not see proposed score until LMS Admin accepts. Reject keeps waiting, not a zero.
4. After reject, LMS Admin can enter a grade or request another Proposal. A new submit replaces the pending Proposal.
5. Certificate is not issued on a Proposal; it still requires a completed Enrollment.
6. Propose does not create or append a Conversation.
