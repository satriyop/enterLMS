---
id: B-003
title: Multi-tenancy / organization scope
status: todo
priority: P1
area: platform
phase: C
depends_on: []
---

# B-003 — Multi-tenancy

## Problem

Semua data global. Tidak bisa isolasi cabang/divisi/bank.

## Goal

Resources (users, courses, enrollments, reports) ter-scope organization.

## Scope

- [ ] Architecture decision (single DB + `organization_id` vs multi-DB)
- [ ] Migration + global scopes / middleware
- [ ] Admin org management
- [ ] Policy updates
- [ ] Test isolation cross-org forbidden

## Risk

Sangat tinggi — sentuh hampir semua query. Butuh design doc di `tasks/artifact/` dulu (local) lalu ADR di `tasks/done/` setelah disetujui.

## Acceptance

User org A tidak bisa lihat/enroll data org B.
