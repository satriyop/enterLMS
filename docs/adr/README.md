# Architecture decisions

Read [`CONTEXT.md`](../../CONTEXT.md) for the glossary, then this file first:

**[001 — AI-first LMS](001-ai-first-class-lms.md)** — what this product is. **[009 — Tutor runtime and skins](009-tutor-runtime-and-skins.md)** — who runs a Tutor turn, and the skins. **[014 — Open catalog](014-open-catalog-many-courses.md)** — the public catalog may list many free Open Courses. **[015 — Author Agent](015-author-agent-content-proposal.md)** — a third job proposes content; LMS Admin accepts.

The rest are stack, identity, path, design, and progress. They do not redefine the product.

| # | Decision |
|---|----------|
| [001](001-ai-first-class-lms.md) | AI-first LMS (product) |
| [002](002-inertia-vue.md) | Inertia.js with Vue |
| [003](003-fortify-auth.md) | Fortify for authentication |
| [004](004-progress-tracking.md) | Lesson progress tracking |
| [005](005-identity-phasing.md) | Local registration |
| [006](006-operator-path-reuses-open-course.md) | Restricted OpenClaw Course reuses the public intro |
| [007](007-tenang-conformance.md) | Tenang conformance and role collapse |
| [008](008-one-progress-calculator.md) | One progress calculator, not a strategy |
| [009](009-tutor-runtime-and-skins.md) | Tutor runtime and skins (revises 001’s invoke path) |
| [010](010-install-presets.md) | Flavor is an install preset of capabilities, not a product type |
| [011](011-offering-and-facilitator.md) | Offering is the run of a Course; Facilitator owns that run |
| [012](012-restricted-grant-onto-named-offering.md) | Restricted Enrollment is granted onto a named Offering |
| [013](013-assessment-on-course-window-on-offering.md) | Assessment belongs to the Course; the Offering times the attempt |
| [014](014-open-catalog-many-courses.md) | Public catalog may list many free Open Courses |
| [015](015-author-agent-content-proposal.md) | Author Agent proposes; LMS Admin accepts |
