# Work toward ADR 001

The product is already an LMS. The vision is not true until a Learner takes a Course with a Tutor.

This is the order of work. It is not a six-phase programme. Payment, SSO, multi-tenancy, SCORM, and LTI are not how this vision is achieved.

Read `CONTEXT.md` and `docs/adr/001-ai-first-class-lms.md` first.

## Already true

- Open Course / Restricted Course / Learning Path, Enrollment, Lesson progress, Assessment, Certificate
- LMS Agent via MCP (catalog, enroll, progress, audit) — a client, not a Tutor

## Makes the vision true

1. **Conversation + Tutor on a Lesson** — a Learner on an Enrollment can talk to a Tutor about that Lesson. Grounded only in the Course's published content. The Conversation is a domain record (policy, audit). Talking does not complete the Lesson and does not publish a Course.
2. **Grade Proposal** — on answers the deterministic strategies cannot grade, a model may suggest a score and feedback. It is not a grade until LMS Admin accepts it.

Stop after those two unless a new ADR says otherwise. Do not add a provider-strategy tree, a seventh Lesson form, or a live agent console in the Lesson. The Tutor runtime (ADR 001) is invoked by Laravel; it is not embedded in the Lesson.

## Not this vision

The previous multi-phase roadmap is retired. Those backlog items may still exist as files; they are not the path to ADR 001.

| Leave parked | Why |
|--------------|-----|
| Payment gateway, commercial enroll | Out of context in `CONTEXT.md` |
| SSO, multi-tenancy | Not required for a Tutor; local registration is ADR 005 |
| SCORM, LTI, course versioning, forums | Not what makes a Tutor real |
| Embed a live OpenClaw/Hermes *console* in a Lesson | Rejected in ADR 001 — the Tutor runtime is outside, invoked by Laravel |

## Execution

1. [B-016](../backlog/B-016-tutor-conversation.md) — Conversation + Tutor on a Lesson
2. [B-017](../backlog/B-017-grade-proposal.md) — Grade Proposal

When a slice is done, `git mv` it to `tasks/done/` and update `tasks/audit/capability-map.md`. Old items are in `tasks/parked/`, not in this queue.
