---
id: B-007
title: Harden SCORM package + runtime
status: todo
priority: P2
area: scorm
phase: E
depends_on: []
---

# B-007 — SCORM harden

## Problem

SCORM foundation ada (upload, player, runtime, tests) tapi belum dianggap production-hardened (edge packages, resume, multi-SCO).

**Code 2026-08-12:** path traversal pada content serve **sudah di-jail** (`ScormPlayerController::resolveSafePackagePath`). B-007 = harden **edge packages / multi-SCO / resume**, bukan build from zero.

## Goal

Stabil untuk paket SCORM 1.2 umum yang dipakai training bank.

## Acceptance

- Upload invalid package → error jelas
- Progress resume setelah reload
- Completion sync ke lesson progress / enrollment
- Path escape tetap ditolak (regresi path jail)
