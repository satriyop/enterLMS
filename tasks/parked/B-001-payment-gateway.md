---
id: B-001
title: Payment gateway integration (Midtrans/Xendit) + webhook
status: todo
priority: P1
area: payments
phase: B
depends_on: []
---

# B-001 — Payment gateway

## Problem

`PaymentService` + `PaymentGatewayContract` sudah ada, UI list/show/cancel ada, tapi **tidak ada implementasi gateway nyata**.

**Code 2026-08-12:** payments **product-off** by design (`lms.payment.enabled=false`, `EnsurePaymentsEnabled` → 404, `Course::isPaid()` requires commercial + flag). Kursus dengan `is_paid` di DB **bukan** di-block — diperlakukan gratis sampai flag + gateway hidup.

## Goal

Learner bisa bayar kursus berbayar → webhook sukses → enrollment aktif otomatis (hanya saat commercial + `payment.enabled`).

## Scope

- [ ] Pilih provider (Midtrans **atau** Xendit dulu — satu saja)
- [ ] Implement `PaymentGatewayContract`
- [ ] Endpoint webhook + signature verify
- [ ] UI redirect/checkout dari course detail
- [ ] Config env + commercial mode
- [ ] Feature tests (fake gateway) + manual sandbox checklist

## Out of scope

- Multi-gateway parallel
- Subscription/recurring

## Acceptance

1. Paid course: create payment → sandbox pay → status `paid` → enrollment exists.
2. Invalid webhook signature ditolak.
3. Cancel pending payment works.
4. Free course tetap tidak melewati payment.

## Refs

- `app/Domain/Payment/`
- `tasks/audit/capability-map.md` §7
