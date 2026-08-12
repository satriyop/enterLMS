# Capability Map — Enteraksi LMS

> **Generated:** 2026-08-11 · **Updated:** 2026-08-12 (agent depth B + stabilize + payment-off)  
> **Stack:** Laravel 13 · Inertia 3 · Vue 3 · Pest · laravel/mcp · Sanctum  
> **Basis:** domain code, routes, pages, tests, seeders  
> **Code stability / debt (before new features):** see [tech-debt-architecture-2026-08-12.md](./tech-debt-architecture-2026-08-12.md)

## Legend

| Status | Arti |
|--------|------|
| ✅ **ready** | Alur utama end-to-end bisa dipakai + ada coverage test/UI |
| 🟡 **partial** | Ada fondasi (model/service/UI), tapi gap penting |
| ❌ **missing** | Belum ada implementasi bermakna |
| ⚪ **n/a** | Tidak di-scope fase ini |

---

## 1. Identity & access

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| Login / logout / session | ✅ ready | Fortify + Inertia auth pages |
| Register (default learner) | ✅ ready | `CreateNewUser`, role default `learner` |
| Email verification | ✅ ready | Fortify feature pages/tests |
| Password reset / change | ✅ ready | Auth + settings |
| 2FA | ✅ ready | Fortify two-factor pages |
| Role model (7 roles) | 🟡 partial | Enum + helpers ada; policy matrix belum merata untuk role enterprise baru |
| Granular permissions (Spatie etc.) | ❌ missing | Masih role-based kasar |
| SSO / SAML / OIDC / AD | ❌ missing | Tidak ada Socialite/SAML integration |
| Multi-tenancy / organization | ❌ missing | Tidak ada `organization_id` scope |

---

## 2. Course content management

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| CRUD course (CM/admin) | ✅ ready | Controllers, policies, pages |
| Section + lesson structure | ✅ ready | Nested routes, reorder |
| Lesson types: text / video / audio / document / youtube | ✅ ready | `Lesson` content_type + player components |
| Lesson type: conference (Zoom/Meet URL) | 🟡 partial | Simpan URL saja, bukan integrasi API live class |
| Lesson type: SCORM | 🟡 partial | Upload package + player + runtime API + tests; harden edge cases |
| Publish validation (min content) | ✅ ready | Cannot publish empty course |
| Draft / published / archived states | ✅ ready | Spatie model states |
| Course versioning | ❌ missing | Edit langsung mempengaruhi learner aktif |
| Categories & tags | ✅ ready | Admin CRUD |
| Soft delete / trash restore | ✅ ready | Admin trash |
| Media upload | ✅ ready | Media controller |

---

## 3. Enrollment & learning delivery

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| Self-enroll public free course | ✅ ready | EnrollmentService + journey tests |
| Invitation restricted course | ✅ ready | Invite accept/decline flow |
| Enrollment deadline / capacity | ✅ ready | Validated in service + tests |
| Progress tracking (lesson page/media) | ✅ ready | ProgressTrackingService |
| Complete course on 100% lessons | ✅ ready | `EnrollmentCompleted` event |
| My Learning + learner dashboard | ✅ ready | Pages + empty states |
| Browse catalog | ✅ ready | Filters + empty states |
| Deadline reminders (scheduled) | ✅ ready | Artisan command + schedule |
| Paid course enroll after payment | 🟡 partial | Payment model + block on unpaid; **no live gateway** |

---

## 4. Assessment & question bank

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| Assessment CRUD + publish | ✅ ready | Nested under course |
| Question types MC / TF / short / essay | ✅ ready | Strategies + grading |
| Attempt start / submit / auto-grade | ✅ ready | Domain services + UI |
| Manual grading | ✅ ready | Grade UI |
| Max attempts / passing score | ✅ ready | Model rules |
| IP / UA logging (anti-cheat basic) | ✅ ready | Attempt fields |
| Question bank reusable items | ✅ ready | Domain + pages + seeder |
| Import bank → assessment | ✅ ready | Import flow + tests |
| Advanced proctoring / lockdown | ❌ missing | Beyond IP log |

---

## 5. Learning paths

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| Path CRUD + publish | ✅ ready | Admin/CM pages |
| Sequential prerequisites | ✅ ready | Evaluators |
| Min completion % gate | ✅ ready | Enforced in evaluators |
| Optional course opt-in | ✅ ready | Learner enroll optional |
| Path progress sync on course complete/drop | ✅ ready | Event listeners |
| Path branching (choose 1 of N) | ❌ missing | Linear only |
| Path certificate | 🟡 partial | Certificate types include path; journey less polished than course cert |

---

## 6. Certificates

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| Auto-issue on course completion | ✅ ready | `IssueCertificateOnCompletion` |
| PDF download / stream | ✅ ready | DomPDF |
| Public verification | ✅ ready | `/certificates/verify/{code}` |
| Learner certificate list | ✅ ready | Index + empty state |
| Revoke (admin) | ✅ ready | Service + policy |
| Fancy branded templates | 🟡 partial | Basic template only |

---

## 7. Payments

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| Payment model + statuses | 🟡 partial | Table, service, tests |
| PaymentGateway contract | 🟡 partial | Interface only — **no Midtrans/Xendit class** |
| Create/cancel payment UI | 🟡 partial | Index/show/cancel |
| Webhook + signature verify | ❌ missing | Contract method unused |
| Auto-enroll after paid | 🟡 partial | Logic di service; butuh gateway sukses |
| Refunds | 🟡 partial | Service method; no gateway |

