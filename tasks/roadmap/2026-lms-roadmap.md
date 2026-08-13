# EnterLMS Roadmap (2026)

> **Sumber status fitur:** `tasks/audit/capability-map.md`  
> **Item kerja aktif:** `tasks/backlog/`  
> **Terakhir diselaraskan:** 2026-08-12

## Tujuan produk

LMS perbankan/kepatuhan (OJK-oriented) untuk:

1. Training internal **gratis** (onboarding, compliance) — prioritas utama
2. Monetisasi kursus (opsional, commercial mode)
3. Enterprise (SSO, multi-org) bila dibutuhkan klien bank

## Fase

### Fase A — Core free flow (utama)

**Tujuan:** Satu orang bisa register → belajar → selesai → sertifikat tanpa bayar.

| Area | Target status |
|------|----------------|
| Auth, course content, enroll, progress | ready |
| Assessment + question bank | ready / partial ok |
| Certificate | ready |
| Demo seeder + journey tests | ready |
| Empty states & polish | ready |

**Status fase:** hampir complete (hardening free flow sudah masuk).  
**Sisa polish:** E2E browser manual, seed data refresh di environment real.

---

### Fase B — Monetisasi

**Tujuan:** Kursus berbayar benar-benar bisa dibayar → enroll otomatis.

| Area | Target |
|------|--------|
| Payment model + UI | sudah partial |
| Gateway Midtrans/Xendit + webhook | backlog |
| Commercial mode config | partial |

**Depends on:** Fase A stabil.

---

### Fase C — Enterprise identity & scale

| Area | Target |
|------|--------|
| SSO / OIDC / SAML | missing → backlog |
| Multi-tenancy / organization scope | missing → backlog |
| Bulk ops (enroll/grade) | partial/ready cek ulang |

---

### Fase D — Advanced learning

| Area | Target |
|------|--------|
| Course versioning | missing |
| Learning path branching | missing |
| Discussion / messaging | missing |

---

### Fase E — Integrasi & compliance lanjutan

| Area | Target |
|------|--------|
| SCORM runtime | partial → harden |
| xAPI | partial → harden |
| LTI | missing |
| Mobile API (Sanctum) | missing (reuse Sanctum dari Fase F) |
| Live conference deep integration | partial (URL only) |

---

### Fase F — Agent platform (Hermes / OpenClaw) — Depth B

**Keputusan (2026-08-12):** v1 = **Depth B**

| Include v1 | Defer |
|------------|--------|
| MCP server produk (`laravel/mcp`) | Webhooks outbound → B-015 v1.1 |
| Sanctum token + abilities | Full OAuth/Passport MCP (kecuali client wajib) |
| Read tools + limited enroll/progress write | Embed agent runtime di Laravel |
| Agent action audit log | ACP / A2A mesh, WhatsApp channel |
| Domain services as source of truth | Duplikasi LMS logic di agent skills |

| Area | Target | Backlog |
|------|--------|---------|
| Foundation (token, server, audit) | **done** (D-012) | D-012 |
| Core tools free-flow | missing → next | B-013 |
| Compliance read tools | missing | B-014 |
| Outbound webhooks | missing (v1.1) | B-015 |

**Prinsip:** agent di luar; EnterLMS expose capability aman. Jangan reimplement LMS di skill Hermes.

---

## Urutan eksekusi yang disarankan

```text
A (free flow polish) → F-lite (B-012 foundation, parallel OK)
  → B (payment) / F-core (B-013 tools) sesuai prioritas bisnis
  → C (SSO) → C (multi-tenancy)
  → D (versioning / discussion) → E (LTI / mobile)
  → F v1.1 (B-014 compliance tools, B-015 webhooks)
```

Jangan mulai multi-tenancy sebelum free flow + payment (jika commercial) stabil — multi-tenancy menyentuh hampir semua query.

**Agent:** B-012 dulu (shared Sanctum juga membantu B-009 mobile nanti).
