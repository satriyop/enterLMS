# Backlog

Item kerja yang **belum dikerjakan**. Satu file = satu item.

## Format nama file

```text
B-NNN-short-slug.md
```

Contoh: `B-001-payment-gateway.md`

## Template header

```yaml
---
id: B-001
title: Payment gateway integration
status: todo          # todo | in_progress | blocked
priority: P1          # P0 blocker | P1 high | P2 medium | P3 low
area: payments
phase: B              # from roadmap
depends_on: []
---
```

## Index (aktif)

| ID | Priority | Title | Phase |
|----|----------|-------|-------|
| [B-001](./B-001-payment-gateway.md) | P1 | Payment gateway (Midtrans/Xendit) + webhook | B |
| [B-002](./B-002-sso-oidc.md) | P1 | SSO / OIDC integration | C |
| [B-003](./B-003-multi-tenancy.md) | P1 | Multi-tenancy / organization scope | C |
| [B-004](./B-004-course-versioning.md) | P2 | Course content versioning | D |
| [B-005](./B-005-path-branching.md) | P3 | Learning path branching | D |
| [B-006](./B-006-discussion-forum.md) | P3 | Discussion / forum | D |
| [B-007](./B-007-scorm-harden.md) | P2 | Harden SCORM package + runtime | E |
| [B-008](./B-008-lti.md) | P3 | LTI 1.3 | E |
| [B-009](./B-009-mobile-api.md) | P2 | Mobile API (Sanctum) | E |
| [B-010](./B-010-conference-integration.md) | P3 | Live conference deep integration | E |
| [B-011](./B-011-role-permission-polish.md) | P2 | Role & permission matrix polish | C |
| ~~B-012~~ → [D-012](../done/D-012-agent-mcp-foundation.md) | — | Agent MCP foundation (**done**) | F |
| ~~B-013~~ → [D-013](../done/D-013-agent-mcp-core-tools.md) | — | Agent MCP core tools (**done**) | F |
| ~~B-014~~ → [D-014](../done/D-014-agent-mcp-compliance-tools.md) | — | Agent MCP compliance tools (**done**) | F |
| ~~B-015~~ → [D-015](../done/D-015-agent-webhooks-v1.1.md) | — | Agent outbound webhooks (**done**) | F |

Urutan ambil kerja: ikuti `tasks/roadmap/2026-lms-roadmap.md`, bukan urutan nomor semata.

**Agent platform (Depth B/F):** D-012 foundation · D-013 free-flow tools · D-014 compliance · **D-015 webhooks done**.

## Vet vs current code (2026-08-12)

Lihat **[backlog-vet-2026-08-12.md](../audit/backlog-vet-2026-08-12.md)** untuk validitas tiap item + pre-work.

| Next (pilih) | Jangan dulu tanpa trigger |
|--------------|---------------------------|
| free-flow polish *atau* B-001 payment (jika monetrize) | multi-tenancy / SSO |
| B-011 roles (enterprise demo) | **B-003** multi-tenancy (perlu ADR) |
| B-007 SCORM edge harden | B-002 SSO (perlu IdP) |

Sanctum + MCP foundation (**D-012**) sudah ada — B-009/B-013 tidak mulai dari nol auth.
