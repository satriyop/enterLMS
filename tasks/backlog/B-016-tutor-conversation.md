---
id: B-016
title: Conversation + Tutor on a Lesson
status: todo
priority: P0
area: tutor
depends_on: []
---

# B-016 — Conversation + Tutor on a Lesson

Read `CONTEXT.md` and [ADR 001](../../docs/adr/001-ai-first-class-lms.md) first, including **Target architecture**.

## Problem

A Learner takes a Course by consuming Lessons. There is no Tutor. The vision sentence is not true in the product.

## Goal

A Learner on an Enrollment can talk to a Tutor about the Lesson they are in. The talk is a Conversation Laravel owns. The brain is `TutorRuntime::completeTurn` — first implementation may call a model with Lesson text; the locked target is a Hermes tutor skill on that same method.

## Scope

- [ ] Conversation belongs to one Enrollment and one Lesson; this academy is the source of truth
- [ ] Only the Learner on that Enrollment (and LMS Admin) can read it
- [ ] `TutorRuntime::completeTurn` — one method, process boundary, not a provider strategy
- [ ] Grounded in that Course's published content (stuff Lesson text, or `tutor.read` tools)
- [ ] Talking does not complete the Lesson and does not change progress
- [ ] The Conversation is still there next week
- [ ] No seventh Lesson form, no live console in the Lesson, no free-flow token on the Tutor

## Out of scope

- Grade Proposal (B-017)
- Wiring Hermes as the production runtime (same interface; may ship after the first implementation)
- LMS Agent tools (enroll, complete) on the Tutor
- Course authoring by the Tutor
- Provider-strategy tree (OpenAI vs Anthropic vs xAI)

## Acceptance

1. Enrolled Learner on Pengenalan Agen AI, Lesson “Apa itu agen”, asks *“Ini bedanya apa dengan chatbot biasa?”* and gets an answer from that Lesson/Course, not a procedure for operating OpenClaw.
2. Same Learner returns later; the Conversation is still on that Enrollment + Lesson. A model call behind `TutorRuntime::completeTurn` is enough for this slice.
3. Completing the Lesson still requires the existing progress rules. A Conversation alone leaves `is_completed` false. Talking on a completed Enrollment does not uncomplete it.
4. Preview without Enrollment: no Tutor. Another Learner: 403. Dropped Enrollment: can read, cannot add turns. LMS Admin can read.
5. Reactivate without preserving progress: new empty Conversation. Reactivate with progress: same Conversation.
6. Asking something only in a later Lesson does not dump that Lesson’s body; “itu di pelajaran berikutnya” is a valid answer.
7. On a Restricted OpenClaw Lesson, asking to operate a live runtime does not open a console; the Tutor can say practice is not in this academy.
8. The Tutor path does not use the LMS Agent free-flow token.
9. LMS Admin not enrolled: no Tutor on a draft Lesson (403). They may still read Learners’ Conversations.
10. Runtime failure: no new turns saved; Lesson still opens; retry is a new request.
11. Delete the Lesson: its Conversation is gone.
12. Tutor replies in the language of the Learner’s turn; if unclear, Bahasa Indonesia.
13. Tutor is on the Lesson page only (not Assessment, not dashboard).
14. If the Learner can view the Lesson, they can talk (no extra unpublish gate).
15. After the Lesson text is edited, new turns use the current body; old turns stay as written.
