# Parked

Work that is **not** the path to [ADR 001](../../docs/adr/001-ai-first-class-lms.md). Files stay so we do not rediscover them as “next.” Do not take items from here until `CONTEXT.md` or an ADR says this work is in scope.

`tasks/backlog/` is only the queue for the vision.

| ID | Title | Why parked |
|----|-------|------------|
| [B-001](./B-001-payment-gateway.md) | Payment gateway | Out of context — priced Course has no self-serve path |
| [B-002](./B-002-sso-oidc.md) | SSO / OIDC | Not required for a Tutor; local registration is ADR 005 |
| [B-003](./B-003-multi-tenancy.md) | Multi-tenancy | Same as B-002; also not required for a Tutor |
| [B-004](./B-004-course-versioning.md) | Course versioning | Does not make a Tutor real |
| [B-005](./B-005-path-branching.md) | Path branching | v1 Path is one sequence; ADR 006 still holds |
| [B-006](./B-006-discussion-forum.md) | Discussion / forum | A Tutor is not a forum |
| [B-007](./B-007-scorm-harden.md) | SCORM harden | Not this academy (`CONTEXT.md`) |
| [B-008](./B-008-lti.md) | LTI 1.3 | Not this academy (`CONTEXT.md`) |
| [B-009](./B-009-mobile-api.md) | Mobile API | Not the vision; Sanctum already serves the LMS Agent |
| [B-010](./B-010-conference-integration.md) | Conference deep integration | Lesson form already exists as a URL; not a Tutor |
| [B-011](./B-011-role-permission-polish.md) | Role matrix polish | Written for seven roles; ADR 007 collapsed to Learner and LMS Admin |

To unpark: move the file back to `tasks/backlog/` and say which ADR or `CONTEXT.md` change made it in scope. Do not unpark B-007 / B-008 without un-freezing that language. Do not unpark B-011 without restoring staff roles in an ADR.
