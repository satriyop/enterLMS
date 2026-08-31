# Tutor overlay and launcher

| Field | Value |
|---|---|
| **Status** | Proposal — mockup only, nothing implemented |
| **Related** | ADR 007 (Tenang conformance), ADR 009 (Tutor runtime and skins), `CONTEXT.md` |
| **Files** | `overlay.html`, `launcher.html`, `assets/tokens.css` |

```bash
cd docs/design/tutor-overlay && python3 -m http.server 5180   # → http://localhost:5180/overlay.html
```

A module graph is used, so open it over HTTP — `file://` will not load `assets/overlay.js`.

---

## What is wrong today

**The panel cannot move because of where it lives, not how it is styled.** `LessonTutorPanel`
renders inside `<main>` at `absolute top-4 right-4` (`Show.vue:187–207`). Everything about
its confinement follows from that one line: it cannot cross into the Konten Kursus column,
it cannot be parked over a part of the Lesson the Learner has finished reading, and it
covers the body text it is supposed to be discussing. A width of `22rem` and a height of
`min(60vh, 28rem)` are then fixed for every Learner, every Lesson, and every screen. The
Tutor answers at lecture length; the screenshot shows a reply clipped mid-sentence at
"jadi aku tidak…". Reading the answer requires scrolling a 12-line box while the paragraph
it refers to is hidden behind it.

**The launcher promises the wrong object.** `CONTEXT.md:49–51` spends a paragraph forbidding
*chatbot*, *copilot* and *assistant* for this thing — it is "the teacher a Learner talks to
about a Lesson on their Enrollment". The current button is a stock `MessageCircle`: the
chatbot glyph, in a flat pill, with no state. It cannot say it is answering, that an answer
is waiting, or that this Lesson has no Tutor at all (a preview Lesson does not).

---

## The overlay

**It is a window, not a modal.** No scrim on desktop; the Lesson stays readable and
scrollable underneath. Reading and asking happen at the same time — that is the entire
point of an overlay rather than a page. The Learner drags it by the header, resizes it from
eight grips, and it stays where they put it.

**Dragging to an edge docks it.** Snapping is not a second component: the same window
surrenders its free axis — full height, square inner corners, no shadow, width still
resizable. This is the only way most people will discover docking, so the promise is painted
before the pointer is released (`.tutor-snap-hint`).

**Escape collapses; it does not close.** A Learner reaching for Escape wants the Lesson
back, not the transcript gone. Collapse leaves the title bar and — importantly — the Focus
chip on screen.

**Position is a workspace preference; open/closed is about the Lesson.** Geometry goes to
`localStorage` and outlives the Lesson. Whether the overlay was open stays in
`sessionStorage` keyed by Lesson, which is what ships today. A remembered position from a
wider monitor is clamped on load so at least `--tutor-keep-visible` of the header is
reachable — a window that cannot be grabbed is a window that is gone.

**Every pointer gesture has a key.** The header is focusable: arrows move, `Alt`+arrows
resize, `Shift` makes either step 1px, `Home`/`End` dock, `Enter` collapses, `Alt`+`T`
toggles the whole thing. Drag-only would make the feature unusable with a keyboard, and
that is not a trade-off available to us.

**Focus is named, not implied.** The header carries a chip with the Lesson title. The
shipping panel says "Tanya tentang Lesson ini" — true, but it never says *which*, and once
the window floats free of the content column "ini" stops pointing at anything. Focus is a
first-class object in `CONTEXT.md:57`; it gets its own token so it cannot be restyled into
a subtitle.

**The Tutor does not get a speech balloon.** The Learner's turns are bubbles — right
aligned, quiet surface, their own words being the least interesting thing on screen. The
Tutor's turns are a rule and prose, capped at `--tutor-measure` for line length. Balloons
cap comfortable reading at a few lines; a lecturer answering in full does not fit in one.

**WhatsApp and Telegram move under a menu.** Two full-width buttons under the composer read
as two other places to ask. They are not: they are skins of the same Tutor (ADR 009), and
following one sets that skin's messaging Focus. A hand-off menu says that; a button row
says the opposite.

**The composer states what the Tutor is not.** "Bertanya tidak menyelesaikan Lesson" sits in
the footer next to the send hint. `CONTEXT.md:62` — talking to a Tutor does not complete a
Lesson — is exactly the misconception a comfortable chat window creates.

**On a phone it is a bottom sheet.** A draggable window on a 375px screen is a joke. Below
640px the same component becomes a sheet with a scrim and a drag-to-dismiss handle, and the
grips disappear.