---

## 8. Compliance, audit, analytics

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| Domain event log | ✅ ready | Shared listeners |
| Audit report UI + CSV export | ✅ ready | Compliance domain + pages |
| Learner/admin dashboards | 🟡 partial | Basic stats, not full analytics BI |
| OJK-grade regulatory pack | 🟡 partial | Audit foundation; not full compliance suite |

---

## 9. SCORM / xAPI / LTI

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| SCORM package upload/parse | 🟡 partial | Domain + tests; packaging edge cases remain |
| SCORM runtime (CMI) | 🟡 partial | Initialize/set/get/commit/finish |
| xAPI statement store + event hooks | 🟡 partial | Table + listeners for key events |
| LTI 1.3 tool/provider | ❌ missing | — |
| External LMS interoperability full suite | ❌ missing | — |

---

## 10. Communication

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| In-app notifications (DB) | ✅ ready | Index + mark read |
| Mail on enroll/complete/access change | ✅ ready | Listeners + mailable |
| Discussion / forum | ❌ missing | — |
| Direct messaging | ❌ missing | — |
| Announcements system | ❌ missing | Flash/notif only |

---

## 11. Platform / DX

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| Free-flow demo seeder | ✅ ready | `FreeFlowDemoSeeder` |
| Banking course seed | ✅ ready | `BankingCourseSeeder` (free) |
| E2E free-flow journey test | ✅ ready | Register → cert |
| Pest feature suite | ✅ ready | Large suite (~2600+) |
| Mobile API (Sanctum) | ❌ missing | Mostly Inertia web; Sanctum diantar B-012 |
| Offline mobile | ❌ missing | — |
| Multi-language i18n UI | 🟡 partial | UI Bahasa; not full i18n framework |
| Production hardening (queue, monitoring) | 🟡 partial | Pail/queue scripts; deploy ops TBD |

---

## 12. Agent platform (Hermes / OpenClaw)

> **Keputusan v1:** Depth B — MCP + Sanctum + read/limited-write + audit. Webhooks = v1.1.

| Capability | Status | Bukti / gap |
|------------|--------|-------------|
| Sanctum product tokens | ✅ ready | Sanctum + `HasApiTokens` + `agent:token` (D-012); xAPI also `auth:sanctum` |
| MCP product server (`/mcp/enteraksi`) | ✅ ready | Free-flow tools D-013 + `agent-ping` |
| Agent abilities/scopes | ✅ ready | `AgentAbility` constants + Sanctum abilities |
| Agent action audit log | ✅ ready | `agent_action_logs` + `AgentActionLogger` |
| MCP catalog/enroll/progress tools | ✅ ready | D-013: list/get/enroll/progress/complete |
| MCP compliance read tools | ❌ missing | B-014 |
| Outbound agent webhooks | ❌ missing | B-015 v1.1 |
| Embed agent runtime in Laravel | ⚪ n/a | Explicitly out of scope |
| ACP / A2A / WhatsApp channel | ⚪ n/a | Not depth B |
| Free-path stabilize (Spatie/SCORM/xAPI/capacity) | ✅ ready | See tech-debt audit “Recently closed”; `tests/Feature/Stabilize/` |
| Payment (live gateway) | ❌ missing | Domain exists; `lms.payment.enabled=false` by design until B-001 |

---

## Ringkasan skor (kasar)

| Area | Ready | Partial | Missing |
|------|------:|--------:|--------:|
| Identity | 5 | 1 | 2 |
| Content | 7 | 2 | 1 |
| Enrollment/learning | 7 | 1 | 0 |
| Assessment | 7 | 0 | 1 |
| Learning paths | 5 | 1 | 1 |
| Certificates | 5 | 1 | 0 |
| Payments | 0 | 5 | 1 |
| Compliance | 2 | 2 | 0 |
| SCORM/xAPI/LTI | 0 | 3 | 2 |
| Communication | 2 | 0 | 3 |
| Platform/DX | 4 | 2 | 2 |
| Agent platform | 3 | 1 | 3 (+ 2 n/a) |

**Kesimpulan:**

- **Siap dipakai sekarang:** free internal LMS (content → enroll → learn → assess → certificate → basic audit).
- **Belum siap production commercial/enterprise:** payment gateway, SSO, multi-tenancy, discussion, LTI, mobile API.
- **Agent (Hermes/OpenClaw):** depth B dipilih; fondasi B-012 in progress; tools B-013.
- **Tracker lama (`.ai/investigation`) outdated** — mis. SCORM sudah partial di code tapi dulu ditandai pending.

---

## Mapping cepat ke backlog

| Gap | Backlog ID (lihat `tasks/backlog/`) |
|-----|-------------------------------------|
| Payment gateway + webhook | B-001 |
| SSO / OIDC | B-002 |
| Multi-tenancy | B-003 |
| Course versioning | B-004 |
| Path branching | B-005 |
| Discussion/forum | B-006 |
| SCORM harden | B-007 |
| LTI | B-008 |
| Mobile API | B-009 |
| Conference deep integrate | B-010 |
| Role/permission polish | B-011 |
| Agent MCP foundation | D-012 (done) |
| Agent MCP core tools | B-013 |
| Agent MCP compliance tools | B-014 |
| Agent outbound webhooks | B-015 |
