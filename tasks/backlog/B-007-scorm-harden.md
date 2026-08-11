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

## Goal

Stabil untuk paket SCORM 1.2 umum yang dipakai training bank.

## Acceptance

- Upload invalid package → error jelas
- Progress resume setelah reload
- Completion sync ke lesson progress / enrollment
