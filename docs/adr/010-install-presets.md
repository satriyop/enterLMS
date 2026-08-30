# Flavor is an install preset of capabilities, not a product type

This academy is one client, one installation (on-prem or dedicated cloud). University, school, and corporate are not tenants and not forks. They are named **presets** that turn capabilities on. Domain code and UI check `Academy::enabled('attendance')` and labels, never `preset === 'academic'`.

The default preset is `academy`: Learner, Course, Tutor, Grade Proposal, LMS Agent — ADR 001 unchanged. `academic` and `corporate` add Offering, Facilitator, and market capabilities (calendar, attendance, letter grades, SSO, identity scheme). Those modules are not in this decision; the flags exist so they can ship later without a flavor `if`.

We rejected Laravel Pennant for flavor (it is for per-scope rollout and lottery, not an academy-wide install), `organization_id` multi-tenancy (B-003 — the box is the isolation), and branching on the preset name. Pennant may still appear later as a short-lived engineering flag that gets purged. `lms.mode` (internal/commercial) stays a different axis; it is not flavor.

One schema for every install. Features hide UI and policy paths; they do not fork migrations.
