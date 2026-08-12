---
id: B-014
title: Agent MCP compliance read tools
status: todo
priority: P2
area: agent-platform
phase: F
depends_on: [B-012]
---

# B-014 — Agent MCP compliance read tools

## Problem

Compliance officer / auditor agent (Hermes skill) butuh query audit & completion status tanpa login web UI.

## Goal

Read-only MCP tools di atas Compliance domain + `domain_event_log`.

## Scope

- [ ] `list-audit-events` — filter by date, event_name, aggregate, actor (`agent:compliance.read`)
- [ ] `get-user-training-status` — enrollments/completions/certs summary for a user (role-gated)
- [ ] `list-certificates` — issued certs filter (optional)
- [ ] Authorization: hanya role compliance/auditor/lms_admin **atau** token ability + policy check
- [ ] Tests: learner token denied; compliance token allowed; PII minimal in responses

## Out of scope

- Mutating compliance records
- Full OJK pack export redesign
- Write tools

## Acceptance

1. Token ability `agent:compliance.read` + role compliance bisa list audit events.
2. Learner-only token ditolak.
3. Responses tidak bocor password/token/secrets.

## Refs

- `AuditReportService`, `domain_event_log`
- B-012 abilities
