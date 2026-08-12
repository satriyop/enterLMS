---
id: B-009
title: Mobile API (Sanctum)
status: todo
priority: P2
area: platform
phase: E
depends_on: []
# Soft: Sanctum + agent token path already from D-012
---

# B-009 — Mobile API

## Problem

Hampir full Inertia web; API minim (health, xAPI, search).

**Code 2026-08-12:** Sanctum **sudah** terpasang (`HasApiTokens`, personal access tokens, xAPI `auth:sanctum`, MCP Bearer). Yang hilang = **REST surface** catalog/enroll/progress untuk native app — bukan instal Sanctum.

## Goal

Token API untuk auth, catalog, enroll, progress, assessments (subset). Prefer reuse Domain services (sama seperti B-013 MCP tools).

## Acceptance

Mobile client bisa login token + complete one free course flow via API.

## Note

Jika B-013 selesai dulu, REST bisa mirror tool surface (hindari dual business logic).
