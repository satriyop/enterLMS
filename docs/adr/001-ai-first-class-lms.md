# EnterLMS is an AI-first LMS

EnterLMS is an AI-first LMS. A Learner takes a Course with a Tutor. An LMS Agent may operate the academy from outside. It is not a generic AI school and not a live-agent control plane.

The public catalog lists Open Courses only (v1: Pengenalan Agen AI, free, self-enroll). Restricted Courses (v1: Administrasi Agen OpenClaw) stay off that catalog. Completing an Open Course does not grant a Restricted Course. Learners on the Path Pengenalan → OpenClaw are granted Path enrollment by LMS Admin. OpenClaw stays locked until Pengenalan is complete. LMS Admin in this phase is only the founder.

A **Tutor** is a first-class participant: the teacher a Learner talks to about a Lesson on their Enrollment. Laravel owns the Conversation, Policy, Enrollment, and Focus. The Tutor's brain is a **locked-down Hermes job** (`enterlms-tutor`) — not a chat widget, not the LMS Agent, and not a live console in the Lesson. Overlay, WhatsApp, and Telegram are skins of that Tutor (ADR 009). Talking does not complete a Lesson and does not publish a Course. Understanding is still measured by Assessment. A **Grade Proposal** is not a grade until LMS Admin accepts it.

The LMS Agent is a different client on a different token (catalog, enroll, progress). Hermes may run either job. Those are two doors, not one process with every tool. A Lesson is not an OpenClaw desktop.

We rejected a live-agent lab in a Lesson, collapsing Tutor and LMS Agent onto one token, storing Conversation only in the runtime, letting the Tutor complete Lessons or publish Courses, a provider-strategy tree inside Laravel, opening OpenClaw to the public, and becoming a generic AI school. We also rejected “the Tutor must not be a runtime” — that mixed up the lab with the teacher. Who invokes Hermes, and which skins exist, is ADR 009.

## When this is true

A Learner on Pengenalan Agen AI opens the Lesson “Apa itu agen,” still reads the text, and asks: *“Ini bedanya apa dengan chatbot biasa?”* The Tutor answers from that Lesson and that Course, not how to operate a live OpenClaw. The exchange is a Conversation on their Enrollment; it is still there next week. Talking does not complete the Lesson.

A short-answer gets a Grade Proposal. LMS Admin accepts or rejects; until then it is not a grade. The certificate waits on real completion.

On Administrasi Agen OpenClaw, asking to operate a live runtime does not open a console. The Tutor can say practice is not in this academy.

An LMS Agent may still enroll via MCP. That is a client, not the Tutor. A chatbot with no Enrollment and no Focus, a Lesson that is an agent desktop, or one token that can teach *and* complete means this decision has not landed.

## Target architecture

Laravel is the academy. The Tutor job is a locked-down Hermes identity outside it. How skins reach that job is ADR 009 — not “the Learner never talks to Hermes,” which 009 retires.

```
 overlay | WhatsApp | Telegram
              │
              ▼
     enterlms-tutor (Hermes)
              │  MCP tutor.read + can-talk + commit-turn
              ▼
     Laravel academy (Enrollment, Lesson, Focus, Conversation)
              │
              │  never a Lesson
     live OpenClaw / agent desktop
```

LMS Agent remains a **different** Hermes job on a **free-flow** token. `ProgressTrackingService` is not called by talking. Do not grow Laravel into an agent framework. Grade Proposal stays in Assessment — not inside the Tutor session.

A preview Lesson has no Tutor. Dropped Enrollment: Conversation stays, no new turns. Completed Enrollment: may still talk; talking does not uncomplete. Reactivate with progress: same Conversation. Reactivate without: a new empty Conversation for that Lesson. Grounding is the Focus Lesson’s body plus enrolled Course outlines — not the bodies of later locked Lessons, not ungranted Restricted Courses. LMS Admin may read Conversations on Courses they run. LMS Admin talks only if enrolled — no author Conversation on a draft. Tutor replies in the language of the Learner’s turn, otherwise Bahasa Indonesia. A turn that never persisted is not in the Conversation. Delete the Lesson and its Conversation goes with it.

The Tutor is Lesson-scoped, not Lesson-page-only. Overlay, WhatsApp, and Telegram are skins. Not Assessment, not the dashboard. If the Learner can open the Lesson they can talk on the overlay; unpublish does not get a second gate. New turns ground in the Lesson as it is now; old turns are not rewritten.

A Grade Proposal is created for any answer that already requires LMS Admin (not MC/TF, not exact short-answer). The Learner sees waiting, not the proposed score. Reject leaves it ungraded; LMS Admin may grade by hand or request another Proposal. A new submit replaces the pending Proposal. Assessment owns the propose call — not `TutorRuntime`, not a Conversation. LMS Admin may read the Lesson Conversation while grading.

| | Tutor | LMS Agent |
|---|---|---|
| Who starts | Hermes `enterlms-tutor` (overlay via Laravel Gate) | The runtime itself |
| Token | runtime `tutor.read` + resolved Learner | free-flow / compliance |
| Tools | published Lesson, outline, Focus, persist | catalog, enroll, complete |
| Memory that counts | Conversation in Laravel | its own logs |
