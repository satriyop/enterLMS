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

`PaymentService` + `PaymentGatewayContract` sudah ada, UI list/show/cancel ada, tapi **tidak ada implementasi gateway nyata**. Kursus berbayar di-block dengan pesan, tidak bisa diselesaikan sampai paid.

## Goal

Learner bisa bayar kursus berbayar → webhook sukses → enrollment aktif otomatis.

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
