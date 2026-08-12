---
id: D-015
title: Agent outbound webhooks (v1.1)
status: done
completed: 2026-08-12
priority: P2
area: agent-platform
phase: F
depends_on: [D-012, D-013]
---

# D-015 — Outbound webhooks for agents

## In plain language

Enteraksi **memberi tahu** gateway agent (Hermes/OpenClaw) lewat HTTP POST saat kejadian penting, tanpa agent harus terus bertanya.

## Events

| Key | When |
|-----|------|
| `enrollment.created` | User enroll |
| `enrollment.completed` | Course enrollment completed |
| `certificate.issued` | New certificate created |

## Security

HMAC-SHA256 over raw JSON body. Header: `X-Enteraksi-Signature: sha256=<hex>`  
Also: `X-Enteraksi-Event`, `X-Enteraksi-Delivery`.

## CLI

```bash
php artisan agent:webhook register \
  --name=openclaw \
  --url=https://your-gateway.example/hooks/enteraksi \
  --secret=your-shared-secret \
  --events=enrollment.completed,certificate.issued

php artisan agent:webhook list
php artisan agent:webhook disable --id=1
```

## Code

- Tables: `agent_webhook_endpoints`, `agent_webhook_deliveries`
- `AgentWebhookDispatcher` + queued `DispatchAgentWebhooks`
- `CertificateIssued` domain event
- Tests: `tests/Feature/Agent/AgentWebhookTest.php`
