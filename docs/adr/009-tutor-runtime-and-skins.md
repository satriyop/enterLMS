# Tutor runtime and skins

ADR 001 still defines the product: an AI-first LMS, Open vs Restricted, Hermes jobs (Tutor vs LMS Agent; Author Agent is ADR 015), Laravel as academy, Conversation as the transcript, no live console in a Lesson. It said the Learner never talks to Hermes, the Tutor sits on the Lesson page only, and Laravel invokes every turn. That was a chat-widget rail. It does not survive WhatsApp and Telegram, and it fought “Tutor is first-class.”

The Tutor is one lecturer for that Learner. Overlay, WhatsApp, and Telegram are skins of the same Tutor, not a second teacher. Hermes `enterlms-tutor` (one profile, one gateway: API server plus later messaging adapters) **runs the turn**. Laravel **is the academy**: Enrollment, Lesson body, Focus, Conversation, progress, Assessment. Official Hermes docs: HTTP clients use the API server, not a custom plugin; WA/TG are platform adapters. We do not write those stacks in PHP, and we do not share this job’s WhatsApp or Telegram with another Hermes job.

Conversation stays **one Lesson on one Enrollment**. Each skin has a **Focus** (which Conversation new turns write to). Overlay Focus is the Lesson URL. Messaging Focus is stored in Laravel, starts from the last overlay Lesson if still allowed (else a list / deep link), and moves only when the Learner asks and Laravel accepts. Outline-level talk does not move Focus. The lecturer may cover that Learner’s Enrollments; locked and ungranted Restricted bodies stay outline-only.

The overlay never holds the Hermes API key: browser → Laravel Gate → API server. Messaging has no cookie: Hermes calls MCP with **one runtime Bearer**, `resolve`s phone/Telegram → User, and every tool carries that `user_id`. `get-published-lesson` must check Enrollment, not only published. Laravel `ConversationService` is the only writer. A reply is not sent on WhatsApp/Telegram until both turns are recorded.

We rejected: Laravel as the WhatsApp/Telegram client; Learner talks to Hermes *and* `completeTurn` calls Hermes again (recursion); Conversation only in Hermes; one Learner-wide transcript; auto-changing Focus when they mention another Lesson; per-Learner Sanctum tokens in Hermes home; trusting the model’s `course_id`; a homemade sidecar that boots Laravel; a custom Hermes HTTP plugin; sharing this job’s WhatsApp or Telegram with the LMS Agent job; `tutor.read` bundled with free-flow.

Diagrams and end-to-end flows: `docs/design/tutor-runtime-and-skins.md`.
