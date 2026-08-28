# General Requirement

## Read CONTEXT.md first

`CONTEXT.md` at the repo root is the authority on what this product is, who uses
it, and what is out of scope. `docs/adr/` holds the decisions and the reasoning
behind them.

Read `CONTEXT.md` before any implementation decision. The product decision is
`docs/adr/001-ai-first-class-lms.md`.

**Do not restate its contents here.** This file is composed into `CLAUDE.md` and
loaded into every session automatically, so a domain fact copied into it keeps
being asserted long after the decision that changed it — and it outranks the
correct file, because only the copy is loaded.

This file carries only requirements that hold regardless of what the domain is.
Anything that would need editing if the product were repositioned again belongs
in `CONTEXT.md` or an ADR, not here.

Scope is enforced, not merely documented — see `tests/Feature/Docs/`.

## Build requirements

- Primary language is Bahasa Indonesia, including validation messages. Seed data
  uses Indonesian names and context.
- Responsive UI is mandatory; every page must work on mobile.
- Every feature ships with tests. See the test enforcement rules in `CLAUDE.md`.
- All input is validated and sanitised. Authorization is enforced by policy,
  never by hiding UI.
- The UI conforms to the Tenang design system: semantic tokens and editorial
  type, no stock Tailwind hues (ADR 007). Gate:
  `tests/Feature/Design/TenangConformanceTest.php`.
- Prefer deleting code over adding abstraction. See the anti-over-engineering
  rules at the top of `CLAUDE.md`.
