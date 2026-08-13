---
id: B-012
title: Agent MCP foundation (Sanctum + server skeleton + audit)
status: in_progress
priority: P1
area: agent-platform
phase: F
depends_on: []
---

# B-012 — Agent MCP foundation (Depth B)

## Problem

EnterLMS siap sebagai free internal LMS, tapi **belum first-class** untuk agent (Hermes / OpenClaw). Domain services ada, tapi tidak ada capability layer yang aman untuk AI client: no token API product, no MCP server produk, no agent audit.

## Goal (Depth B v1)

Fondasi resmi:

1. **Auth token** (Laravel Sanctum) untuk agent / automation client
2. **MCP web server** produk (`/mcp/enterlms`) via `laravel/mcp`
3. **Scopes/abilities** ketat (read vs limited write)
4. **Agent action audit log** (siapa token, tool apa, acting-as siapa, hasil)
5. **Tidak** embed agent runtime, WhatsApp channel, ACP/A2A multi-agent mesh

## Scope

- [ ] Install Sanctum; `HasApiTokens` pada `User`
- [ ] Promote `laravel/mcp` sebagai dependency produk (bukan hanya transitive via Boost)
- [ ] Publish `routes/ai.php`; register `EnterLmsAgentServer` web + `auth:sanctum` + throttle
- [ ] Ability constants (scopes agent)
- [ ] Artisan `agent:token` untuk issue/revoke personal access token ber-ability
- [ ] Tabel `agent_action_logs` + service logger
- [ ] Skeleton server + tool health/ping (read-only)
- [ ] Feature tests: unauthenticated ditolak; token valid list tools / call ping; audit row tercatat

## Out of scope (lihat backlog lain)

| Item | Backlog |
|------|---------|
| Core LMS tools (catalog, enroll, progress) | B-013 |
| Compliance read tools | B-014 |
| Outbound webhooks ke agent gateway | B-015 (v1.1) |
| Mobile REST full surface | B-009 (bisa reuse Sanctum) |
| OAuth/Passport full MCP OAuth 2.1 | nanti jika client wajib OAuth |
| Embed Hermes/OpenClaw di Laravel | **never** — client di luar |

## Design ringkas

```text
Hermes / OpenClaw
    |  Authorization: Bearer <sanctum-token>
    v
POST /mcp/enterlms   (Laravel MCP web server)
    |  auth:sanctum + throttle
    v
EnterLmsAgentServer
    |  tools thin → Domain/* services
    |  acting-as = token owner (User)
    v
AgentActionLogger → agent_action_logs
```

### Scopes (Sanctum abilities) — v1

| Ability | Arti |
|---------|------|
| `agent:ping` | Health / identity |
| `agent:catalog.read` | Browse published catalog |
| `agent:course.read` | Course/section/lesson metadata |
| `agent:enrollment.read` | My enrollments / status |
| `agent:enrollment.write` | Enroll free public course (limited) |
| `agent:progress.read` | Progress snapshot |
| `agent:progress.write` | Mark lesson complete (limited) |
| `agent:compliance.read` | Audit/compliance read (B-014) |

Token **hanya** boleh ability yang di-issue. Tool cek ability sebelum domain call.

### Prinsip

- Domain logic **tidak** di-duplikasi di tool — wrap service existing
- Write tool **sempit** (free enroll + mark complete); paid/payment/admin write dilarang di v1
- xAPI tetap channel telemetry terpisah; MCP = **aksi + query**
- UI admin token management boleh belakangan (CLI cukup v1)

## Acceptance

1. Unauthenticated request ke `/mcp/enterlms` → 401.
2. Token dengan ability `agent:ping` bisa call tool ping; response identity + app name.
3. Token tanpa ability relevan → tool error / not available (bukan silent success).
4. Setiap call tool sukses/gagal menulis `agent_action_logs` (tool, user_id, token_id, status, latency).
5. `php artisan agent:token {email} --ability=agent:ping` menghasilkan token usable.
6. Feature tests hijau.

## Refs

- Laravel MCP: Sanctum middleware pada `Mcp::web`
- `app/Domain/*` services
- `domain_event_log` (domain events) — beda dari `agent_action_logs` (MCP tool invocations)
- Roadmap Fase F — Agent platform
