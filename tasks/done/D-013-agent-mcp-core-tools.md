---
id: D-013
title: Agent MCP core tools (catalog, enroll, progress)
status: done
completed: 2026-08-12
priority: P1
area: agent-platform
phase: F
depends_on: [D-012]
---

# D-013 — Agent MCP core tools

## Delivered

Free-flow MCP tools on `EnteraksiAgentServer` (`/mcp/enteraksi`), thin wrappers over Domain services:

| Tool | Ability |
|------|---------|
| `list-catalog` | `agent:catalog.read` |
| `get-course` | `agent:course.read` |
| `list-my-enrollments` | `agent:enrollment.read` |
| `get-enrollment` | `agent:enrollment.read` |
| `get-progress` | `agent:progress.read` |
| `enroll-course` | `agent:enrollment.write` (free path; rejects paid when payments on) |
| `mark-lesson-complete` | `agent:progress.write` (owner + active enrollment only) |

Plus existing `agent-ping`.

## Token

```bash
php artisan agent:token learner@example.com --free-flow
# Authorization: Bearer <token> → POST /mcp/enteraksi
```

Default without `--free-flow` remains **ping-only**.

## Tests

`tests/Feature/Agent/AgentMcpCoreToolsTest.php` — catalog, free-flow enroll+complete, paid reject, cross-user forbid, tools/list.

## Refs

- `app/Mcp/Tools/Agent/*`
- `EnrollmentService`, `ProgressTrackingService`
