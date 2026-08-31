/* ============================================================
   Tutor overlay — interaction model (mockup)
   ------------------------------------------------------------
   This is the behaviour contract the Vue component should
   implement, written out so it can be felt rather than read.
   Names map 1:1 onto what LessonTutorPanel.vue would need:
   geometry state, clamp, snap, persist.
   ============================================================ */

const GEOMETRY_KEY = 'tutor.geometry'; // per Learner, survives lessons
const OPEN_KEY = 'tutor.open'; // per Lesson, session only

const px = (n) => `${Math.round(n)}px`;
const clamp = (n, min, max) => Math.min(Math.max(n, min), max);
const rem = (n) =>
    n * parseFloat(getComputedStyle(document.documentElement).fontSize);

/* ------------------------------------------------------------
   Geometry
   Position and size are a workspace preference, so they live in
   localStorage and outlive the Lesson. Whether the overlay was
   *open* is about this Lesson, so that stays in sessionStorage
   keyed by lesson — the split the shipping panel already makes
   for `open`, extended rather than replaced.
   ------------------------------------------------------------ */

/* First run parks the window above its own launcher, not on top of
   it: the Learner must be able to see the control they just used. */
const defaultGeometry = () => {
    const width = rem(24);
    const height = Math.min(rem(30), window.innerHeight - rem(8));

    return {
        mode: 'float',
        width,
        height,
        left: window.innerWidth - width - rem(1),
        top: window.innerHeight - height - rem(1) - rem(3.5),
        collapsed: false,
    };
};

function loadGeometry() {
    try {
        const raw = localStorage.getItem(GEOMETRY_KEY);
        return raw ? { ...defaultGeometry(), ...JSON.parse(raw) } : defaultGeometry();
    } catch {
        return defaultGeometry();
    }
}

function saveGeometry(g) {
    try {
        localStorage.setItem(GEOMETRY_KEY, JSON.stringify(g));
    } catch {
        /* private mode: the window still works, it just forgets */
    }
}

/* A window that remembers a position from a wider monitor must
   not restore off-screen. Clamp keeps at least --tutor-keep-visible
   of the header reachable on every axis. */
function clampToViewport(g) {
    const gutter = rem(1);
    const keep = rem(6);
    const maxW = clamp(g.width, rem(19), Math.min(rem(44), window.innerWidth - gutter * 2));
    const maxH = clamp(g.height, rem(14), window.innerHeight - gutter * 2);

    return {
        ...g,
        width: maxW,
        height: maxH,
        left: clamp(g.left, keep - maxW, window.innerWidth - keep),
        top: clamp(g.top, 0, window.innerHeight - rem(3)),
    };
}

/* ------------------------------------------------------------
   Controller
   ------------------------------------------------------------ */

