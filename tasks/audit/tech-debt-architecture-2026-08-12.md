# Tech debt / architecture / code-smell audit

> **Date:** 2026-08-12  
> **Tree basis:** working tree after free-flow harden + agent foundation (D-012) + stabilize pack (Spatie/SCORM/xAPI/capacity/payment-off/progress default)  
> **Method:** evidence from `app/`, `routes/`, `config/`, `tests/` — **not** pattern-worship  
> **Companion:** [capability-map.md](./capability-map.md) (product readiness); this file = **code stability** before new features

---

## Executive verdict

| Question | Answer |
|----------|--------|
| Is free-flow LMS **stable enough** to build on? | **YES — go**, with listed known debt accepted or tracked |
| Is agent/MCP product-ready? | **Partial** — foundation only (`agent-ping`); core tools = [B-013](../backlog/B-013-agent-mcp-core-tools.md) |
| Is commercial payment ready? | **NO** — intentionally disabled; parked as [B-001](../parked/B-001-payment-gateway.md) (not the path to ADR 001) |
| Should we rewrite architecture first? | **NO** — domain boundaries are sound; fix residual P1 when touching those paths |

**Go / no-go for new features**

| Feature track | Decision | Why |
|---------------|----------|-----|
| Free-flow polish, content, assessment, cert | **GO** | Core path ready; recent correctness fixes in tree |
| B-013 MCP core tools (thin adapters) | **GO with rules** | Domain services exist; wrap only; reuse policies |
| B-001 payment gateway | **NO-GO until intentional** | Domain half-wired; flag off by design |
| Multi-tenancy / SSO | **NO-GO** | Cross-cuts all queries; product gaps, not code polish |
| Big DTO/event-sourcing rewrite | **NO-GO** | Would add debt without solving free LMS needs |

---

## Recently closed (do not re-open as P0)

Verified **present in current tree** (re-scan 2026-08-12):

| Former risk | Evidence now | Tests |
|-------------|--------------|-------|
| Spatie `status === 'string'` false forever | `EnrollmentContext` uses `$enrollment?->isActive()`; progress uses `isCompleted()`; `CourseController` uses `isDraft()` | `tests/Feature/Stabilize/StabilizeDebtFixesTest.php` |
| SCORM path traversal | `ScormPlayerController::resolveSafePackagePath` rejects `..` | same Stabilize suite |
| xAPI IDOR index + actor spoof | `auth:sanctum`; force actor in `XapiStatementController@store`; learner scope on index | Stabilize + `tests/Feature/Xapi/` |
| Capacity race | `lockForUpdate` + `assertCapacityAvailable` on create / drop-reactivate / soft-delete restore | Stabilize |
| Soft-delete unique brick | restore path + capacity check | Stabilize |
| Home stats wrong schema | `visibility = 'public'`; `COALESCE(manual_duration_minutes, estimated_duration_minutes, 0)` | Stabilize |
| Dual route load courses/paths | only `routes/web.php` requires them; `bootstrap/app.php` documents single source | Stabilize |
| Progress FormRequest `authorize: true` | Gate via `LessonProgress` policy | Stabilize |
| Unlimited parallel assessment starts | resume open attempt; count `in_progress` toward max | `AssessmentAttemptFlowTest` |
| Payment ghost success path | `lms.payment.enabled` default false; `Course::isPaid()` requires flag; `ensurePaymentsEnabled()` | `PaymentTest` + Stabilize |
| PHPStan live errors (prior 8) | `./vendor/bin/phpstan analyse` → **0 errors** (baseline still holds historical ignores) | CI local |
| xAPI context enrollment ownership | `XapiStatementController::scopedContextForActor` rejects foreign enrollment ids | Stabilize |
| Agent token default write abilities | `agent:token` defaults to `AgentAbility::defaults()` = ping only; `--free-flow` opt-in | Agent MCP foundation tests |
| Dual enrollment SoftDeletes + states | SoftDeletes **removed** from `Enrollment`; lifecycle = status only; migration drops `deleted_at` | DataIntegrity + Stabilize |
| Invite magic-string exceptions | `InvitationNotPendingException` / `InvitationExpiredException` | EnrollmentController catches types |
| Section duration accessor query | `getDurationAttribute` uses denormalized field only | — |
| Prod N+1 soft-fail | `lms.strict_eager_loading` default **true** (fail closed) | RequiresEagerLoading |
| Payment HTTP when disabled | `EnsurePaymentsEnabled` middleware 404 | routes/payments.php |

---

## Open findings (current)

### Severity legend

| Sev | Meaning |
|-----|---------|
| **P0** | Correctness/security/integrity risk active in default free path |
| **P1** | Multiplies cost of next features or bites under load/commercial/agent |
| **P2** | Smell / maintainability; fix when touching area |

---

### P0 — blocking free-path stability

**None remaining** for the default **internal / free** LMS path after stabilize.

