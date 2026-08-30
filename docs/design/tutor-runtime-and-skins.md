# Tutor runtime and skins

| Field | Value |
|---|---|
| **Status** | Accepted (grilling 2026-08-30) |
| **Related** | ADR 001, ADR 009, `CONTEXT.md` |

Laravel is the academy. Hermes `enterlms-tutor` runs the Tutor turn. Overlay, WhatsApp, and Telegram are skins of **one** Tutor. Conversation stays one Lesson on one Enrollment. This is the target; the Lesson overlay already talks through Laravel. Messaging skins are not shipped yet.

---

## Shape

```
                            LEARNER
              ┌───────────────┼───────────────┐
              │               │               │
         Lesson overlay    WhatsApp       Telegram
         (Inertia)      (own number)    (own bot)
              │               │               │
              │ Gate          │               │
              │ cookie        │  no cookie    │
              ▼               │               │
         Laravel ─────────────┤               │
         (web skin only:      │               │
          hold API key,       │               │
          URL = Focus)        │               │
              │               │               │
              └───────────────┼───────────────┘
                              ▼
              ┌───────────────────────────────────┐
              │  enterlms-tutor                   │
              │  one Hermes profile, one gateway  │
              │                                   │
              │   API server    WA adapter   TG   │
              │   skill: tutor                    │
              │   tools: MCP only (no shell)      │
              └─────────────────┬─────────────────┘
                                │
                                │  one runtime Bearer
                                │  resolve phone/TG → user_id
                                │
                    MCP /mcp/enterlms
                      can-talk
                      get-focus / set-focus
                      get-published-lesson   (Enrollment checked)
                      get-course-outline
                      commit-turn
                                │
                                ▼
              ┌───────────────────────────────────┐
              │  Laravel academy                  │
              │                                   │
              │   User ← linked phone / telegram  │
              │   Enrollment                      │
              │   Focus (per messaging skin)      │
              │   Conversation (per Lesson)       │
              │   Lesson body_text                │
              │   Progress / Assessment           │
              │     (Tutor cannot complete/grade) │
              └───────────────────────────────────┘
```

The LMS Agent is a **different** Hermes job on a **free-flow** token. A Lesson is not a live console.

---

## Focus

```
  Overlay          WhatsApp              Telegram
  Focus = URL      Focus stored          Focus stored
                   in Laravel            in Laravel
                     │
                     ▼
            Conversation of THAT Lesson only
```

- Overlay: opening a Lesson *is* Focus.
- Messaging: first Focus is the Lesson last opened in the overlay if still allowed; otherwise a short list; a deep link from a Lesson page sets it.
- Switch: Learner asks to change Course/Lesson; Laravel accepts only if enrolled and unlocked. Mentioning another Lesson does not move Focus; the Tutor may offer to switch.
- Outline-level talk stays on the current Focus’s Conversation. Teaching another Lesson’s **body** requires a new Focus.

---

## Overlay — end to end

Browser holds a Laravel session, never the Hermes API key.

```
 Learner          Laravel              Hermes enterlms-tutor         Laravel MCP
    │                 │                         │                         │
    │  POST message   │                         │                         │
    │  (Lesson URL    │                         │                         │
    │   = Focus)      │                         │                         │
    │────────────────►│                         │                         │
    │                 │  Gate (Enrollment,      │                         │
    │                 │   this Lesson)          │                         │
    │                 │                         │                         │
    │                 │  POST /v1/chat/         │                         │
    │                 │  completions            │                         │
    │                 │────────────────────────►│                         │
    │                 │                         │  can-talk               │
    │                 │                         │────────────────────────►│
    │                 │                         │◄──────── ok ────────────│
    │                 │                         │  get-published-lesson   │
    │                 │                         │────────────────────────►│
    │                 │                         │◄──── body_text ─────────│
    │                 │                         │                         │
    │                 │                         │  (model + tutor skill)  │
    │                 │                         │                         │
    │                 │                         │  commit-turn            │
    │                 │                         │  (learner + tutor)      │
    │                 │                         │────────────────────────►│
    │                 │                         │◄──────── saved ─────────│
    │                 │                         │                         │
    │                 │◄──── assistant text ────│                         │
    │◄── Conversation │                         │                         │
```

If `commit-turn` fails, the overlay does not show a Tutor reply.

---

## WhatsApp / Telegram — end to end

No cookie. Hermes `resolve`s identity, then the same agent loop.

```
 Learner          Hermes enterlms-tutor              Laravel MCP
    │                      │                              │
    │  Learner message     │                              │
    │─────────────────────►│                              │
    │                      │  resolve(phone|tg) → user_id │
    │                      │─────────────────────────────►│
    │                      │◄──────── User ───────────────│
    │                      │  get-focus                   │
    │                      │─────────────────────────────►│
    │                      │◄── Lesson or "pick one" ─────│
    │                      │  can-talk + get-published-   │
    │                      │  lesson (Enrollment checked) │
    │                      │─────────────────────────────►│
    │                      │◄──────── body_text ──────────│
    │                      │  (model + tutor skill)       │
    │                      │  commit-turn                 │
    │                      │─────────────────────────────►│
    │                      │◄──────── saved ──────────────│
    │◄──── Tutor reply ────│                              │
```

No `saved` → no send. The overlay on that Lesson then shows the same Conversation.

### Explicit Focus switch (messaging)

```
 Learner          Hermes                         Laravel
    │               │                               │
    │  "pindah ke   │  set-focus                    │
    │   …"          │──────────────────────────────►│
    │               │     enrolled + unlocked?      │
    │               │◄──── Focus moved ─────────────│
    │◄── name the   │                               │
    │    new Focus  │                               │
```

---

## Rules that keep this one product

| Keep | Not |
|---|---|
| Laravel Conversation is the transcript | Hermes session as the record |
| One runtime MCP Bearer + `user_id` on tools | Per-Learner tokens in Hermes; trusting the model’s `course_id` |
| `get-published-lesson` checks Enrollment | Published-only |
| Persist, then reply | Ghost replies |
| Dedicated `enterlms-tutor` gateway | Sharing this job with the LMS Agent job |
| API server (official HTTP door) | Custom Hermes HTTP plugin; Laravel-booted sidecar as the target |

MCP tool names above are the contract. Several do not exist in code yet (`can-talk`, Focus, `commit-turn`, Enrollment on `get-published-lesson`). Overlay today still uses `ConversationService::postTurn` → `TutorRuntime::completeTurn`.