We rejected: a modal with a scrim (kills the reason to overlay a Lesson at all); a fixed
right-hand drawer that pushes the Lesson body (re-flows the paragraph the Learner is reading
mid-sentence); remembering geometry per Lesson (position is about the desk, not the page);
free rotation and z-order for multiple windows (there is one Tutor); making Escape close
(destroys the Learner's place for a keypress people hit reflexively).

## The launcher

Four variants in `launcher.html`, sharing one mark and one token set. **The pill is the
recommendation** — the label is always visible, so nothing has to be guessed, which is what
"understandable right away" actually costs.

The mark is a speech bubble **carrying a spark**. The bubble is kept because "this is where
you ask" must survive at 14px with no label; the spark is what stops it from being the
chatbot glyph the glossary rejects. The futurism budget is spent on exactly one thing: a
single highlight travelling the rim on hover and focus (`--tutor-aura`), driven by an
animated `@property` angle. No neon, no second hue, no gradient — ADR 007 does not leave
room for a colour that is not already in the palette, and a glow that is always on is
movement in the corner of the eye of someone trying to read.

States are colour or words first, motion second: answering runs the aura at double speed
*and* writes "Tutor sedang menjawab…" in the thread; an unread answer is a gold dot (gold
means "this is yours" in this palette, ADR 007); unavailable is a disabled pill with the
reason in `title`, not a hidden button — hiding it makes people think the feature broke.

The fourth variant is additive: select a phrase in the Lesson body and an anchor appears
offering to ask about that phrase, seeding the composer with it. It is the most direct
expression of Focus in the whole design — the question arrives already carrying its subject.

---

## Token contract

`assets/tokens.css` is the deliverable; copy its `:root` and `.dark` blocks into
`resources/css/app.css`. Three families are genuinely new to the app:

| Family | Why it does not exist yet |
|---|---|
| `--tutor-z-*` | The app has no stacking ladder; the panel uses a bare `z-40`, which is why it sits under nothing in particular and inside `<main>` regardless. |
| `--tutor-elev-*` | `--shadow-lg` is a resting shadow. A window needs a *second*, wider one while dragging — the only depth cue a pointer user gets that the thing is loose. |
| `--tutor-dur-*`, `--tutor-ease-*` | `app.css` declares no motion scale at all, so every component invents its own timing. |

No new colour is introduced. Every colour token resolves through a Tenang primitive, and the
dark block overrides only the two values that genuinely invert — the scrim and the aura,
which must darken rather than brighten over dark mode's mint `--primary`.

Under `prefers-reduced-motion`, all five duration tokens go to `0ms`. Nothing is lost,
because no state in this design is carried by motion alone.

---

## Adoption

| Step | Change | |
|---|---|---|
| 1 | Copy the token blocks into `resources/css/app.css`, and add `tutorGeometry` to `STORAGE_KEYS` in `lib/constants.ts`. No visual change; nothing else can land first. | done |
| 2 | The launcher stops being a speech bubble and starts carrying its own name. | done |
| 3 | `LessonTutorPanel.vue` wraps its root in `<Teleport to="body">` and drops its own `absolute top-4 right-4 z-40` wrapper, and `assets/overlay.js` becomes a `useTutorWindow()` composable — `geometry`, `clamp`, `persist`, drag, resize, keyboard. `Show.vue` needs **no** change: the wrapper lives in the panel, and Teleport relocates the DOM from wherever the component is declared. | done |
| 4 | `dock`, `undock`, the snap preview, collapse, and the mobile sheet. | |
| 5 | The Focus chip and the Follow divider. | |

Three things bit on step 3. The existing tests mount with a bare `mount()` and assert
through `wrapper.find()`, so teleported DOM became invisible to them — they now mount with
`global: { stubs: { teleport: true } }`. `LessonTutorPanel.vue` is **not** in
`tests/Feature/Design/tenang-baseline.json`, so it has no licence to carry a literal hex or
a `bg-card`; the tokens have to exist in `app.css` before the markup can reference them.

And the app server-renders (`vite build --ssr`, `INERTIA_SSR_ENABLED` defaults true). Vue
buffers teleported content into a slot Inertia's server render never emits, so a plain
`<Teleport to="body">` would drop the overlay on the server and then log a hydration
mismatch. The teleport is disabled until `onMounted`, which costs three lines and no visual
difference — the wrapper is `fixed`, so it looks identical rendered in place.

`components/ui/` is exempt from the Tenang gate, but this component is not — the conformance
test in `tests/Feature/Design/TenangConformanceTest.php` will see it, so the `--tutor-*`
tokens must land in `app.css` before any of this markup does.

---

## Focus changes: follow the URL

ADR 009 says the overlay's Focus **is** the Lesson URL. So when a Learner clicks
*Selanjutnya* with the overlay open, Focus genuinely moves, and the Conversation on screen
stops being the one new turns are recorded against. **Decided: follow it, and draw a
divider saying so.** The Learner loses sight of an answer they were half-way through — the
real cost — but `ConversationService` has already persisted every turn, so navigating back
brings it straight back, and the composer is never pointed somewhere other than where it
will write.

We rejected **ask** — freeze the old transcript and offer to move — because declining
leaves an overlay Focus that is not the URL, which amends ADR 009 rather than styling
around it. And **carry** — keep the old turns above the divider — because an answer that is
still on screen but no longer visible to the Tutor invites *"tapi tadi kamu bilang…"*, and
the screen would be promising a continuity the Conversation does not have.

Implemented in `applyFocusChange()` at the bottom of `assets/overlay.js`.