If any of the “Recently closed” items are **not** committed/deployed on a given branch, treat that branch as not audited green.

---

### P1 — fix before or while building next features

#### P1-1 Dual enrollment lifecycle — **closed**

SoftDeletes removed from Enrollment; only Spatie status lifecycle remains.

#### P1-2 Payment domain half-product — **closed as product-off**

Still no gateway (B-001), but HTTP surface 404 via `EnsurePaymentsEnabled` + service hard-fail + `isPaid()` gated. Residual domain code intentional scaffolding for B-001.

#### P1-3 Agent MCP abilities without tools (product/API drift risk)

| | |
|--|--|
| **What** | Abilities define free-flow scopes; only tool is `agent-ping`. |
| **Why** | Tokens can be issued with write abilities that do nothing yet — or B-013 might reimplement rules outside domain if rushed. |
| **Where** | `app/Domain/Agent/Abilities/AgentAbility.php`; `app/Mcp/Servers/EnterLmsAgentServer.php` (`$tools = [AgentPingTool::class]`); `routes/ai.php` |
| **Fix direction** | B-013: **thin** tools → Domain services + existing policies only. Issue tokens with minimal abilities until tools ship. |
| **Before** | Hermes/OpenClaw real workflows |

#### P1-4 xAPI context IDs not ownership-scoped — **closed 2026-08-12**

See “Recently closed”. Residual: compliance roles may still set arbitrary context (by design for auditors).

#### P1-5 `RequiresEagerLoading` silent N+1 — **closed**

Default fail-closed via `lms.strict_eager_loading` (true). Soft-degrade only if env false.

#### P1-6 Query-in-accessor section duration — **closed**

Accessor returns denormalized `estimated_duration_minutes` only; recompute via `updateEstimatedDuration()`.

#### P1-7 Nested resource scoping inconsistent

| | |
|--|--|
| **What** | Some controllers hard-filter parent (`where course_id`); others rely on policy 403; no global `scopeBindings()`. |
| **Why** | New nested routes can copy wrong pattern → IDOR or 403/404 inconsistency. |
| **Where** | e.g. `LessonController` / `AssessmentController` parent checks; `tests/Feature/Authorization/NestedRouteScopingTest.php` accepts 403 **or** 404 |
| **Fix direction** | Standardize abort 404 when child ∉ parent; optional route `scoped` bindings |
| **Before** | New nested admin/agent routes |

#### P1-8 Exception control flow via magic strings — **closed**

Domain exceptions for invitation not pending / expired.

#### P1-9 PHPStan baseline (~46 historical ignores)

| | |
|--|--|
| **What** | Live analyse is clean; baseline still ignores older type holes (Resources, events, CSV types, etc.). |
| **Why** | Same class of bugs can reappear without forcing fix. |
| **Where** | `phpstan-baseline.neon`; `phpstan.neon` level 5 |
| **Fix direction** | Burn down `property.notFound` / Resource typing when editing those files |
| **Before** | Large Resource/API work |

#### P1-10 Fat gravity wells (maintainability)

| | |
|--|--|
| **What** | Very large units: `Course` model ~582 LOC; Path/Enrollment/Payment services 400+; `EnrollmentController` ~327. |
| **Why** | Not wrong today; high change risk when multi-tenant / versioning lands. |
| **Where** | `app/Models/Course.php`; `PathProgressService`; `EnrollmentService`; `PaymentService`; `EnrollmentController` |
| **Fix direction** | Extract only when a story forces it; do not big-bang split |
| **Before** | Course versioning (B-004), multi-tenancy (B-003) |

#### P1-11 Enterprise roles incomplete (product/authz)

| | |
|--|--|
| **What** | 7 roles on `User`; policies still mostly CM/trainer/admin/learner. |
| **Why** | Compliance/auditor/TA may over/under-permit on new pages. |
| **Where** | `app/Models/User.php` `ROLES`; sparse policy references; backlog [B-011](../backlog/B-011-role-permission-polish.md) |
| **Fix direction** | Matrix pass when enterprise customers appear |
| **Before** | Selling “compliance officer” product story |

---

### P2 — smells / cleanup when nearby

| ID | Finding | Where | Note |
|----|---------|-------|------|
| P2-1 | Double authorize: FormRequest Gate + controller `Gate::authorize` on progress | `LessonProgressController` + FormRequests | Harmless redundancy; pick one layer |
| P2-2 | Payment routes registered even when disabled | `routes/payments.php` via `web.php` | UX noise; service still hard-fails |
| P2-3 | Strategy factories + tags larger than swap need | `DomainServiceProvider` grading/progress/prereq | OK if used; avoid new strategy layers “for flexibility” |
| P2-4 | Large Vue pages (question-bank, outline) | `resources/js/pages/question-bank/*` | DX only unless editing those pages |
| P2-5 | ~~Agent freeFlow as default~~ **closed** — default is ping-only; `--free-flow` opt-in | `AgentAbility::defaults()` | |
| P2-6 | `EnrollmentController@store` inline auth (no FormRequest) | `EnrollmentController` | Works; inconsistent with other resources |
| P2-7 | redesign-tournament artifacts uncommitted / parallel design track | `redesign-tournament/` | Out of product runtime path |

