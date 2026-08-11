---
id: B-002
title: SSO / OIDC integration
status: todo
priority: P1
area: auth
phase: C
depends_on: []
---

# B-002 — SSO / OIDC

## Problem

Login hanya email/password Fortify. Bank biasanya butuh SSO (Google Workspace / Azure AD / Okta).

## Goal

User korporat login via OIDC provider terpilih; akun di-link ke role Enteraksi.

## Scope

- [ ] Socialite atau package OIDC
- [ ] Mapping email → user + default role
- [ ] Disable register publik (opsional config)
- [ ] Tests + docs env

## Acceptance

Login via SSO berhasil untuk user domain yang diizinkan; user non-domain ditolak.
