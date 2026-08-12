---
id: B-015
title: Agent outbound webhooks (v1.1)
status: todo
priority: P2
area: agent-platform
phase: F
depends_on: [B-012, B-013]
---

# B-015 — Agent outbound webhooks (v1.1)

> **Deferred from Depth B v1.** Depth B ship tanpa webhook; agent poll via MCP tools dulu.

## Problem

Agent gateway (OpenClaw/Hermes) ingin push-event (enrollment completed, cert issued) tanpa polling.

## Goal

Signed outbound webhooks ke URL terdaftar per agent client.

## Scope (nanti)

- [ ] `agent_webhook_endpoints` (url, secret, events[], active)
- [ ] Dispatch on domain events subset: `UserEnrolled`, `EnrollmentCompleted`, certificate issued
- [ ] HMAC signature header + retry + dead letter
- [ ] Admin/CLI manage endpoints
- [ ] Tests: signature, retry, disable endpoint

## Out of scope v1.1

- Bidirectional ACP
- WhatsApp/channel adapters
- Full event bus SaaS

## Acceptance

1. On free course complete → webhook POST with signed payload received by fake endpoint.
2. Invalid secret verify fails on receiver docs sample.
3. Disabled endpoint no delivery.

## Refs

- Domain events listeners
- B-012 audit (delivery attempts boleh log di agent_action_logs atau tabel terpisah)
