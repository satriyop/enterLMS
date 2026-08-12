---
id: D-014
title: Agent MCP compliance read tools
status: done
completed: 2026-08-12
priority: P2
area: agent-platform
phase: F
depends_on: [D-012]
---

# D-014 — Agent MCP compliance read tools

## Delivered

| Tool | Auth |
|------|------|
| `list-audit-events` | `agent:compliance.read` + compliance role |
| `get-user-training-status` | same |
| `list-certificates` | same |

Roles: `compliance_officer`, `auditor`, `lms_admin` (`User::canViewCompliance()`).

Metadata strips password/token/secret keys. No password fields in responses.

## Token example

```bash
php artisan agent:token compliance@example.com --ability=agent:compliance.read --ability=agent:ping
```

## Tests

`tests/Feature/Agent/AgentMcpComplianceToolsTest.php`
