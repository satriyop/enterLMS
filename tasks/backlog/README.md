# Backlog

The queue for [ADR 001](../../docs/adr/001-ai-first-class-lms.md). One file = one item. Older items live in [`tasks/parked/`](../parked/).

## Format nama file

```text
B-NNN-short-slug.md
```

## Template header

```yaml
---
id: B-016
title: Short title
status: todo          # todo | in_progress | blocked
priority: P0
area: tutor
depends_on: []
---
```

## Index (aktif)

| ID | Priority | Title | Depends |
|----|----------|-------|---------|
| [B-016](./B-016-tutor-conversation.md) | P0 | Conversation + Tutor on a Lesson | — |
| [B-017](./B-017-grade-proposal.md) | P0 | Grade Proposal | B-016 |

**Next:** B-016. Do not start B-017 until B-016 is done. Do not take files from `tasks/parked/`.

**LMS Agent (MCP):** D-012–D-015 are done. That client is not a Tutor.
