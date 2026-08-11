# Enteraksi LMS Roadmap (2026)

> **Sumber status fitur:** `tasks/audit/capability-map.md`  
> **Item kerja aktif:** `tasks/backlog/`  
> **Terakhir diselaraskan:** 2026-08-11

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
| Mobile API (Sanctum) | missing |
| Live conference deep integration | partial (URL only) |

---

## Urutan eksekusi yang disarankan

```text
A (free flow polish) → B (payment gateway) → C (SSO) → C (multi-tenancy)
  → D (versioning) → D (branching / discussion) → E (LTI / mobile)
```

Jangan mulai multi-tenancy sebelum free flow + payment (jika commercial) stabil — multi-tenancy menyentuh hampir semua query.
