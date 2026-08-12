---
id: B-013
title: Agent MCP core tools (catalog, enroll, progress)
status: todo
priority: P1
area: agent-platform
phase: F
depends_on: [B-012]
---

# B-013 — Agent MCP core tools

## Problem

Setelah fondasi MCP (B-012), agent masih belum bisa menjalankan learning flow. Hermes/OpenClaw butuh tools domain agar bisa: cek katalog → enroll free → lihat progress → tandai lesson selesai.

## Goal

Surface MCP **read + limited write** untuk free learning flow, wrapping Domain services existing.

## Scope

### Read tools

| Tool | Ability | Domain |
|------|---------|--------|
| `list-catalog` | `agent:catalog.read` | Published free/public courses (filter + paginate) |
| `get-course` | `agent:course.read` | Course + sections/lessons summary |
| `list-my-enrollments` | `agent:enrollment.read` | Enrollments token owner |
| `get-enrollment` | `agent:enrollment.read` | One enrollment + progress % |
| `get-progress` | `agent:progress.read` | Lesson progress for enrollment |

### Write tools (limited)

| Tool | Ability | Domain | Constraint |
|------|---------|--------|------------|
| `enroll-course` | `agent:enrollment.write` | `EnrollmentService` | Free + published + public/self-enroll only; no paid |
| `mark-lesson-complete` | `agent:progress.write` | `ProgressTrackingService` | Must own active enrollment; no skip-locked content rules violation |

### Cross-cutting

- [ ] Setiap tool: validate input, check ability, call service, `AgentActionLogger`, `Response::structured`
- [ ] Errors jelas (Bahasa + machine-readable code)
- [ ] Annotations: read tools `#[IsReadOnly]`, writes `#[IsIdempotent]` where true
- [ ] Feature tests per tool (auth, ability, happy, forbidden, validation)

## Out of scope

- Paid enroll / payment create
- Admin/CM content write
- Grade attempt submit (assessment agent tools → later)
- Path enroll tools (bisa follow-up)
- Compliance tools → B-014
- Webhooks → B-015

## Acceptance

1. Agent dengan scopes penuh free-flow: list catalog → get course → enroll free → get progress → mark lesson complete.
2. Paid course `enroll-course` ditolak dengan error jelas.
3. User A token tidak bisa mark progress enrollment User B.
4. Audit log mencatat setiap tool call.
5. Domain events existing (enroll/complete) tetap fire seperti UI path.

## Refs

- B-012 foundation
- `EnrollmentService`, `ProgressTrackingService`
- Free-flow journey tests (mirror behavior)
