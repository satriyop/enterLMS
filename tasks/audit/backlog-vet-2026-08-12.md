# Backlog vet vs current code

> **Date:** 2026-08-12  
> **Basis:** `main` after stabilize + audit close pack (`195c6d2`, `f86c1ad`, …)  
> **Companion:** [tech-debt-architecture-2026-08-12.md](./tech-debt-architecture-2026-08-12.md)

## Verdict ringkas

| Kategori | Item |
|----------|------|
| **Valid, next recommended** | **B-013** (agent tools) *atau* free-flow polish (no B-ID) |
| **Valid, but do not start yet** | B-001, B-002, B-003, B-011 (need product trigger) |
| **Valid, re-scope slightly** | B-007, B-009 |
| **Valid, later** | B-004, B-005, B-006, B-008, B-010, B-014, B-015 |
| **Obsolete / wrong as written** | None fully obsolete; several **problem statements stale** |
| **Already done** | B-012 → [D-012](../done/D-012-agent-mcp-foundation.md) |

**Tidak ada pre-blocker teknis besar** sebelum B-013.  
**Ada product gates** sebelum B-001 (payment on) dan B-003 (tenancy).

---

## Per item

### B-001 Payment gateway — **VALID**, not ready to start casually

| | |
|--|--|
| **Code now** | `PaymentService` + `PaymentGatewayContract` (no impl); `lms.payment.enabled=false`; `Course::isPaid()` needs commercial + flag; HTTP 404 via `EnsurePaymentsEnabled` |
| **Stale in file** | “Kursus berbayar di-block dengan pesan” — **salah** saat payment off: course `is_paid` di DB diperlakukan **gratis** |
| **Pre-work** | Decide Midtrans **or** Xendit; env keys; keep free-flow tests green when flag false |
| **Depends** | None hard; **product decision** “kita monetrize?” |
| **Prep before start** | Flip flag only after gateway + webhook tests; document commercial mode checklist |

### B-002 SSO/OIDC — **VALID**

| | |
|--|--|
| **Code now** | Fortify session only |
| **Pre-work** | None from recent debt |
| **Depends** | Product: provider bank (Azure AD / Okta / …) |
| **Note** | Can parallel B-011 later for role mapping |

### B-003 Multi-tenancy — **VALID**, **do not start** without ADR

| | |
|--|--|
| **Code now** | Global data, no `organization_id` |
| **Pre-work** | Architecture decision (single DB + org_id vs multi-DB) — mandatory |
| **Risk** | Touches almost all queries/policies |
| **Prep** | Freeze free-flow + agent if possible before this |

### B-004 Course versioning — **VALID**

| | |
|--|--|
| **Code now** | No version field; publish edits live content |
| **Pre-work** | None; large design |
| **Depends** | Free-flow stable (yes) |

### B-005 Path branching — **VALID**, low priority

| | |
|--|--|
| **Code now** | Linear sequential + evaluators |
| **Pre-work** | None |

### B-006 Discussion forum — **VALID**, greenfield

| | |
|--|--|
| **Code now** | Not present |
| **Pre-work** | None |

### B-007 SCORM harden — **VALID**, **narrow scope**

| | |
|--|--|
| **Code now** | Upload/player/runtime + **path jail** (`resolveSafePackagePath`) + tests |
| **Stale** | Treat as “foundation missing” — foundation **exists** |
| **Still needed** | Invalid package UX, multi-SCO, resume edge cases, completion → lesson progress edge packs |
| **Pre-work** | None blocked |

### B-008 LTI 1.3 — **VALID**, later

| | |
|--|--|
| **Code now** | Missing |
| **Pre-work** | None |

### B-009 Mobile API — **VALID**, **re-scope**

| | |
|--|--|
| **Code now** | Sanctum installed; xAPI `auth:sanctum`; MCP Bearer tokens; still no REST mobile surface for enroll/progress |
| **Stale** | “No Sanctum” is **false** |
| **Overlap** | B-013 tools ≈ agent “API”; B-009 = REST for native apps |
| **Pre-work** | Prefer B-013 first if agent priority — reuse same Domain services for REST later |
| **depends_on suggestion** | Soft-depends D-012 (Sanctum ready) |

### B-010 Conference deep integration — **VALID**

| | |
|--|--|
| **Code now** | `conference_url` / `conference_type` only |
| **Pre-work** | Provider API keys product decision |

### B-011 Role/permission polish — **VALID**

| | |
|--|--|
| **Code now** | 7 roles on User; **~0** policy references for compliance_officer/auditor/TA |
| **Matches** | Audit P1-11 open |
| **Pre-work** | Document matrix first, then tests |
| **When** | Before selling enterprise roles |

### B-012 Agent foundation — **DONE**

| | |
|--|--|
| **Code** | D-012: MCP server, Sanctum, audit log, `agent-ping`, token default **ping-only** |
| **Action** | Keep in done; do not re-open |

### B-013 Agent MCP core tools — **VALID, recommended next (if agent)**

| | |
|--|--|
| **Code now** | Server + ping only; abilities constants include free-flow; token default ping-only; `--free-flow` opt-in |
| **depends_on** | B-012 **satisfied** (D-012) |
| **Pre-work (do during, not before)** | Thin tools → Domain services; no paid enroll; use `--free-flow` only after tools registered |
| **Prep ready** | Enrollment capacity locks, free `isPaid()`, progress services, audit trait |
| **Risk if skip rules** | Dual business logic in tools |

### B-014 Agent compliance tools — **VALID**

| | |
|--|--|
| **depends_on** | D-012 OK |
| **Pre-work** | Prefer after B-013 or parallel if compliance agent priority |
| **Code** | `AuditReportService`, `domain_event_log` exist |

### B-015 Agent webhooks — **VALID deferred**

| | |
|--|--|
| **depends_on** | B-013 still correct |
| **Pre-work** | Ship B-013 poll path first |

---

## Recommended order (code-reality)

```text
NOW (product pick one):
  A) B-013 MCP core tools     ← agent track, foundations ready
  B) Free-flow polish / seed  ← no backlog ID, web track

SOON (when product asks):
  B-011 role matrix           ← if enterprise demos
  B-007 SCORM edge harden     ← if SCORM packages in prod training
  B-009 mobile REST           ← after B-013 patterns proven (optional)

LATER / gated:
  B-001 payment               ← only with gateway project + flip flags
  B-002 SSO                   ← when IdP known
  B-003 multi-tenancy         ← ADR first, last among “big” items

BACKLOG KEEP, low urgency:
  B-004 versioning, B-005 branching, B-006 forum, B-008 LTI, B-010 conference, B-014/015 agent extras
```

---

## What we do **not** need to prepare first

| Claim | Reality |
|-------|---------|
| “Must fix all debt before B-013” | **False** — correctness debt closed |
| “Must implement payment before free LMS” | **False** — payment deliberately off |
| “Must install Sanctum for mobile/agent” | **Done** |
| “Must invent enrollment concurrency” | **Done** (locks + unique + capacity) |
| “SCORM unusable until B-007” | **False** for basic packages; B-007 = harden |

---

## Suggested file tweaks (backlog docs)

1. **B-001** — rewrite Problem: paid path product-off; goal = enable commercial + gateway.  
2. **B-007** — note path jail done; focus multi-SCO/resume/edge.  
3. **B-009** — note Sanctum present; REST surface missing; optional depend D-012.  
4. **B-013** — `depends_on: [D-012]` mentally; mention `--free-flow` after tools.  
5. **README** — link this vet file; next-up callout.

(Applied in same change set where useful.)