---

## Architecture assessment (objective)

### Sound (keep)

1. **Bounded contexts under `app/Domain/*`** with thin controllers and services returning models.  
2. **Rich models for state** (enrollment/course) + Spatie model-states.  
3. **Policies + FormRequests** as primary authz/validation (Laravel-native).  
4. **Concurrency awareness** on enroll (locks, unique, capacity).  
5. **Domain events + `domain_event_log`** for compliance trail.  
6. **Agent as external client** (MCP + Sanctum) — correct product boundary.  
7. **Test investment** large Feature suite including Stabilize, free-flow journey, nested scoping.

### Weak / watch

1. **Dual surfaces** (Inertia web vs token/MCP/xAPI) without a single “application service” doc — mitigated if B-013 stays thin.  
2. **Dual lifecycle** on enrollment (P1-1).  
3. **Half-domains** (payment) that look production-shaped but are disabled (P1-2).  
4. **Prod soft-fail N+1** (P1-5).  
5. **Role model** coarse for enterprise (P1-11).

### Explicit non-problems (do not “fix” without cause)

- DDD folder layout is not over-engineered for this size.  
- Strategy pattern for grading/progress is justified by multiple algorithms.  
- JsonResource over custom VO layers is correct Laravel style.  
- Inertia + Fortify auth for learners is appropriate; no need for Passport for web.

---

## Before next feature — checklist

Use this as a gate; not all must be code-complete.

### Always (any feature)

- [ ] Touching enroll/capacity/invite → keep locks; no soft-delete without restore path  
- [ ] Nested routes → assert parent ownership (404 preferred)  
- [ ] New progress/API write → FormRequest authorize via policy  
- [ ] PHPStan clean on touched files (no new baseline noise)

### Before B-013 (agent tools)

- [ ] Tools only call Domain services / model methods used by web  
- [ ] Ability checks via `tokenCan` + audit logger (existing trait)  
- [ ] Do not issue default freeFlow write tokens until tools exist (prefer ping-only)  
- [ ] Paid enroll remains blocked via `isPaid()` / payment flag  

### Before B-001 (payment)

- [ ] Real gateway class + container bind  
- [ ] Webhook signature verify  
- [ ] Flip `lms.payment.enabled` only with tests green  
- [ ] UI only when enabled  

### Before multi-tenancy / SSO

- [ ] Expect cross-cutting query + policy rewrite — plan as product phase, not “cleanup sprint”

---

## Cross-links

| Artifact | Role |
|----------|------|
| [capability-map.md](./capability-map.md) | Product capability ready/partial/missing |
| [../roadmap/README.md](../roadmap/README.md) | Work toward ADR 001 |
| [../done/D-012-agent-mcp-foundation.md](../done/D-012-agent-mcp-foundation.md) | MCP foundation done |
| [../backlog/B-013-agent-mcp-core-tools.md](../backlog/B-013-agent-mcp-core-tools.md) | Next agent work |
| [../parked/B-001-payment-gateway.md](../parked/B-001-payment-gateway.md) | Payment — parked, not ADR 001 |
| `tests/Feature/Stabilize/StabilizeDebtFixesTest.php` | Regression net for stabilize fixes |

---

## Summary table (status after close pack)

| ID | Sev | Topic | Status |
|----|-----|-------|--------|
| — | P0 | Free path | **none open** |
| P1-1 | P1 | SoftDeletes + enrollment states | **closed** |
| P1-2 | P1 | Payment half-domain HTTP | **closed** (product-off + 404 middleware) |
| P1-3 | P1 | MCP core tools | **closed → D-013** |
| P1-4 | P1 | xAPI context ownership | **closed** |
| P1-5 | P1 | Prod N+1 fallback | **closed** (fail-closed default) |
| P1-6 | P1 | Section duration accessor | **closed** |
| P1-7 | P1 | Nested scoping consistency | **accepted** (NestedRouteScopingTest; standardise on new routes) |
| P1-8 | P1 | Exception string matching | **closed** |
| P1-9 | P1 | PHPStan baseline | **accepted deferred** |
| P1-10 | P1 | Fat models/services | **accepted deferred** |
| P1-11 | P1 | Enterprise role matrix | **open → B-011** (product) |
| P2-* | P2 | smells | opportunistic |

**Bottom line (updated):** Correctness/security debt from the audit is **closed or product-gated**. Remaining open items are **features** (B-013, B-011) or **accepted deferred** maintainability — safe to vet backlog and build free-flow / agent tools.