export function createTutorOverlay(root) {
    const el = root.querySelector('.tutor');
    const header = root.querySelector('.tutor__header');
    const thread = root.querySelector('.tutor__thread');
    const input = root.querySelector('.tutor__input');
    const send = root.querySelector('.tutor__send');
    const hintLeft = root.querySelector('.tutor-snap-hint[data-side="left"]');
    const hintRight = root.querySelector('.tutor-snap-hint[data-side="right"]');
    const launchers = document.querySelectorAll('[data-tutor-launch]');

    let g = clampToViewport(loadGeometry());
    let open = sessionStorage.getItem(OPEN_KEY) === '1';

    /* --- render ---------------------------------------------- */

    function paint() {
        el.dataset.mode = g.mode;
        el.dataset.collapsed = String(g.collapsed);

        if (g.mode === 'float') {
            el.style.left = px(g.left);
            el.style.top = px(g.top);
            el.style.right = 'auto';
            el.style.width = px(g.width);
            el.style.height = px(g.height);
        } else {
            el.style.left = '';
            el.style.top = '';
            el.style.right = '';
            el.style.width = px(g.width);
            el.style.height = '';
        }

        root.hidden = !open;
        launchers.forEach((b) => b.setAttribute('aria-expanded', String(open)));
    }

    function commit() {
        saveGeometry(g);
        paint();
    }

    function setOpen(next) {
        open = next;
        sessionStorage.setItem(OPEN_KEY, next ? '1' : '0');
        paint();
        if (next) {
            input?.focus();
            scrollToLatest();
        }
    }

    function scrollToLatest() {
        requestAnimationFrame(() => {
            thread.scrollTop = thread.scrollHeight;
        });
    }

    /* --- drag ------------------------------------------------- */

    let drag = null;

    /* Move and up are bound to the document, not to the header.
       Pointer capture would be tidier, but a pointer that leaves
       the handle faster than the window can follow — which happens
       on every quick throw toward an edge — stops delivering events
       to the captured element in some engines, and the window is
       left stuck mid-flight. */
    header.addEventListener('pointerdown', (e) => {
        if (e.target.closest('button')) return; // header buttons are not the handle
        if (g.mode !== 'float') return;

        e.preventDefault();
        drag = { id: e.pointerId, dx: e.clientX - g.left, dy: e.clientY - g.top };
        el.dataset.dragging = 'true';

        document.addEventListener('pointermove', onDragMove);
        document.addEventListener('pointerup', onDragEnd);
        document.addEventListener('pointercancel', onDragEnd);
    });

    function onDragMove(e) {
        if (!drag) return;

        g.left = e.clientX - drag.dx;
        g.top = e.clientY - drag.dy;

        const side = snapSide(e.clientX);
        hintLeft.dataset.active = String(side === 'left');
        hintRight.dataset.active = String(side === 'right');

        paint();
    }

    function onDragEnd(e) {
        if (!drag) return;
        drag = null;
        delete el.dataset.dragging;
        hintLeft.dataset.active = 'false';
        hintRight.dataset.active = 'false';

        document.removeEventListener('pointermove', onDragMove);
        document.removeEventListener('pointerup', onDragEnd);
        document.removeEventListener('pointercancel', onDragEnd);

        const side = snapSide(e.clientX);
        if (side) {
            dock(side);
        } else {
            g = clampToViewport(g);
            commit();
        }
    }

    function snapSide(x) {
        const t = 28;
        if (x <= t) return 'left';
        if (x >= window.innerWidth - t) return 'right';
        return null;
    }

    function dock(side) {
        g.mode = side === 'left' ? 'dock-left' : 'dock-right';
        g.width = clamp(g.width, rem(20), rem(38));
        g.collapsed = false;
        commit();
    }

    function undock() {
        g.mode = 'float';
        g.left = clamp(g.left, rem(1), window.innerWidth - g.width - rem(1));
        g.top = rem(4);
        commit();
    }

    /* --- resize ------------------------------------------------ */

    root.querySelectorAll('.tutor__grip').forEach((grip) => {
        grip.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            const dir = grip.dataset.dir;
            const start = { x: e.clientX, y: e.clientY, ...g };
            el.dataset.resizing = 'true';

            const onMove = (ev) => {
                const dx = ev.clientX - start.x;
                const dy = ev.clientY - start.y;

                if (dir.includes('e')) g.width = start.width + dx;
                if (dir.includes('s')) g.height = start.height + dy;
                if (dir.includes('w')) {
                    g.width = start.width - dx;
                    g.left = start.left + dx;
                }
                if (dir.includes('n')) {
                    g.height = start.height - dy;
                    g.top = start.top + dy;
                }

                /* Clamp width/height first, then re-derive the moving
                   edge — otherwise dragging past the minimum walks the
                   opposite edge across the screen. */
                const w = clamp(g.width, rem(19), rem(44));
                const h = clamp(g.height, rem(14), window.innerHeight - rem(2));
                if (dir.includes('w')) g.left = start.left + (start.width - w);
                if (dir.includes('n')) g.top = start.top + (start.height - h);
                g.width = w;
                g.height = h;

                paint();
            };

            const onUp = () => {
                delete el.dataset.resizing;
                document.removeEventListener('pointermove', onMove);
                document.removeEventListener('pointerup', onUp);
                commit();
            };

            document.addEventListener('pointermove', onMove);
            document.addEventListener('pointerup', onUp);
        });
    });

    /* --- keyboard ---------------------------------------------
       Dragging is a pointer gesture; every pointer gesture here
       has a key. Without this the whole feature is unusable with
       a keyboard, which is not a trade-off we get to make.
       --------------------------------------------------------- */

    header.addEventListener('keydown', (e) => {
        const step = e.shiftKey ? 1 : 16;
        const moves = {
            ArrowLeft: [-step, 0],
            ArrowRight: [step, 0],
            ArrowUp: [0, -step],
            ArrowDown: [0, step],
        };

        if (e.altKey && moves[e.key]) {
            e.preventDefault();
            g.width = clamp(g.width + moves[e.key][0], rem(19), rem(44));
            g.height = clamp(g.height + moves[e.key][1], rem(14), window.innerHeight);
            commit();
            return;
        }

        if (moves[e.key] && g.mode === 'float') {
            e.preventDefault();
            g.left += moves[e.key][0];
            g.top += moves[e.key][1];
            g = clampToViewport(g);
            commit();
            return;
        }

        if (e.key === 'Home') dock('left');
        if (e.key === 'End') dock('right');
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleCollapse();
        }
    });

    header.addEventListener('dblclick', (e) => {
        if (e.target.closest('button')) return;
        toggleCollapse();
    });

    function toggleCollapse() {
        g.collapsed = !g.collapsed;
        commit();
    }

    /* --- window chrome ----------------------------------------- */

    root.querySelector('[data-act="collapse"]')?.addEventListener('click', toggleCollapse);
    root.querySelector('[data-act="close"]')?.addEventListener('click', () => setOpen(false));
    root.querySelector('[data-act="dock"]')?.addEventListener('click', () => {
        g.mode === 'float' ? dock('right') : undock();
    });

    const menu = root.querySelector('.tutor__menu');
    root.querySelector('[data-act="menu"]')?.addEventListener('click', () => {
        menu.hidden = !menu.hidden;
    });

    launchers.forEach((b) =>
        b.addEventListener('click', () => {
            if (b.dataset.state === 'unavailable') return;
            setOpen(!open);
        }),
    );

    document.addEventListener('keydown', (e) => {
        /* Alt+T opens and closes. Escape collapses rather than
           closes: a Learner reaching for Escape wants the Lesson
           back, not the transcript gone. */
        if (e.altKey && e.key.toLowerCase() === 't') {
            e.preventDefault();
            setOpen(!open);
        }
        if (e.key === 'Escape' && open && !g.collapsed) {
            g.collapsed = true;
            commit();
        }
    });

    window.addEventListener('resize', () => {
        g = clampToViewport(g);
        paint();
    });

    /* --- composer ----------------------------------------------- */

    input?.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = px(Math.min(input.scrollHeight, rem(8)));
        send.disabled = input.value.trim().length === 0;
    });

    input?.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
            e.preventDefault();
            root.querySelector('.tutor__form')?.requestSubmit();
        }
    });

    /* Persist the clamped geometry, not just the painted one: a
       position rescued from a wider monitor should stay rescued
       even if the Learner never touches the window. */
    paint();
    saveGeometry(g);

    return { setOpen, dock, undock, scrollToLatest, geometry: () => g };
}

/* ============================================================
   Focus changes — DECIDED: follow
   ------------------------------------------------------------
   ADR 009: "The overlay's Focus is the Lesson URL." So when a
   Learner clicks Selanjutnya with the overlay open, Focus really
   does move, and the Conversation on screen stops being the one
   new turns are recorded against.

   We follow the URL and say so with a divider. The Learner loses
   sight of an answer they were half-way through, which is the
   real cost — but the record keeps it, so navigating back brings
   it straight back, and the overlay never shows a composer
   pointed somewhere other than where it will write.

   We rejected: ASK — freeze the old transcript and offer to move
   — because declining leaves an overlay Focus that is not the
   URL, which is an amendment to ADR 009 rather than a styling
   choice. And CARRY — keep the old turns above the divider —
   because a visible answer the Tutor can no longer see invites
   "tapi tadi kamu bilang…", and the screen would be promising a
   continuity the Conversation does not have.
   ============================================================ */

/**
 * @param {{ id: number, title: string, locked: boolean }} nextFocus
 * @param {{ el: HTMLElement, thread: HTMLElement }} ui
 */
export function applyFocusChange(nextFocus, ui) {
    /* The divider goes in before the thread is refilled, so it
       reads as the last thing that happened on the Conversation
       being left rather than the first thing on the new one. */
    renderFocusShift(ui.thread, nextFocus.title);

    ui.el.querySelector('.tutor__focus-label').textContent = nextFocus.title;
    ui.el.querySelector('.tutor__focus').dataset.locked = String(nextFocus.locked);
}

export function renderFocusShift(thread, title) {
    const row = document.createElement('div');
    row.className = 'tutor__focus-shift';
    row.textContent = `Fokus pindah ke ${title}`;
    thread.append(row);
}
